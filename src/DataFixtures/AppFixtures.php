<?php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\Author;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
  public function load(ObjectManager $manager): void
  {
    for ($i = 0; $i < 20; $i++) {
      $book = new Book();
      $book->setTitle("Titre " . $i);
      $book->setCoverText("Quatrième de couverture numéro: " . $i);

      $manager->persist($book);
    }

    for ($i = 0; $i < 13; $i++) {
      $author = new Author();
      $author->setFirstName("firstName " . $i);
      $author->setLastName("lastName " . $i);

      $manager->persist($author);
    }

    // Création d'un user "normal"
    $user = new User();
    $user->setEmail("user@bookapi.com");
    $user->setRoles(["ROLE_USER"]);
    $user->setPassword($this->userPasswordHasher->hashPassword($user, "password"));
    $manager->persist($user);

    // Création d'un user admin
    $userAdmin = new User();
    $userAdmin->setEmail("admin@bookapi.com");
    $userAdmin->setRoles(["ROLE_ADMIN"]);
    $userAdmin->setPassword($this->userPasswordHasher->hashPassword($userAdmin, "password"));
    $manager->persist($userAdmin);

    $manager->flush();
  }

  private $userPasswordHasher;

  public function __construct(UserPasswordHasherInterface $userPasswordHasher)
  {
    $this->userPasswordHasher = $userPasswordHasher;
  }

}
