<?php

namespace App\Service;

use App\Entity\Enchere;
use App\Entity\Offre;
use App\Entity\Utilisateur;
use App\Enum\EnchereStatut;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class BiddingService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RedisService $redisService
    ) {}

    /**
     * Process a bid for an auction with Redis-based concurrency control
     * 
     * @param Enchere $enchere The auction/enchère
     * @param Utilisateur $user The user placing the bid
     * @param float $bidAmount The bid amount
     * @param float $minimumIncrease Optional minimum increase from previous bid
     * 
     * @return array Result containing success status and message
     */
    public function placeBid(
        Enchere $enchere,
        Utilisateur $user,
        float $bidAmount,
        float $minimumIncrease = 0
    ): array {
        // Validate auction status
        if ($enchere->getStatut() !== EnchereStatut::ACTIVE) {
            return [
                'success' => false,
                'message' => "This auction is not active. Current status: {$enchere->getStatut()->value}",
                'code' => 'AUCTION_NOT_ACTIVE'
            ];
        }

        // Validate bid timing
        $now = new \DateTime();
        if ($enchere->getDateDebut() > $now) {
            return [
                'success' => false,
                'message' => "This auction hasn't started yet.",
                'code' => 'AUCTION_NOT_STARTED'
            ];
        }

        if ($enchere->getDateFin() && $enchere->getDateFin() < $now) {
            return [
                'success' => false,
                'message' => "This auction has ended.",
                'code' => 'AUCTION_ENDED'
            ];
        }

        // Validate minimum bid against base price
        if ($bidAmount < $enchere->getPrixDeBase()) {
            return [
                'success' => false,
                'message' => "Your bid must be at least {$enchere->getPrixDeBase()} (the base price).",
                'code' => 'BID_BELOW_BASE_PRICE',
                'minimumRequired' => $enchere->getPrixDeBase()
            ];
        }

        // Use Redis for concurrent bid processing
        $result = \App\Service\AuctionUtils::placeBid(
            $enchere->getId(),
            $user->getId(),
            $bidAmount,
            $minimumIncrease
        );

        if (!$result['success']) {
            return $result;
        }

        // If Redis accepted the bid, persist to database
        try {
            $offre = new Offre();
            $offre->setMontant($bidAmount);
            $offre->setDateOffre(new \DateTime());
            $offre->setEchere($enchere);
            $offre->setUser($user);

            $this->entityManager->persist($offre);
            
            // Update the auction's current price
            $enchere->setPrixActuel($bidAmount);
            
            $this->entityManager->flush();

            return [
                'success' => true,
                'message' => "Your bid of $bidAmount has been placed successfully!",
                'code' => 'BID_ACCEPTED',
                'amount' => $bidAmount,
                'offre_id' => $offre->getId(),
                'user_id' => $user->getId()
            ];
        } catch (\Exception $e) {
            // If database fails, remove from Redis
            \App\Service\AuctionUtils::clearAuction($enchere->getId());
            
            return [
                'success' => false,
                'message' => "Failed to save bid to database: {$e->getMessage()}",
                'code' => 'DATABASE_ERROR'
            ];
        }
    }

    /**
     * Get the current winning bid for an auction
     */
    public function getHighestBid(Enchere $enchere): ?array
    {
        return \App\Service\AuctionUtils::getHighestBid($enchere->getId());
    }

    /**
     * Get all bids placed on an auction
     */
    public function getAllBids(Enchere $enchere): array
    {
        return \App\Service\AuctionUtils::getAllBids($enchere->getId());
    }

    /**
     * Get bid statistics for an auction
     */
    public function getBidStats(Enchere $enchere): array
    {
        $bidCount = \App\Service\AuctionUtils::getBidCount($enchere->getId());
        $highestBid = \App\Service\AuctionUtils::getHighestBid($enchere->getId());

        return [
            'totalBids' => $bidCount,
            'currentPrice' => $enchere->getPrixActuel(),
            'basePrice' => $enchere->getPrixDeBase(),
            'highestBidder' => $highestBid['userId'] ?? null,
            'highestAmount' => $highestBid['amount'] ?? null,
            'status' => $enchere->getStatut()->value,
            'timeRemaining' => $this->getTimeRemaining($enchere)
        ];
    }

    /**
     * Get time remaining for the auction
     */
    private function getTimeRemaining(Enchere $enchere): ?int
    {
        if (!$enchere->getDateFin()) {
            return null;
        }

        $now = new \DateTime();
        $remaining = $enchere->getDateFin()->getTimestamp() - $now->getTimestamp();

        return max(0, $remaining);
    }

    /**
     * Clear all bid data for an auction (useful for testing or closing)
     */
    public function clearAuctionBids(Enchere $enchere): void
    {
        \App\Service\AuctionUtils::clearAuction($enchere->getId());
    }

    /**
     * Convert a bid result to a JSON response
     */
    public function bidResultToJsonResponse(array $result): JsonResponse
    {
        $statusCode = $result['success'] ? 200 : 400;
        
        return new JsonResponse(
            [
                'success' => $result['success'],
                'message' => $result['message'],
                'code' => $result['code'],
                'data' => array_diff_key($result, ['success' => 1, 'message' => 1, 'code' => 1])
            ],
            $statusCode
        );
    }
}
