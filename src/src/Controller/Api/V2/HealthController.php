<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

use Cake\Log\Log;

/**
 * HealthController
 *
 * Provides health check endpoints for load balancers, Docker health checks,
 * and monitoring systems. Does NOT require authentication.
 *
 * GET /api/v2/health       — comprehensive health check with dependency status
 * GET /api/v2/health/ping  — lightweight liveness probe
 */
class HealthController extends AppController
{
    /**
     * Application boot time, used to compute uptime.
     *
     * @var float
     */
    private static float $bootTime = 0;

    /**
     * GET /api/v2/health/ping
     *
     * Lightweight liveness check — returns immediately with no dependency checks.
     * Suitable for Docker HEALTHCHECK and load balancer probes.
     *
     * @return void
     */
    public function ping(): void
    {
        $this->request->allowMethod(['get']);

        $this->response = $this->response->withStatus(200);
        $this->set('response', ['status' => 'ok']);
        $this->viewBuilder()->setOption('serialize', 'response');
    }

    /**
     * GET /api/v2/health
     *
     * Comprehensive health check that verifies all system dependencies:
     * database, Redis, queue depths, scheduler heartbeat, and disk space.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $checks = [];
        $overallStatus = 'healthy';

        // Database check
        $checks['database'] = $this->checkDatabase();

        // Redis check
        $checks['redis'] = $this->checkRedis();

        // Queue checks (require Redis)
        $redis = $this->getRedisConnection();
        $checks['queue_default'] = $this->checkQueue($redis, 'default');
        $checks['queue_notifications'] = $this->checkQueue($redis, 'notifications');

        // Scheduler check
        $checks['scheduler'] = $this->checkScheduler($redis);

        // Disk check
        $checks['disk'] = $this->checkDisk();

        // Determine overall status
        foreach ($checks as $check) {
            if ($check['status'] === 'down') {
                $overallStatus = 'unhealthy';
                break;
            }
            if ($check['status'] === 'warning') {
                $overallStatus = 'degraded';
            }
        }

        // Critical services: if database or redis are down, always unhealthy
        if ($checks['database']['status'] === 'down' || $checks['redis']['status'] === 'down') {
            $overallStatus = 'unhealthy';
        }

        $uptime = $this->getUptime();

        $body = [
            'status' => $overallStatus,
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'version' => '1.0.0',
            'uptime' => $uptime,
            'checks' => $checks,
        ];

        $httpStatus = $overallStatus === 'unhealthy' ? 503 : 200;
        $this->response = $this->response->withStatus($httpStatus);
        $this->set('response', $body);
        $this->viewBuilder()->setOption('serialize', 'response');
    }

    /**
     * Check database connectivity with a simple SELECT 1 query.
     *
     * @return array{status: string, latency_ms: int|null, error?: string}
     */
    private function checkDatabase(): array
    {
        try {
            $start = hrtime(true);
            $connection = \Cake\Datasource\ConnectionManager::get('default');
            $connection->execute('SELECT 1');
            $latency = (int)round((hrtime(true) - $start) / 1_000_000);

            return ['status' => 'up', 'latency_ms' => $latency];
        } catch (\Throwable $e) {
            Log::error("Health check: database down — {$e->getMessage()}");

            return ['status' => 'down', 'latency_ms' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check Redis connectivity with PING.
     *
     * @return array{status: string, latency_ms: int|null, error?: string}
     */
    private function checkRedis(): array
    {
        try {
            $redis = $this->getRedisConnection();
            if ($redis === null) {
                return ['status' => 'down', 'latency_ms' => null, 'error' => 'Could not connect to Redis'];
            }

            $start = hrtime(true);
            $redis->ping();
            $latency = (int)round((hrtime(true) - $start) / 1_000_000);

            return ['status' => 'up', 'latency_ms' => $latency];
        } catch (\Throwable $e) {
            Log::error("Health check: redis down — {$e->getMessage()}");

            return ['status' => 'down', 'latency_ms' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check queue depth via Redis LLEN.
     *
     * @param \Redis|null $redis Redis connection.
     * @param string $queueName Queue name (e.g. 'default', 'notifications').
     * @return array{status: string, depth: int|null, error?: string}
     */
    private function checkQueue(?\Redis $redis, string $queueName): array
    {
        if ($redis === null) {
            return ['status' => 'down', 'depth' => null, 'error' => 'Redis unavailable'];
        }

        try {
            // CakePHP Queue uses Redis lists; the key format is typically queue:{name}
            $depth = (int)$redis->lLen("queue:{$queueName}");
            $status = $depth > 100 ? 'warning' : 'up';

            return ['status' => $status, 'depth' => $depth];
        } catch (\Throwable $e) {
            Log::error("Health check: queue {$queueName} check failed — {$e->getMessage()}");

            return ['status' => 'down', 'depth' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check the scheduler heartbeat via a Redis key.
     *
     * The scheduler daemon sets `keepup:scheduler:last_tick` on each tick.
     * If the key is missing or older than 90 seconds, the scheduler is considered stopped.
     *
     * @param \Redis|null $redis Redis connection.
     * @return array{status: string, last_tick: string|null, error?: string}
     */
    private function checkScheduler(?\Redis $redis): array
    {
        if ($redis === null) {
            return ['status' => 'down', 'last_tick' => null, 'error' => 'Redis unavailable'];
        }

        try {
            $lastTick = $redis->get('keepup:scheduler:last_tick');

            if ($lastTick === false || $lastTick === '') {
                return ['status' => 'warning', 'last_tick' => 'never'];
            }

            $elapsed = time() - (int)$lastTick;
            if ($elapsed < 0) {
                $elapsed = 0;
            }

            $status = $elapsed <= 90 ? 'up' : 'stopped';
            $lastTickHuman = $elapsed . 's ago';

            return ['status' => $status, 'last_tick' => $lastTickHuman];
        } catch (\Throwable $e) {
            Log::error("Health check: scheduler check failed — {$e->getMessage()}");

            return ['status' => 'down', 'last_tick' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check available disk space.
     *
     * @return array{status: string, free_gb: float|null, error?: string}
     */
    private function checkDisk(): array
    {
        try {
            $freeBytes = disk_free_space('/');
            if ($freeBytes === false) {
                return ['status' => 'warning', 'free_gb' => null, 'error' => 'Could not determine disk space'];
            }

            $freeGb = round($freeBytes / (1024 * 1024 * 1024), 1);
            $status = $freeGb < 1.0 ? 'warning' : 'up';

            return ['status' => $status, 'free_gb' => $freeGb];
        } catch (\Throwable $e) {
            Log::error("Health check: disk check failed — {$e->getMessage()}");

            return ['status' => 'warning', 'free_gb' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get a Redis connection using the same pattern as RedisLockService.
     *
     * @return \Redis|null
     */
    private function getRedisConnection(): ?\Redis
    {
        static $redis = null;

        if ($redis !== null) {
            return $redis;
        }

        try {
            $redis = new \Redis();

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

            $redis->connect($host, $port, 2.0);

            if ($password !== '') {
                $redis->auth($password);
            }

            return $redis;
        } catch (\Throwable $e) {
            Log::error("Health check: Redis connection failed — {$e->getMessage()}");
            $redis = null;

            return null;
        }
    }

    /**
     * Compute human-readable uptime string.
     *
     * Uses the process start time from /proc/self/stat if available,
     * otherwise falls back to the request time.
     *
     * @return string e.g. "3d 5h 22m"
     */
    private function getUptime(): string
    {
        try {
            // Try to get process uptime from /proc
            if (is_readable('/proc/uptime')) {
                $uptime = (float)explode(' ', file_get_contents('/proc/uptime'))[0];
                $seconds = (int)$uptime;
            } else {
                // Fallback: use server REQUEST_TIME
                $seconds = time() - (int)($_SERVER['REQUEST_TIME'] ?? time());
            }

            $days = (int)floor($seconds / 86400);
            $hours = (int)floor(($seconds % 86400) / 3600);
            $minutes = (int)floor(($seconds % 3600) / 60);

            $parts = [];
            if ($days > 0) {
                $parts[] = "{$days}d";
            }
            if ($hours > 0 || $days > 0) {
                $parts[] = "{$hours}h";
            }
            $parts[] = "{$minutes}m";

            return implode(' ', $parts);
        } catch (\Throwable $e) {
            return 'unknown';
        }
    }
}
