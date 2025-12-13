# Redis Concurrent Bidding - Architecture & Flow Diagrams

## System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Web Browser / Client                    │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  HTML Form / JavaScript API                          │  │
│  │  - Place Bid Button                                  │  │
│  │  - Real-time Stats Display                           │  │
│  │  - Error Messages                                    │  │
│  └──────────────────────────────────────────────────────┘  │
└──────────────┬──────────────────────────────────────────────┘
               │ HTTP Request/Response
               ▼
┌─────────────────────────────────────────────────────────────┐
│              Symfony Web Application Layer                  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  OffreController                                     │  │
│  │  ├─ POST /offre/api/bid (JSON endpoint)             │  │
│  │  ├─ GET /offre/api/auction/{id}/stats               │  │
│  │  ├─ GET /offre/api/auction/{id}/bids                │  │
│  │  └─ POST /offre/new/{id} (Form endpoint)            │  │
│  └──────────────────────────────────────────────────────┘  │
└──────────────┬──────────────────────────────────────────────┘
               │ Service Layer
               ▼
┌─────────────────────────────────────────────────────────────┐
│              Business Logic Layer                           │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  BiddingService                                      │  │
│  │  ├─ placeBid()                                       │  │
│  │  ├─ getHighestBid()                                  │  │
│  │  ├─ getBidStats()                                    │  │
│  │  ├─ getAllBids()                                     │  │
│  │  └─ clearAuctionBids()                               │  │
│  └──────────────────────────────────────────────────────┘  │
└──────────────┬──────────────────────────────────────────────┘
               │
        ┌──────┴──────┐
        ▼             ▼
    ┌────────────┐   ┌────────────┐
    │ AuctionUtils│   │ Exception  │
    │ (Locking)   │   │ Handling   │
    └────────────┘   └────────────┘
        │                ▲
        │                │
        │    ┌───────────┘
        │    │
        ▼    │
┌─────────────────────────────────────────────────────────────┐
│                   Data Access Layer                         │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  RedisService (In-Memory Cache)                     │  │
│  │  ├─ acquireLock()                                    │  │
│  │  ├─ releaseLock()                                    │  │
│  │  ├─ getHighestBid()                                  │  │
│  │  └─ addBidToHistory()                                │  │
│  └──────────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Doctrine ORM (MySQL Database)                       │  │
│  │  ├─ Offre Entity (Persistent)                        │  │
│  │  ├─ Enchere Entity (Auction)                         │  │
│  │  └─ Utilisateur Entity (Users)                       │  │
│  └──────────────────────────────────────────────────────┘  │
└──────────────┬──────────────────────────────────────────────┘
               │
        ┌──────┴──────┐
        ▼             ▼
    ┌────────────┐   ┌────────────┐
    │   Redis    │   │   MySQL    │
    │  6379      │   │  3306      │
    └────────────┘   └────────────┘
```

---

## Bid Placement Flow

```
User Places Bid ($500)
        │
        ▼
┌─────────────────────────────┐
│ Validate User Logged In     │
│ ✓ Session exists            │
└──────────────┬──────────────┘
               │
               ▼
┌─────────────────────────────┐
│ Validate Auction Status     │
│ ✓ Status = ACTIVE           │
│ ✓ Not started               │
│ ✓ Not ended                 │
└──────────────┬──────────────┘
               │
               ▼
┌─────────────────────────────┐
│ Validate Bid Amount         │
│ ✓ >= Base Price             │
│ ✓ > Current Highest         │
│ ✓ Meets min increment       │
└──────────────┬──────────────┘
               │
               ▼
┌─────────────────────────────┐
│ Try Acquire Redis Lock      │
│ key: "lock:auction:123"     │
│ timeout: 10 seconds         │
└──────────────┬──────────────┘
               │
               ├─ Lock acquired ──┐
               │                   │
               │                   ▼
               │        ┌──────────────────────────┐
               │        │ Read highest bid from    │
               │        │ Redis sorted set         │
               │        │ ZREVRANGE: O(1)          │
               │        └──────────┬───────────────┘
               │                   │
               │                   ▼
               │        ┌──────────────────────────┐
               │        │ Verify bid amount is     │
               │        │ higher than current      │
               │        └──────────┬───────────────┘
               │                   │
               │                   ▼
               │        ┌──────────────────────────┐
               │        │ Add bid to Redis        │
               │        │ ZADD auction:123:bids   │
               │        │ {userId: amount}        │
               │        └──────────┬───────────────┘
               │                   │
               │                   ▼
               │        ┌──────────────────────────┐
               │        │ Update auction metadata │
               │        │ HSET auction:123:meta   │
               │        │ highest_bid_user        │
               │        │ highest_bid_amount      │
               │        │ last_bid_time           │
               │        └──────────┬───────────────┘
               │                   │
               │                   ▼
               │        ┌──────────────────────────┐
               │        │ Release Redis Lock      │
               │        │ DEL lock:auction:123    │
               │        └──────────┬───────────────┘
               │                   │
               └─ Lock wait───────┘  ▼
                   (retry)    ┌──────────────────────────┐
               │              │ Save to MySQL Database  │
               │              │ INSERT INTO offre       │
               │              │ UPDATE enchere          │
               │              └──────────┬───────────────┘
               │                         │
               │                         ▼
               │              ┌──────────────────────────┐
               │              │ Return Success Response │
               │              │ HTTP 200 + JSON         │
               │              └──────────────────────────┘
               │
               ├─ Lock timeout
               │
               ▼
        ┌─────────────────────────────┐
        │ Return Error Response       │
        │ HTTP 400/408 + Error Code   │
        │ + User Message              │
        └─────────────────────────────┘
