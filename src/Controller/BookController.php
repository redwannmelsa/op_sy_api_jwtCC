<?php

namespace App\Controller;

use App\Repository\AuthorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\BookRepository;
use App\Entity\Book;
use Symfony\Component\Validator\Validator\ValidatorInterface;


class BookController extends AbstractController
{
  #[Route('/book', name: 'app_book')]
  public function index(): JsonResponse
  {
    return $this->json([
      'message' => 'Welcome to your new controller!',
      'path' => 'src/Controller/BookController.php',
    ]);
  }

  #[Route('/api/book', name: 'app_book_list', methods: ['GET'])]
  public function getBookList(BookRepository $bookRepository, SerializerInterface $serializer): JsonResponse
  {
    $bookList = $bookRepository->findAll();
    $jsonBookList = $serializer->serialize($bookList, 'json', ['groups' => ['book' => 'getBooks']]);
    return new JsonResponse($jsonBookList, Response::HTTP_OK, [], true);
  }

  #[Route('/api/book/{id}', name: 'detailBook', methods: ['GET'])]
  public function getDetailBook(Book $book, SerializerInterface $serializer): JsonResponse
  {
    $jsonBook = $serializer->serialize($book, 'json');
    return new JsonResponse($jsonBook, Response::HTTP_OK, ['accept' => 'json'], true);
  }


  #[Route('/api/books', name: 'createBook', methods: ['POST'])]
  #[IsGranted("ROLE_ADMIN", statusCode: 404, message: "Vous n'avez pas les droits suffisants pour créer un livre")]
  public function createBook(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator, AuthorRepository $authorRepository, ValidatorInterface $validator): JsonResponse
  {
    $book = $serializer->deserialize($request->getContent(), Book::class, 'json');

    // Récupération de l'ensemble des données envoyées sous forme de tableau
    $content = $request->toArray();

    $errors = $validator->validate($book);
    if ($errors->count() > 0) {
      return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
    }

    // Récupération de l'idAuthor. S'il n'est pas défini, alors on met -1 par défaut.
    $idAuthor = $content['idAuthor'] ?? -1;

    // On cherche l'auteur qui correspond et on l'assigne au livre.
    // Si "find" ne trouve pas l'auteur, alors null sera retourné.
    $book->setAuthor($authorRepository->find($idAuthor));

    $em->persist($book);
    $em->flush();

    $jsonBook = $serializer->serialize($book, 'json', ['groups' => 'getBooks']);

    $location = $urlGenerator->generate('detailBook', ['id' => $book->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

    return new JsonResponse($jsonBook, Response::HTTP_CREATED, ['Location' => $location], true);
  }

  #[Route('/api/books/{id}', name: 'updateBook', methods: ['PUT'])]
  public function updateBook(Request $request, SerializerInterface $serializer, Book $currentBook, EntityManagerInterface $em, AuthorRepository $authorRepository)
  {
    $updatedBook = $serializer->deserialize(
      $request->getContent(),
      Book::class,
      'json',
      [AbstractNormalizer::OBJECT_TO_POPULATE => $currentBook]
    );

    $content = $request->toArray();
    $idAuthor = $content['idAuthor'] ?? -1;
    $updatedBook->setAuthor($authorRepository->find($idAuthor));

    $em->persist($updatedBook);
    $em->flush();

    return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
  }
}
