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

    public static function GetClient(): \Predis\Client
    {
        return self::$client;
    }

    /**
     * Acquire a distributed lock using Redis
     */
    public static function acquireLock(string $key, int $timeout = 10): bool
    {
        $client = self::GetClient();
        $lockKey = "lock:$key";
        $lockValue = uniqid('lock_', true);
        
        // Try to set lock with NX (only if not exists) and EX (expire in timeout seconds)
        $result = $client->set($lockKey, $lockValue, 'NX', 'EX', $timeout);
        
        return $result !== null;
    }

    /**
     * Release a distributed lock
     */
    public static function releaseLock(string $key): void
    {
        $client = self::GetClient();
        $lockKey = "lock:$key";
        $client->del($lockKey);
    }

    /**
     * Get the current highest bid for an auction
     */
    public static function getHighestBid(int $auctionId): ?array
    {
        $client = self::GetClient();
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

    /**
     * Add a bid to the sorted set (for tracking history)
     */
    public static function addBidToHistory(int $auctionId, int $userId, float $amount): void
    {
        $client = self::GetClient();
        $historyKey = "auction:$auctionId:bid_history";
        
        // Store with timestamp as score for ordering
        $client->zadd($historyKey, time(), json_encode([
            'userId' => $userId,
            'amount' => $amount,
            'timestamp' => time()
        ]));
        
        // Keep history for 30 days
        $client->expire($historyKey, 86400 * 30);
    }

    /**
     * Clear all auction data
     */
    public static function clearAuction(int $auctionId): void
    {
        $client = self::GetClient();
        $client->del(
            "auction:$auctionId:bids",
            "auction:$auctionId:bid_history",
            "auction:$auctionId:meta"
        );
    }
}

class AuctionUtils
{
    /**
     * Place a bid with distributed locking for concurrency safety
     * Only the highest bid is accepted
     */
    public static function placeBid(int $auctionId, int $userId, float $amount, float $minimumIncrease = 0): array
    {
        $client = RedisService::GetClient();
        $bidsKey = "auction:$auctionId:bids";
        $lockKey = "auction:$auctionId";
        
        // Try to acquire lock (max 10 second wait)
        $maxAttempts = 20;
        $attempt = 0;
        
        while ($attempt < $maxAttempts) {
            if (RedisService::acquireLock($lockKey, 10)) {
                break;
            }
            $attempt++;
            usleep(50000); // Wait 50ms before retry
        }
        
        if ($attempt >= $maxAttempts) {
            return [
                'success' => false,
                'message' => 'Timeout: Could not process your bid. Please try again.',
                'code' => 'LOCK_TIMEOUT'
            ];
        }
        
        try {
            // Check highest existing bid
            $current = $client->zrevrange($bidsKey, 0, 0, ['withscores' => true]);
            
            if (!empty($current)) {
                $highestAmount = array_values($current)[0];
                $minRequired = $highestAmount + $minimumIncrease;
                
                if ($amount <= $highestAmount) {
                    return [
                        'success' => false,
                        'message' => "Your bid must be higher than the current bid of $highestAmount.",
                        'code' => 'BID_TOO_LOW',
                        'currentHighest' => $highestAmount
                    ];
                }
                
                if ($minimumIncrease > 0 && $amount < $minRequired) {
                    return [
                        'success' => false,
                        'message' => "Your bid must be at least $minRequired (current bid + minimum increase of $minimumIncrease).",
                        'code' => 'BID_BELOW_MINIMUM_INCREASE',
                        'minimumRequired' => $minRequired
                    ];
                }
            } else {
                // No bids yet - check against base price if needed
                if ($amount <= 0) {
                    return [
                        'success' => false,
                        'message' => 'Bid amount must be positive.',
                        'code' => 'INVALID_AMOUNT'
                    ];
                }
            }

            // Add the bid atomically
            $client->zadd($bidsKey, [$userId => $amount]);
            
            // Store in auction metadata the highest bid
            $client->hset("auction:$auctionId:meta", "highest_bid_user", $userId);
            $client->hset("auction:$auctionId:meta", "highest_bid_amount", $amount);
            $client->hset("auction:$auctionId:meta", "last_bid_time", time());
            
            // Expire bid data after 90 days
            $client->expire($bidsKey, 86400 * 90);
            $client->expire("auction:$auctionId:meta", 86400 * 90);
            
            // Add to history
            RedisService::addBidToHistory($auctionId, $userId, $amount);
            
            return [
                'success' => true,
                'message' => "Your bid of $amount has been placed successfully!",
                'code' => 'BID_ACCEPTED',
                'amount' => $amount,
                'userId' => $userId
            ];
        } finally {
            // Always release the lock
            RedisService::releaseLock($lockKey);
        }
    }

    /**
     * Get all bids for an auction (for display purposes)
     */
    public static function getAllBids(int $auctionId): array
    {
        $client = RedisService::GetClient();
        $bidsKey = "auction:$auctionId:bids";
        
        $bids = $client->zrevrange($bidsKey, 0, -1, ['withscores' => true]);
        
        $result = [];
        foreach ($bids as $userId => $amount) {
            $result[] = [
                'userId' => $userId,
                'amount' => $amount
            ];
        }
        
        return $result;
    }

    /**
     * Get bid count for an auction
     */
    public static function getBidCount(int $auctionId): int
    {
        $client = RedisService::GetClient();
        $bidsKey = "auction:$auctionId:bids";
        
        return $client->zcard($bidsKey);
    }
}