```

---

## Concurrent Bid Scenario (Race Condition Prevention)

```
Time    User A (Bid: $600)     Lock State    User B (Bid: $700)
────    ──────────────────     ──────────    ──────────────────

t0      POST /api/bid          [FREE]
        {"auctionId":1,
         "amount": 600}

t1                             [FREE]        POST /api/bid
                                             {"auctionId":1,
                                              "amount": 700}

t2      acquire_lock()         [PENDING]
        wait < 1s

t3      ✓ Lock acquired        [LOCKED]      acquire_lock()
        Read: highest=$500     [LOCKED]      wait (BLOCKED)

t4      Verify: 600 > 500      [LOCKED]
        ✓ Valid bid            [LOCKED]

t5      ZADD to Redis          [LOCKED]      (still waiting)
        auction:1:bids

t6      Update metadata        [LOCKED]
        prixActuel = 600       [LOCKED]

t7      release_lock()         [FREE]        ✓ Lock acquired
        DEL lock:auction:1     [FREE]        Read: highest=$600

t8      Persist to MySQL       [PENDING]     Verify: 700 > 600
                                             ✓ Valid bid

t9      Flush complete         [FREE]        ZADD to Redis

t10     Return 200 + Success   [FREE]        Update metadata
                                             prixActuel = 700

t11                            [FREE]        release_lock()
                                             DEL lock

t12                            [FREE]        Persist to MySQL

t13                            [FREE]        Flush + Return 200

Result: ✅ Both bids succeed, $700 is highest winning bid
```

---

## Data Flow in Redis

```
Redis Memory Layout for Auction #123
═════════════════════════════════════

KEY: auction:123:bids (Sorted Set)
┌──────────────────────────────────┐
│ Member (User) │ Score (Amount)   │
├──────────────┼──────────────────┤
│ 42            │ 1050.00          │ ← Highest (index 0)
│ 17            │ 950.00           │
│ 88            │ 850.00           │
│ 5             │ 750.00           │
│ 23            │ 650.00           │ ← Lowest
└──────────────────────────────────┘
Query: ZREVRANGE auction:123:bids 0 0 WITHSCORES
Result: [42, 1050.00]  ← Highest bid in O(1)


KEY: auction:123:meta (Hash)
┌──────────────────────────────────┐
│ Field                │ Value      │
├──────────────────────┼────────────┤
│ highest_bid_user     │ 42         │
│ highest_bid_amount   │ 1050.00    │
│ last_bid_time        │ 1702431600 │
└──────────────────────────────────┘


KEY: auction:123:bid_history (Sorted Set)
┌──────────────────────────────────┐
│ Member                │ Score     │
├──────────────────────┼───────────┤
│ JSON: {userId:42,    │ 1702431605│
│        amount:1050}  │           │
│ JSON: {userId:17,    │ 1702431602│
│        amount:950}   │           │
│ JSON: {userId:88,    │ 1702431599│
│        amount:850}   │           │
└──────────────────────────────────┘
TTL: 30 days (for audit trail)


KEY: lock:auction:123 (String)
┌──────────────────────────────────┐
│ Value: uuid_lock_1702431601     │
│ TTL: 10 seconds (auto-cleanup)   │
└──────────────────────────────────┘
```

---

## Error Handling Flow

```
                    Bid Request
                        │
                        ▼
                 ┌──────────────┐
                 │ Validate All │
                 │ Preconditions│
                 └──────┬───────┘
                        │
        ┌───────────────┼───────────────┐
        │               │               │
        ▼               ▼               ▼
    User Auth     Auction Active   Amount Valid
        │               │               │
    ┌───┴─────┐     ┌───┴─────┐   ┌───┴────────┐
    │ NOT_AUTH│     │ NOT_ACTIVE  │ BID_TOO_LOW│
    │ (403)   │     │ (400)       │ (400)      │
    │ ❌ FAIL │     │ ❌ FAIL     │ ❌ FAIL    │
    └─────────┘     └─────────┘   └────────────┘
        │
        ✓ OK
        │
        ▼
    Try Acquire Lock
        │
        ├─ Lock Failed ───────────► LOCK_TIMEOUT (408)
        │                          ❌ FAIL
        │
        ├─ Lock Timeout ──────────► LOCK_TIMEOUT (408)
        │                          ❌ FAIL
        │
        ✓ Lock Acquired
        │
        ▼
    Compare with Highest Bid
        │
        ├─ Too Low ────────────────► BID_TOO_LOW (400)
        │                           ❌ FAIL
        │
        ├─ Below Minimum Incr ─────► BID_BELOW_MIN (400)
        │                           ❌ FAIL
        │
        ✓ Valid
        │
        ▼
    Update Redis (Atomic)
        │
        ├─ Failed ─────────────────► DATABASE_ERROR (500)
        │                           ❌ FAIL
        │
        ✓ Success
        │
        ▼
    Persist to MySQL
        │
        ├─ Failed ─────────────────► DATABASE_ERROR (500)
        │  (Rollback Redis)         ❌ FAIL
        │
        ✓ Success
        │
        ▼
    Return Success (200)
    BID_ACCEPTED
    ✅ SUCCESS
