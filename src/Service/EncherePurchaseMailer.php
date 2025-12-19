<?php

namespace App\Service;

use App\Entity\Enchere;
use App\Repository\OffreRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\UriSigner;

class EncherePurchaseMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly OffreRepository $offreRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly UriSigner $uriSigner,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function sendPurchaseLink(Enchere $enchere): void
    {
        // Determine winning offer (highest bid)
        $winningOffer = $this->offreRepository->findHighestByEchere($enchere);

        if (!$winningOffer) {
            $this->logger->info('No offers found for enchere, skipping purchase email', [
                'enchere_id' => $enchere->getId(),
            ]);
            return;
        }

        $winner = $winningOffer->getUser();
        if (!$winner || !$winner->getEmail()) {
            $this->logger->warning('Winning offer has no user/email, skipping purchase email', [
                'enchere_id' => $enchere->getId(),
                'offer_id' => $winningOffer->getId(),
            ]);
            return;
        }

        // Generate signed purchase URL
        $unsignedUrl = $this->urlGenerator->generate(
            'app_enchere_purchase',
            ['id' => $enchere->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $signedUrl = $this->uriSigner->sign($unsignedUrl);

        $email = (new TemplatedEmail())
            ->from($_ENV['MAILER_FROM_EMAIL'] ?? 'noreply@thesphynx.net')
            ->to($winner->getEmail())
            ->subject('Terminé: Achetez votre œuvre gagnée - ' . ($enchere->getOeuvre()?->getTitle() ?? 'Enchère #' . $enchere->getId()))
            ->htmlTemplate('emails/enchere_purchase_link.html.twig')
            ->context([
                'enchere' => $enchere,
                'oeuvre' => $enchere->getOeuvre(),
                'amount' => $winningOffer->getMontant(),
                'purchaseUrl' => $signedUrl,
                'user' => $winner,
            ]);

        try {
            $this->mailer->send($email);
            $this->logger->info('Sent purchase link email to auction winner', [
                'enchere_id' => $enchere->getId(),
                'email' => $winner->getEmail(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send purchase link email', [
                'enchere_id' => $enchere->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
