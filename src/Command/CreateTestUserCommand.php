<?php

namespace App\Command;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateTestUserCommand extends Command
{
    protected static $defaultName = 'app:create-test-user';
    
    private $entityManager;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure()
    {
        $this
            ->setDescription('Crée un utilisateur de test')
            ->setHelp('Cette commande crée un utilisateur de test avec le rôle ADMIN');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = 'admin@example.com';
        $password = 'admin123';

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);
        
        if ($existingUser) {
            $output->writeln('Un utilisateur avec cet email existe déjà.');
            return Command::FAILURE;
        }

        // Créer un nouvel utilisateur
        $user = new Utilisateur();
        $user->setEmail($email);
        $user->setNom('Admin');
        $user->setPrenom('Test');
        $user->setRole('ADMIN');
        $user->setDateInscription(new \DateTime());
        $user->setIsVerified(true);
        
        // Hacher le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Enregistrer l'utilisateur
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('Utilisateur créé avec succès !');
        $output->writeln(sprintf('Email: %s', $email));
        $output->writeln(sprintf('Mot de passe: %s', $password));

        return Command::SUCCESS;
    }
}
