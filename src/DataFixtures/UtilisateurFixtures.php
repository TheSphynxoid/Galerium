<?php

namespace App\DataFixtures;

use App\Entity\Utilisateur;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UtilisateurFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // 1) CRÉATION ADMIN
        $admin = new Utilisateur();
        $admin->setNom('Admin');
        $admin->setPrenom('Super');
        $admin->setEmail('admin@example.com');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setRole('ADMIN');
        $admin->setDateInscription(new \DateTime());
        $admin->setIsVerified(true);
        $manager->persist($admin);

        // 2) CRÉATION 5 JURYS
        for ($i = 1; $i <= 5; $i++) {
            $jury = new Utilisateur();
            $jury->setNom("Jury$i");
            $jury->setPrenom("Member$i");
            $jury->setEmail("jury$i@example.com");
            $jury->setPassword($this->passwordHasher->hashPassword($jury, 'jury123'));
            $jury->setRole('JURY');
            $jury->setDateInscription(new \DateTime());
            $jury->setIsVerified(true);
            $manager->persist($jury);
        }

        $manager->flush();
    }
}