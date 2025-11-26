<?php

namespace App\Service;

class RedisService
{
    public function __construct(
        private \Predis\Client $redis
    ) {}

    public function GetClient()
    {
        return $this->redis;
    }

    public function writeTest()
    {
        $this->redis->set('foo', 'bar');
    }

    public function readTest()
    {
        return $this->redis->get('foo');
    }
}
