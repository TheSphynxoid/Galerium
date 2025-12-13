<?php

namespace App\Tests\Service;

use App\Entity\Enchere;
use App\Entity\Offre;
use App\Entity\Utilisateur;
use App\Enum\EnchereStatut;
use App\Service\BiddingService;
use App\Service\AuctionUtils;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Predis\Client;

/**
 * Example test suite for concurrent bidding system
 * 
 * Run with: php bin/phpunit tests/Service/ConcurrentBiddingTest.php
 */
class ConcurrentBiddingTest extends TestCase
{
    private BiddingService $biddingService;
    private Enchere $testEnchere;
    private Utilisateur $testUser1;
    private Utilisateur $testUser2;

    public function setUp(): void
    {
        // Initialize test data
        // Note: This is a simplified example. In real tests, use a test database fixture
    }

    /**
     * Test that only the highest bid is accepted
     */
    public function testHighestBidWins(): void
    {
        $auctionId = 1;
        
        // Simulate two concurrent bids
        $result1 = AuctionUtils::placeBid($auctionId, 1, 100.00);
        $result2 = AuctionUtils::placeBid($auctionId, 2, 150.00);
        
        // First bid should succeed
        $this->assertTrue($result1['success']);
        $this->assertEquals('BID_ACCEPTED', $result1['code']);
        
        // Second (higher) bid should succeed
        $this->assertTrue($result2['success']);
        $this->assertEquals('BID_ACCEPTED', $result2['code']);
        
        // Check highest bid is the second one
        $highest = AuctionUtils::getHighestBid($auctionId);
        $this->assertEquals(2, $highest['userId']);
        $this->assertEquals(150.00, $highest['amount']);
        
        // Cleanup
        AuctionUtils::clearAuction($auctionId);
    }

    /**
     * Test that lower bids are rejected
     */
    public function testLowerBidRejected(): void
    {
        $auctionId = 2;
        
        // Place first bid
        $result1 = AuctionUtils::placeBid($auctionId, 1, 200.00);
        $this->assertTrue($result1['success']);
        
        // Try to place lower bid
        $result2 = AuctionUtils::placeBid($auctionId, 2, 150.00);
        $this->assertFalse($result2['success']);
        $this->assertEquals('BID_TOO_LOW', $result2['code']);
        $this->assertEquals(200.00, $result2['currentHighest']);
        
        // Cleanup
        AuctionUtils::clearAuction($auctionId);
    }

    /**
     * Test minimum bid increment
     */
    public function testMinimumBidIncrement(): void
    {
        $auctionId = 3;
        $minimumIncrease = 10.00;
        
        // Place first bid
        $result1 = AuctionUtils::placeBid($auctionId, 1, 100.00, $minimumIncrease);
        $this->assertTrue($result1['success']);
        
        // Try bid that's only slightly higher
        $result2 = AuctionUtils::placeBid($auctionId, 2, 105.00, $minimumIncrease);
        $this->assertFalse($result2['success']);
        $this->assertEquals('BID_BELOW_MINIMUM_INCREASE', $result2['code']);
        $this->assertEquals(110.00, $result2['minimumRequired']);
        
        // Place valid bid with minimum increment
        $result3 = AuctionUtils::placeBid($auctionId, 2, 110.00, $minimumIncrease);
        $this->assertTrue($result3['success']);
        
        // Cleanup
        AuctionUtils::clearAuction($auctionId);
    }

    /**
     * Test bid history tracking
     */
    public function testBidHistoryTracking(): void
    {
        $auctionId = 4;
        
        // Place multiple bids
        AuctionUtils::placeBid($auctionId, 1, 100.00);
        AuctionUtils::placeBid($auctionId, 2, 150.00);
        AuctionUtils::placeBid($auctionId, 3, 200.00);
        
        // Get all bids
        $allBids = AuctionUtils::getAllBids($auctionId);
        $this->assertCount(3, $allBids);
        
        // Verify highest is first (bids are in descending order)
        $this->assertEquals(3, $allBids[0]['userId']);
        $this->assertEquals(200.00, $allBids[0]['amount']);
        
        // Cleanup
        AuctionUtils::clearAuction($auctionId);
    }

