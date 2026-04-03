<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Log\Log;
use Redis;
use RuntimeException;

/**
 * Redis Lock Service
 *
 * Provides distributed locking via Redis SET NX EX.
 * Used to prevent concurrent processing of the same monitor
 * or incident across multiple queue workers.
 */
class RedisLockService
{
    /**
     * Key prefix for all locks.
     */
    private const KEY_PREFIX = 'keepup:lock:';

    /**
     * Redis connection instance.
     *
     * @var \Redis
     */
    protected Redis $redis;

    /**
     * Constructor.
     *
     * Connects to Redis using the same environment variables
     * as the rest of the application (REDIS_URL).
     */
    public function __construct()
    {
        $this->redis = new Redis();

        $redisUrl = getenv('REDIS_URL') ?: '';
        $host = '127.0.0.1';
        $port = 6379;
        $password = '';

        if ($redisUrl) {
            $parsed = parse_url($redisUrl);
            $host = $parsed['host'] ?? '127.0.0.1';
            $port = $parsed['port'] ?? 6379;
            $password = $parsed['pass'] ?? '';
        }

        try {
            $this->redis->connect($host, $port, 2.0);

            if ($password !== '') {
                $this->redis->auth($password);
            }

            // Use DB 6 for locks (separate from queue on DB 5)
            $this->redis->select(6);
        } catch (\RedisException $e) {
            Log::error("RedisLockService: Failed to connect to Redis: {$e->getMessage()}");
            throw new RuntimeException("Cannot connect to Redis for locking: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Acquire a distributed lock.
     *
     * Uses SET with NX (only set if not exists) and EX (expiry) flags
     * to atomically acquire the lock.
     *
     * @param string $key Lock key (will be prefixed with keepup:lock:)
     * @param int $ttlSeconds Lock expiry in seconds
     * @return bool True if lock was acquired, false if already held
     */
    public function acquire(string $key, int $ttlSeconds): bool
    {
        try {
            $fullKey = self::KEY_PREFIX . $key;

            // SET key value NX EX ttl — atomic acquire with auto-expiry
            $result = $this->redis->set($fullKey, (string)getmypid(), ['NX', 'EX' => $ttlSeconds]);

            return $result === true;
        } catch (\RedisException $e) {
            Log::error("RedisLockService: Failed to acquire lock '{$key}': {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Release a distributed lock.
     *
     * @param string $key Lock key (will be prefixed with keepup:lock:)
     * @return void
     */
    public function release(string $key): void
    {
        try {
            $fullKey = self::KEY_PREFIX . $key;
            $this->redis->del($fullKey);
        } catch (\RedisException $e) {
            Log::error("RedisLockService: Failed to release lock '{$key}': {$e->getMessage()}");
        }
    }
}
