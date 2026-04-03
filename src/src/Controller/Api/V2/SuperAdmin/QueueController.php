<?php
declare(strict_types=1);

namespace App\Controller\Api\V2\SuperAdmin;

use App\Controller\Api\V2\AppController;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\Queue\QueueManager;
use Redis;

/**
 * Super Admin QueueController
 *
 * Queue monitoring dashboard: worker status, queue depths, failed jobs, scheduler heartbeat.
 */
class QueueController extends AppController
{
    /**
     * Redis DB used by enqueue/redis transport (configured in Queue config).
     */
    private const QUEUE_REDIS_DB = 5;

    /**
     * Redis DB used by the scheduler heartbeat (same as lock service).
     */
    private const HEARTBEAT_REDIS_DB = 6;

    /**
     * Scheduler heartbeat Redis key.
     */
    private const SCHEDULER_HEARTBEAT_KEY = 'keepup:scheduler:last_tick';

    /**
     * GET /api/v2/super-admin/queue
     *
     * Overview of queue system: depths, scheduler, workers, failed jobs, stats.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        if (!$this->isSuperAdmin) {
            $this->error('Super admin access required', 403);

            return;
        }

        // Queue depths from Redis
        $queues = $this->getQueueDepths();

        // Scheduler status from heartbeat key
        $scheduler = $this->getSchedulerStatus();

        // Failed jobs from database
        $failedJobsData = $this->getFailedJobsSummary();

        // Stats: jobs processed/failed in last 24h
        $stats = $this->getStats();

        $this->success([
            'queues' => $queues,
            'scheduler' => $scheduler,
            'workers' => [
                'active' => $this->getActiveWorkerCount(),
                'last_heartbeat' => $scheduler['last_run'],
            ],
            'failed_jobs' => $failedJobsData,
            'stats' => $stats,
        ]);
    }

    /**
     * GET /api/v2/super-admin/queue/failed-jobs
     *
     * Paginated list of failed jobs.
     *
     * @return void
     */
    public function failedJobs(): void
    {
        $this->request->allowMethod(['get']);

        if (!$this->isSuperAdmin) {
            $this->error('Super admin access required', 403);

            return;
        }

        try {
            $failedJobsTable = $this->fetchTable('Cake/Queue.FailedJobs');
            $page = max(1, (int)$this->request->getQuery('page', '1'));
            $limit = min(100, max(1, (int)$this->request->getQuery('limit', '25')));
            $offset = ($page - 1) * $limit;

            $total = $failedJobsTable->find()->count();

            $jobs = $failedJobsTable->find()
                ->orderByDesc('created')
                ->limit($limit)
                ->offset($offset)
                ->all()
                ->map(function ($job) {
                    return [
                        'id' => $job->id,
                        'class' => $job->class,
                        'method' => $job->method,
                        'queue' => $job->queue,
                        'config' => $job->config,
                        'exception' => $job->exception,
                        'created' => $job->created ? $job->created->toIso8601String() : null,
                    ];
                })
                ->toArray();

            $this->success([
                'failed_jobs' => array_values($jobs),
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => (int)ceil($total / $limit),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("QueueController::failedJobs error: {$e->getMessage()}");
            $this->success([
                'failed_jobs' => [],
                'pagination' => ['page' => 1, 'limit' => 25, 'total' => 0, 'pages' => 0],
            ]);
        }
    }

    /**
     * POST /api/v2/super-admin/queue/retry/{id}
     *
     * Re-queue a failed job by reading it from the failed_jobs table,
     * pushing it back to the queue, and deleting it from failed_jobs.
     *
     * @param string $id The failed job ID
     * @return void
     */
    public function retryJob(string $id): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->isSuperAdmin) {
            $this->error('Super admin access required', 403);

            return;
        }

        try {
            $failedJobsTable = $this->fetchTable('Cake/Queue.FailedJobs');
            $job = $failedJobsTable->get((int)$id);

            // Re-push to the queue
            $data = json_decode($job->data, true) ?: [];
            $config = $job->config ?: 'default';

            QueueManager::push($job->class, $data, [
                'config' => $config,
                'queue' => $job->queue ?: null,
            ]);

            // Delete from failed_jobs
            $failedJobsTable->delete($job);

            $this->success(['message' => "Job #{$id} re-queued successfully"]);
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            $this->error("Failed job #{$id} not found", 404);
        } catch (\Exception $e) {
            Log::error("QueueController::retryJob error for #{$id}: {$e->getMessage()}");
            $this->error("Failed to retry job: {$e->getMessage()}", 500);
        }
    }

    /**
     * DELETE /api/v2/super-admin/queue/failed-jobs
     *
     * Purge all failed jobs.
     *
     * @return void
     */
    public function purgeFailedJobs(): void
    {
        $this->request->allowMethod(['delete']);

        if (!$this->isSuperAdmin) {
            $this->error('Super admin access required', 403);

            return;
        }

        try {
            $failedJobsTable = $this->fetchTable('Cake/Queue.FailedJobs');
            $count = $failedJobsTable->find()->count();
            $failedJobsTable->deleteAll([]);

            $this->success(['message' => "Purged {$count} failed job(s)"]);
        } catch (\Exception $e) {
            Log::error("QueueController::purgeFailedJobs error: {$e->getMessage()}");
            $this->error("Failed to purge jobs: {$e->getMessage()}", 500);
        }
    }

