<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Conseil;
use Faker;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');
        $months = ['01','02','03','04','05','06','07','08','09','10','11','12'];

        $users = [];

        for ($i = 0; $i < 5; $i++) {
            $user = new User();
            $user->setEmail($faker->email);
            $user->setCity($faker->city);
            $user->setZipcode($faker->postcode);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $user->setCreatedAt(new \DateTimeImmutable());

            $manager->persist($user);
            $users[] = $user;
        }

        for ($i = 0; $i < 5; $i++) {
            $conseil = new Conseil();
            $conseil->setText($faker->sentence());
            shuffle($months);
            $conseil->setMonth(array_slice($months, 0, 3));
            $conseil->setCreatedAt(new \DateTimeImmutable());
            $conseil->setUpdatedAt(new \DateTimeImmutable());

            $conseil->setAuthor($users[array_rand($users)]);

            $manager->persist($conseil);
        }

        $manager->flush();
    }
}
