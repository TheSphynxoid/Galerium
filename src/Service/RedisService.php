<?php

namespace App\Service;

class RedisService
{
    private static \Predis\Client $client;
    public function __construct(
        private \Predis\Client $redis
    ) {
        self::$client = $redis;
    }

    public static function GetClient()
    {
        return self::$client;
    }
}

class AuctionUtils
{

    public static function placeBid(int $auctionId, int $userId, float $amount): bool
    {
        $bidsKey = "auction:$auctionId:bids";
        $client = RedisService::GetClient();
        // Watch for concurrent modification
        $client->watch($bidsKey);

        // Read highest existing bid
        $current = $client->zrevrange($bidsKey, 0, 0, ['withscores' => true]);
        if (!empty($current)) {
            $highestAmount = array_values($current)[0];

            if ($amount <= $highestAmount) {
                $client->unwatch();
                return false;
            }
        }

        // Create transaction
        $transaction = $client->multi();

        // Add bid atomically
        $transaction->zadd($bidsKey, [$userId => $amount]);

        // Execute the transaction
        $result = $transaction->exec();

        if ($result === null) {
            // Transaction aborted due to race condition
            return false;
        }

        return true;
    }

    public static function getHighestBid(int $auctionId): ?array
    {
        $client = RedisService::GetClient();
        $bidsKey = "auction:$auctionId:bids";

        $data = $client->zrevrange($bidsKey, 0, 0, ['withscores' => true]);
        if (empty($data)) {
            return null;
        }

        return [
            'userId' => array_key_first($data),
            'amount' => array_values($data)[0],
        ];
    }

    public static function clearAuction(int $auctionId): void
    {
        $client = RedisService::GetClient();

        $client->del(
            "auction:$auctionId:bids",
            "auction:$auctionId:meta"
        );
    }
}