    /**
     * Get queue depths from Redis using LLEN.
     *
     * @return array<string, array{depth: int, name: string}>
     */
    private function getQueueDepths(): array
    {
        $queues = [
            'default' => ['depth' => 0, 'name' => 'default'],
            'notifications' => ['depth' => 0, 'name' => 'notifications'],
        ];

        try {
            $redis = $this->connectRedis(self::QUEUE_REDIS_DB);
            if ($redis) {
                foreach ($queues as $name => &$info) {
                    $depth = $redis->lLen($name);
                    $info['depth'] = $depth !== false ? (int)$depth : 0;
                }
                unset($info);
                $redis->close();
            }
        } catch (\Exception $e) {
            Log::error("QueueController: Failed to get queue depths: {$e->getMessage()}");
        }

        return $queues;
    }

    /**
     * Get scheduler status from Redis heartbeat key.
     *
     * @return array{last_run: string|null, status: string}
     */
    private function getSchedulerStatus(): array
    {
        $result = [
            'last_run' => null,
            'status' => 'stopped',
        ];

        try {
            $redis = $this->connectRedis(self::HEARTBEAT_REDIS_DB);
            if ($redis) {
                $lastTick = $redis->get(self::SCHEDULER_HEARTBEAT_KEY);
                if ($lastTick !== false && $lastTick !== null) {
                    $result['last_run'] = $lastTick;
                    $result['status'] = 'running';
                }
                $redis->close();
            }
        } catch (\Exception $e) {
            Log::error("QueueController: Failed to get scheduler status: {$e->getMessage()}");
        }

        return $result;
    }

    /**
     * Get failed jobs summary: total count and recent entries.
     *
     * @return array{total: int, recent: array}
     */
    private function getFailedJobsSummary(): array
    {
        try {
            $failedJobsTable = $this->fetchTable('Cake/Queue.FailedJobs');
            $total = $failedJobsTable->find()->count();

            $recent = $failedJobsTable->find()
                ->orderByDesc('created')
                ->limit(5)
                ->all()
                ->map(function ($job) {
                    return [
                        'id' => $job->id,
                        'class' => $job->class,
                        'queue' => $job->queue,
                        'exception' => $job->exception ? mb_substr($job->exception, 0, 200) : null,
                        'created' => $job->created ? $job->created->toIso8601String() : null,
                    ];
                })
                ->toArray();

            return [
                'total' => $total,
                'recent' => array_values($recent),
            ];
        } catch (\Exception $e) {
            Log::error("QueueController: Failed to get failed jobs summary: {$e->getMessage()}");

            return ['total' => 0, 'recent' => []];
        }
    }

    /**
     * Get queue processing stats for the last 24 hours.
     *
     * Uses Redis keys if available, falls back to counting failed jobs.
     *
     * @return array{jobs_processed_24h: int, jobs_failed_24h: int}
     */
    private function getStats(): array
    {
        $stats = [
            'jobs_processed_24h' => 0,
            'jobs_failed_24h' => 0,
        ];

        try {
            // Count failed jobs in last 24h
            $failedJobsTable = $this->fetchTable('Cake/Queue.FailedJobs');
            $stats['jobs_failed_24h'] = $failedJobsTable->find()
                ->where(['created >=' => DateTime::now()->subHours(24)->format('Y-m-d H:i:s')])
                ->count();

            // Estimate processed jobs from monitor checks in last 24h
            $checksTable = $this->fetchTable('MonitorChecks');
            $stats['jobs_processed_24h'] = $checksTable->find()
                ->where(['MonitorChecks.checked_at >=' => DateTime::now()->subHours(24)->format('Y-m-d H:i:s')])
                ->count();
        } catch (\Exception $e) {
            Log::error("QueueController: Failed to get stats: {$e->getMessage()}");
        }

        return $stats;
    }

    /**
     * Estimate the number of active queue workers.
     *
     * Checks Redis for active consumer keys. Falls back to 0 if unavailable.
     *
     * @return int
     */
    private function getActiveWorkerCount(): int
    {
        try {
            $redis = $this->connectRedis(self::QUEUE_REDIS_DB);
            if ($redis) {
                // enqueue/redis stores consumer reservation keys
                $keys = $redis->keys('*:reserved');
                $count = is_array($keys) ? count($keys) : 0;
                $redis->close();

                return $count;
            }
        } catch (\Exception $e) {
            Log::error("QueueController: Failed to get worker count: {$e->getMessage()}");
        }

        return 0;
    }

    /**
     * Create a Redis connection to the specified database.
     *
     * @param int $db Redis database number
     * @return \Redis|null
     */
    private function connectRedis(int $db): ?Redis
    {
        try {
            $redis = new Redis();
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

            $redis->select($db);

            return $redis;
        } catch (\RedisException $e) {
            Log::error("QueueController: Redis connection failed (db={$db}): {$e->getMessage()}");

            return null;
        }
    }
}
