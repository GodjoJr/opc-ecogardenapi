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

        $admin = new User();
        $admin->setEmail('admin@ecogarden.com');
        $admin->setCity('Paris');
        $admin->setZipcode('75000');
        $admin->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
        $admin->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($admin);
        $users[] = $admin;

        $user = new User();
        $user->setEmail('user@ecogarden.com');
        $user->setCity('Lyon');
        $user->setZipcode('69000');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $user->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($user);
        $users[] = $user;

        for ($i = 0; $i < 5; $i++) {
            $randomUser = new User();
            $randomUser->setEmail($faker->email);
            $randomUser->setCity($faker->city);
            $randomUser->setZipcode($faker->postcode);
            $randomUser->setRoles(['ROLE_USER']);
            $randomUser->setPassword($this->passwordHasher->hashPassword($randomUser, 'password'));
            $randomUser->setCreatedAt(new \DateTimeImmutable());

            $manager->persist($randomUser);
            $users[] = $randomUser;
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