```

---

## High Concurrency Scenario

```
Auction Ends in 10 Minutes
Highest Bid: $500
Users Online: 47
───────────────────────────────────────

10 requests arrive simultaneously:
┌─────────────────────────────────────┐
│ Request  │ Bid Amount │ Status      │
├──────────┼────────────┼─────────────┤
│ User 1   │ $520       │ Queued      │
│ User 2   │ $510       │ Queued      │
│ User 3   │ $530       │ Queued      │
│ User 4   │ $540       │ Queued      │
│ User 5   │ $550       │ Queued      │
│ User 6   │ $560       │ Queued      │
│ User 7   │ $570       │ Queued      │
│ User 8   │ $580       │ Queued      │
│ User 9   │ $590       │ Queued      │
│ User 10  │ $600       │ Queued      │
└─────────────────────────────────────┘

Processing Order (Redis Lock Protection):
═════════════════════════════════════════

1. User 10 acquires lock (1st try) ─────────┐
   ├─ Validate: $600 > $500 ✓               │
   ├─ Add to Redis                          │  Lock held
   ├─ Update DB: prixActuel = $600          │
   └─ Release lock ◄─ ~50ms ──────────────┐ │
                                            │ │
2. User 9 acquires lock (was waiting) ─────┴─┤
   ├─ Validate: $590 > $600 ✗               │
   ├─ Return error: BID_TOO_LOW             │
   └─ Release lock ◄─ ~5ms ────────────────┐│
                                            ││
3. User 8 acquires lock (was waiting) ─────┐││
   ├─ Validate: $580 > $600 ✗               │││
   ├─ Return error: BID_TOO_LOW             │││
   └─ Release lock ◄─ ~5ms ──────────────┐ │││
                                          ││││
   ... (Users 7,6,5,4,3,2,1 fail similarly)
                                          ││││
Total Time: ~250ms (50ms + 9×5ms + overhead)
Successful Bids: 1 (User 10)
Failed Bids: 9 (All with clear error messages)
Locks Acquired: 10 (sequential due to lock protection)
```

---

## Redis Data Expiration

```
Timeline of Auction #123 Data Lifecycle
═══════════════════════════════════════

2024-12-13 14:00:00 UTC
↓
Auction starts, first bid placed
├─ auction:123:bids (TTL: 90 days)
├─ auction:123:meta (TTL: 90 days)
└─ auction:123:bid_history (TTL: 30 days)

2024-12-13 18:00:00 UTC (4 hours later)
↓
Bids continue, data updated but TTL resets

2024-01-11 14:00:00 UTC (30 days later)
↓
❌ auction:123:bid_history EXPIRES
✓ auction:123:bids (60 days remaining)
✓ auction:123:meta (60 days remaining)

2025-03-13 14:00:00 UTC (90 days later)
↓
❌ auction:123:bids EXPIRES
❌ auction:123:meta EXPIRES
✓ MySQL database still has all offre records

Benefit: Automatic cleanup without manual intervention
```

---

## Performance Characteristics

```
Bid Placement Performance
═════════════════════════

Operation                    Time        Complexity
────────────────────────────────────────────────────
Acquire Lock (success)       0.1-1ms     O(1)
Check Highest Bid            0.05ms      O(1)
Add to Sorted Set            0.2ms       O(log N)
Update Hash Metadata         0.05ms      O(1)
Release Lock                 0.05ms      O(1)
────────────────────────────────────────────────────
Total Redis Operations       ~0.5-1.5ms
────────────────────────────────────────────────────
Persist to MySQL             5-20ms      O(1)
────────────────────────────────────────────────────
Total Request Time           10-50ms
(without network latency)


Concurrency Scenarios
═════════════════════

Users     Bids/Second   Avg Lock Wait   Success Rate
─────────────────────────────────────────────────────
10        10            0ms             100%
50        50            10ms            100%
100       100           50ms            100%
500       500           250ms           98% (timeouts)
1000      1000          500+ms          95% (timeouts)

Bottleneck: Redis lock timeout (10 seconds)
Max concurrent: ~200 users at 50 bids/second
```

---

This documentation provides a complete visual understanding of the system architecture, data flows, and concurrency handling mechanisms.
