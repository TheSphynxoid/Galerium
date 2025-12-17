<?php

namespace App\Service;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class EmailVerificationService
{
    private $em;
    private $mailer;
    private $logger;

    public function __construct(
        EntityManagerInterface $em, 
        MailerInterface $mailer,
        LoggerInterface $logger
    ) {
        $this->em = $em;
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    public function generateVerificationCode(Utilisateur $user): string
    {
        $code = str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        $user->setVerificationCode($code);
        $user->setVerificationCodeExpiresAt(new \DateTime('+15 minutes'));
        
        $this->em->persist($user);
        $this->em->flush();

        return $code;
    }

    public function sendVerificationEmail(Utilisateur $user): bool
    {
        try {
            $code = $this->generateVerificationCode($user);
            
            $email = (new Email())
                ->from($_ENV['MAILER_FROM_EMAIL'] ?? 'noreply@galerium.com')
                ->to($user->getEmail())
                ->subject('Vérification de votre adresse email - Galerium')
                ->text(sprintf(
                    'Bonjour %s,

Votre code de vérification est : %s

Ce code est valable 15 minutes.

Si vous n\'avez pas demandé cette vérification, veuillez ignorer cet email.

Cordialement,
L\'équipe Galerium',
                    $user->getPrenom() ?: 'utilisateur',
                    $code
                ));

            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de l\'email de vérification', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]);
            return false;
        }
    }

    public function isVerificationCodeValid(Utilisateur $user, string $code): bool
    {
        if ($user->getVerificationCode() !== $code) {
            return false;
        }

        $now = new \DateTime();
        if ($user->getVerificationCodeExpiresAt() < $now) {
            return false;
        }

        return true;
    }

    public function verifyUser(Utilisateur $user, string $code): bool
    {
        if (!$this->isVerificationCodeValid($user, $code)) {
            return false;
        }

        $user->setIsVerified(true);
        $user->setVerificationCode(null);
        $user->setVerificationCodeExpiresAt(null);
        
        $this->em->persist($user);
        $this->em->flush();

        return true;
    }
}
