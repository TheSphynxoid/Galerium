<?php

namespace App\Service;

use App\Entity\Enchere;
use App\Entity\Offre;
use App\Entity\Utilisateur;
use App\Enum\EnchereStatut;
use App\Repository\OffreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class BiddingService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OffreRepository $offreRepository,
        private readonly LoggerInterface $logger,
        private readonly OutbidNotificationService $outbidNotifier,
    ) {
    }

    public function placeBid(Enchere $enchere, Utilisateur $user, float $amount): Offre
    {
        if ($enchere->getStatut() !== EnchereStatut::ACTIVE->value) {
            throw new \RuntimeException('Enchère non active.');
        }

        $now = new \DateTimeImmutable();
        if ($enchere->getDateFin() && $enchere->getDateFin() < $now) {
            throw new \RuntimeException('Enchère terminée.');
        }

        $current = $enchere->getPrixActuel() ?? $enchere->getPrixDeBase() ?? 0.0;
        $minIncrement = $enchere->getMinIncrement() ?? 1.0;
        $requiredMin = $current + $minIncrement;
        if ($amount < $requiredMin) {
            throw new \RuntimeException(sprintf('Offre minimale requise: %.2f', $requiredMin));
        }

        $lastOffer = $this->offreRepository->findHighestByEchere($enchere);
        if ($lastOffer && $lastOffer->getUser() && $lastOffer->getUser()->getId() === $user->getId()) {
            throw new \RuntimeException('Vous êtes déjà le meilleur enchérisseur.');
        }

        $offer = new Offre();
        $offer->setEchere($enchere)
            ->setUser($user)
            ->setMontant($amount)
            ->setDateOffre(new \DateTime());

        $this->em->persist($offer);

        $enchere->setPrixActuel($amount);

        if ($enchere->getDateFin() !== null) {
            $threshold = (clone $enchere->getDateFin())->modify('-' . $enchere->getAntiSnipingThresholdMinutes() . ' minutes');
            if ($now >= $threshold) {
                $enchere->setDateFin((clone $enchere->getDateFin())->modify('+' . $enchere->getAntiSnipingExtensionMinutes() . ' minutes'));
                $this->logger->info('Anti-sniping extension applied', [
                    'enchere_id' => $enchere->getId(),
                    'new_end' => $enchere->getDateFin()?->format(DATE_ATOM),
                ]);
            }
        }

        $this->em->flush();

        // Send outbid notification AFTER flush so offer is persisted
        try {
            $this->outbidNotifier->notifyOutbid($enchere, $offer);
        } catch (\Throwable $e) {
            $this->logger->error('Error notifying outbid', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $offer;
    }
}
