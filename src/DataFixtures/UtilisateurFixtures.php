<?php

namespace App\DataFixtures;

use App\Entity\Utilisateur;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UtilisateurFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // ============================
        //   1) CRÉATION ADMIN
        // ============================
        $admin = new Utilisateur();
        $admin->setEmail('admin@example.com');
        $admin->setNom('Admin');
        $admin->setPrenom('Super');
        $admin->setRole('ADMIN');
        $admin->setDateInscription(new \DateTime());

        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin123');
        $admin->setPassword($hashedPassword);

        $manager->persist($admin);

        // ============================
        //   2) CRÉATION 5 JURY
        // ============================
        for ($i = 1; $i <= 5; $i++) {
            $jury = new Utilisateur();
            $jury->setEmail("jury$i@example.com");
            $jury->setNom("JuryNom$i");
            $jury->setPrenom("JuryPrenom$i");
            $jury->setRole('JURY');
            $jury->setDateInscription(new \DateTime());

            $hashedPassword = $this->passwordHasher->hashPassword($jury, 'jury123');
            $jury->setPassword($hashedPassword);

            $manager->persist($jury);
        }

        $manager->flush();
    }
}
