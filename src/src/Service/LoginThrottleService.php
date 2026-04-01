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
        $data = Cache::read($key, self::CACHE_CONFIG);

        if (!$data || !is_array($data)) {
            return false;
        }

        $attempts = (int)($data['count'] ?? 0);
        $firstFailure = (int)($data['first_failure'] ?? 0);

        // If lockout window has expired, clear and return false
        if ($firstFailure > 0 && (time() - $firstFailure) > (self::LOCKOUT_MINUTES * 60)) {
            Cache::delete($key, self::CACHE_CONFIG);

            return false;
        }

        return $attempts >= self::MAX_ATTEMPTS;
    }

    public function recordFailure(string $identifier): int
    {
        $key = 'login_attempts_' . md5($identifier);
        $data = Cache::read($key, self::CACHE_CONFIG);

        if (!$data || !is_array($data)) {
            $data = ['count' => 0, 'first_failure' => time()];
        }

        // If lockout window has expired, reset
        $firstFailure = (int)($data['first_failure'] ?? time());
        if ((time() - $firstFailure) > (self::LOCKOUT_MINUTES * 60)) {
            $data = ['count' => 0, 'first_failure' => time()];
        }

        $data['count'] = ((int)($data['count'] ?? 0)) + 1;

        // Write with explicit TTL of lockout duration
        Cache::write($key, $data, self::CACHE_CONFIG);

        return $data['count'];
    }

    public function clearAttempts(string $identifier): void
    {
        Cache::delete('login_attempts_' . md5($identifier), self::CACHE_CONFIG);
    }

    public function getRemainingAttempts(string $identifier): int
    {
        $key = 'login_attempts_' . md5($identifier);
        $data = Cache::read($key, self::CACHE_CONFIG);

        if (!$data || !is_array($data)) {
            return self::MAX_ATTEMPTS;
        }

        // If lockout window has expired, return full attempts
        $firstFailure = (int)($data['first_failure'] ?? 0);
        if ($firstFailure > 0 && (time() - $firstFailure) > (self::LOCKOUT_MINUTES * 60)) {
            return self::MAX_ATTEMPTS;
        }

        $attempts = (int)($data['count'] ?? 0);

        return max(0, self::MAX_ATTEMPTS - $attempts);
    }
}
