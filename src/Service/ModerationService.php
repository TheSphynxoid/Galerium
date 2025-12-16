<?php

namespace App\Service;

use App\Entity\Commentaire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ModerationService
{
    private const BANNED_WORDS = ['spam', 'hate', 'xxx', 'arnaque', 'escroc', 'pute', 'merde'];

    public function __construct(
        private EntityManagerInterface $em,
        private MailerInterface $mailer
    ) {}

    public function scanAndFlag(Commentaire $commentaire): bool
    {
        $contenu = mb_strtolower($commentaire->getContenu());
        foreach (self::BANNED_WORDS as $word) {
            if (str_contains($contenu, $word)) {
                $commentaire->setStatut('signale');
                $this->em->flush();
                $this->notifyAdmin($commentaire, 'auto-flag');
                return true;
            }
        }
        return false;
    }

    public function reportComment(Commentaire $commentaire): void
    {
        $commentaire->incrementSignalements();
        if ($commentaire->getSignalements() >= 3) {
            $commentaire->setStatut('masque');
            $this->notifyAdmin($commentaire, 'auto-hide');
        }
        $this->em->flush();
    }

    private function notifyAdmin(Commentaire $commentaire, string $reason): void
    {
        $email = (new Email())
            ->from('noreply@galerium.tn')
            ->to('hatstraw133@gmail.com')
            ->subject('Modération: commentaire signalé')
            ->text(sprintf(
                "Le commentaire #%d a été %s.\nContenu: %s\nSignalements: %d",
                $commentaire->getId(),
                $reason === 'auto-flag' ? 'auto-flaggé' : 'masqué après 3 signalements',
                $commentaire->getContenu(),
                $commentaire->getSignalements()
            ));

        $this->mailer->send($email);
    }
}