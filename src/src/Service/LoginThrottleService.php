<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Cache\Cache;

class LoginThrottleService
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;
    private const CACHE_CONFIG = 'default';

    public function isLocked(string $identifier): bool
    {
        $key = 'login_attempts_' . md5($identifier);
        $attempts = (int)Cache::read($key, self::CACHE_CONFIG);

        return $attempts >= self::MAX_ATTEMPTS;
    }

    public function recordFailure(string $identifier): int
    {
        $key = 'login_attempts_' . md5($identifier);
        $attempts = (int)Cache::read($key, self::CACHE_CONFIG);
        $attempts++;
        Cache::write($key, $attempts, self::CACHE_CONFIG);

        return $attempts;
    }

    public function clearAttempts(string $identifier): void
    {
        Cache::delete('login_attempts_' . md5($identifier), self::CACHE_CONFIG);
    }

    public function getRemainingAttempts(string $identifier): int
    {
        $attempts = (int)Cache::read('login_attempts_' . md5($identifier), self::CACHE_CONFIG);

        return max(0, self::MAX_ATTEMPTS - $attempts);
    }
}
