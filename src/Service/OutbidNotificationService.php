<?php

namespace App\Service;

use App\Entity\Enchere;
use App\Entity\Offre;
use App\Repository\OffreRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class OutbidNotificationService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly OffreRepository $offreRepository,
        private readonly LoggerInterface $logger,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function notifyOutbid(Enchere $enchere, Offre $newOffer): void
    {
        // Get the previous highest offer (before the new one)
        $previousOffers = $this->offreRepository->createQueryBuilder('o')
            ->andWhere('o.echere = :enchere')
            ->andWhere('o.id != :newOfferId')
            ->setParameter('enchere', $enchere)
            ->setParameter('newOfferId', $newOffer->getId())
            ->orderBy('o.montant', 'DESC')
            ->addOrderBy('o.dateOffre', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$previousOffers) {
            return; // No previous offer to notify
        }

        $previousBidder = $previousOffers->getUser();
        if (!$previousBidder || !$previousBidder->getEmail()) {
            $this->logger->warning('Previous bidder has no email', [
                'enchere_id' => $enchere->getId(),
                'previous_offer_id' => $previousOffers->getId(),
            ]);
            return;
        }

        $auctionUrl = $this->urlGenerator->generate(
            'app_enchere_show',
            ['id' => $enchere->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new TemplatedEmail())
            ->from($_ENV['MAILER_FROM_EMAIL'] ?? 'noreply@galerium.com')
            ->to($previousBidder->getEmail())
            ->subject('Vous avez été surenchéri - ' . ($enchere->getOeuvre()?->getTitle() ?? 'Enchère #' . $enchere->getId()))
            ->htmlTemplate('emails/outbid_notification.html.twig')
            ->context([
                'enchere' => $enchere,
                'oeuvre' => $enchere->getOeuvre(),
                'previousBid' => $previousOffers->getMontant(),
                'newBid' => $newOffer->getMontant(),
                'newBidder' => $newOffer->getUser()?->getPrenom() ?? 'Un enchérisseur',
                'user' => $previousBidder,
                'auctionUrl' => $auctionUrl,
            ]);

        try {
            $this->mailer->send($email);
            $this->logger->info('Sent outbid notification to previous bidder', [
                'enchere_id' => $enchere->getId(),
                'email' => $previousBidder->getEmail(),
                'previous_bid' => $previousOffers->getMontant(),
                'new_bid' => $newOffer->getMontant(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send outbid notification', [
                'enchere_id' => $enchere->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
