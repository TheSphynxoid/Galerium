<?php

namespace App\Command;

use App\Entity\Artiste;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Créer un compte administrateur'
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Email de l\'administrateur')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Mot de passe')
            ->addOption('nom', null, InputOption::VALUE_OPTIONAL, 'Nom')
            ->addOption('prenom', null, InputOption::VALUE_OPTIONAL, 'Prénom');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        $io->title('Création d\'un compte administrateur');

        // Email
        $email = $input->getOption('email');
        if (!$email) {
            $question = new Question('Entrez l\'email de l\'administrateur: ');
            $email = $helper->ask($input, $output, $question);
        }

        // Vérifier si l'email existe déjà
        $existingArtiste = $this->entityManager->getRepository(Artiste::class)->findOneBy(['email' => $email]);
        if ($existingArtiste) {
            $question = new ConfirmationQuestion(
                "Un compte avec l'email {$email} existe déjà. Voulez-vous le promouvoir administrateur ? (y/n): ",
                false
            );

            if ($helper->ask($input, $output, $question)) {
                $existingArtiste->setRoles(['ROLE_ADMIN']);
                $this->entityManager->flush();
                $io->success("Le compte {$email} a été promu administrateur avec succès !");
                return Command::SUCCESS;
            } else {
                $io->error('Opération annulée.');
                return Command::FAILURE;
            }
        }

        // Nom
        $nom = $input->getOption('nom');
        if (!$nom) {
            $question = new Question('Entrez le nom: ');
            $nom = $helper->ask($input, $output, $question);
        }

        // Prénom
        $prenom = $input->getOption('prenom');
        if (!$prenom) {
            $question = new Question('Entrez le prénom: ');
            $prenom = $helper->ask($input, $output, $question);
        }

        // Mot de passe
        $password = $input->getOption('password');
        if (!$password) {
            $question = new Question('Entrez le mot de passe: ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $password = $helper->ask($input, $output, $question);
        }

        // Créer l'artiste admin
        $artiste = new Artiste();
        $artiste->setEmail($email);
        $artiste->setNom($nom);
        $artiste->setPrenom($prenom);
        $artiste->setRoles(['ROLE_ADMIN']);
        $artiste->setIsActive(true);

        $hashedPassword = $this->passwordHasher->hashPassword($artiste, $password);
        $artiste->setPassword($hashedPassword);

        $this->entityManager->persist($artiste);
        $this->entityManager->flush();

        $io->success([
            "Compte administrateur créé avec succès !",
            "Email: {$email}",
            "Nom: {$prenom} {$nom}",
            "",
            "Vous pouvez maintenant vous connecter et accéder à l'interface admin:",
            "URL: /admin/dashboard"
        ]);

        return Command::SUCCESS;
    }
}