    /**
     * Test bid count
     */
    public function testBidCounting(): void
    {
        $auctionId = 5;
        
        $this->assertEquals(0, AuctionUtils::getBidCount($auctionId));
        
        AuctionUtils::placeBid($auctionId, 1, 100.00);
        $this->assertEquals(1, AuctionUtils::getBidCount($auctionId));
        
        AuctionUtils::placeBid($auctionId, 2, 150.00);
        $this->assertEquals(2, AuctionUtils::getBidCount($auctionId));
        
        // Cleanup
        AuctionUtils::clearAuction($auctionId);
    }

    /**
     * Test concurrent bid scenario (simulated)
     * In real scenario, this would be done with multiple threads/requests
     */
    public function testConcurrentBidScenario(): void
    {
        $auctionId = 6;
        
        // Simulate 10 users placing bids concurrently
        $results = [];
        for ($userId = 1; $userId <= 10; $userId++) {
            $bidAmount = 100.00 + ($userId * 10);
            $result = AuctionUtils::placeBid($auctionId, $userId, $bidAmount);
            $results[$userId] = $result;
        }
        
        // All bids should succeed
        foreach ($results as $userId => $result) {
            $this->assertTrue($result['success'], "User $userId bid should succeed");
        }
        
        // Check that we have 10 bids recorded
        $this->assertEquals(10, AuctionUtils::getBidCount($auctionId));
        
        // Check highest bid is from user 10 with $200
        $highest = AuctionUtils::getHighestBid($auctionId);
        $this->assertEquals(10, $highest['userId']);
        $this->assertEquals(200.00, $highest['amount']);
        
        // Cleanup
        AuctionUtils::clearAuction($auctionId);
    }

    /**
     * Test invalid bid amounts
     */
    public function testInvalidBidAmounts(): void
    {
        $auctionId = 7;
        
        // Negative amount
        $result1 = AuctionUtils::placeBid($auctionId, 1, -100.00);
        $this->assertFalse($result1['success']);
        
        // Zero amount
        $result2 = AuctionUtils::placeBid($auctionId, 1, 0);
        $this->assertFalse($result2['success']);
        
        // Valid positive bid should work
        $result3 = AuctionUtils::placeBid($auctionId, 1, 50.00);
        $this->assertTrue($result3['success']);
        
        // Cleanup
        AuctionUtils::clearAuction($auctionId);
    }

    /**
     * Test auction cleanup
     */
    public function testAuctionCleanup(): void
    {
        $auctionId = 8;
        
        // Add bids
        AuctionUtils::placeBid($auctionId, 1, 100.00);
        AuctionUtils::placeBid($auctionId, 2, 150.00);
        $this->assertEquals(2, AuctionUtils::getBidCount($auctionId));
        
        // Clear auction
        AuctionUtils::clearAuction($auctionId);
        
        // Verify all data is cleared
        $this->assertEquals(0, AuctionUtils::getBidCount($auctionId));
        $this->assertNull(AuctionUtils::getHighestBid($auctionId));
    }

    /**
     * Test stress scenario - many rapid bids
     */
    public function testStressScenario(): void
    {
        $auctionId = 9;
        $successCount = 0;
        
        // Rapid sequential bids (not true concurrent, but tests lock handling)
        for ($i = 0; $i < 100; $i++) {
            $result = AuctionUtils::placeBid($auctionId, ($i % 10) + 1, 100.00 + ($i * 0.10));
            if ($result['success']) {
                $successCount++;
            }
        }
        
        // All bids should succeed since they're all higher
        $this->assertEquals(100, $successCount);
        
        // Check we have 100 bids
        $this->assertEquals(100, AuctionUtils::getBidCount($auctionId));
        
        // Cleanup
        AuctionUtils::clearAuction($auctionId);
    }
}
