<?php
declare(strict_types=1);

namespace App\Job;

use App\Job\NotificationJob;
use App\Service\Alert\AlertService;
use App\Service\Alert\DiscordAlertChannel;
use App\Service\Alert\EmailAlertChannel;
use App\Service\Alert\OpsGenieAlertChannel;
use App\Service\Alert\PagerDutyAlertChannel;
use App\Service\Alert\VoiceCallAlertChannel;
use App\Service\Alert\SlackAlertChannel;
use App\Service\Alert\SmsAlertChannel;
use App\Service\Alert\TelegramAlertChannel;
use App\Service\Alert\WebhookAlertChannel;
use App\Service\Alert\WhatsAppAlertChannel;
use App\Service\Check\CheckService;
use App\Service\Check\HeartbeatChecker;
use App\Service\Check\HttpChecker;
use App\Service\Check\KeywordChecker;
use App\Service\Check\PingChecker;
use App\Service\Check\PortChecker;
use App\Service\Check\RestApiChecker;
use App\Service\Check\SslCertChecker;
use App\Service\IncidentService;
use App\Service\MonitorCacheService;
use App\Service\RedisLockService;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Queue\Job\JobInterface;
use Cake\Queue\Job\Message;
use Cake\Queue\QueueManager;
use Interop\Queue\Processor;

/**
 * Monitor Check Job
 *
 * Executes a health check for a single monitor via the queue.
 * Acquires a Redis lock per monitor to prevent concurrent checks
 * of the same monitor across multiple workers.
 */
class MonitorCheckJob implements JobInterface
{
    use LocatorAwareTrait;

    /**
     * Whether only one instance of this job should exist per unique key.
     *
     * @var bool
     */
    public static bool $shouldBeUnique = true;

    /**
     * Maximum number of retry attempts.
     *
     * @var int|null
     */
    public static ?int $maxAttempts = 2;

    /**
     * Execute the monitor check job.
     *
     * @param \Cake\Queue\Job\Message $message The queue message
     * @return string Processor result (ACK or REJECT)
     */
    public function execute(Message $message): string
    {
        $data = $message->getArgument('data') ?? [];
        $monitorId = $data['monitor_id'] ?? null;
        $regionId = $data['region_id'] ?? null;

        if ($monitorId === null) {
            Log::error('MonitorCheckJob: Missing monitor_id in message data');

            return Processor::REJECT;
        }

        // Acquire a lock for this monitor to prevent concurrent checks
        try {
            $lockService = new RedisLockService();
        } catch (\RuntimeException $e) {
            Log::error("MonitorCheckJob: Cannot create lock service: {$e->getMessage()}");

            return Processor::REJECT;
        }

        $lockKey = "monitor_check:{$monitorId}";
        if (!$lockService->acquire($lockKey, 120)) {
            // Another worker is already checking this monitor — acknowledge and move on
            Log::debug("MonitorCheckJob: Lock not acquired for monitor {$monitorId}, skipping");

            return Processor::ACK;
        }

        try {
            // Load the monitor
            $monitorsTable = $this->fetchTable('Monitors');
            $monitor = $monitorsTable->find()
                ->where(['id' => $monitorId, 'active' => true])
                ->first();

            if ($monitor === null) {
                Log::warning("MonitorCheckJob: Monitor {$monitorId} not found or inactive");

                return Processor::ACK;
            }

            // Initialize check service with all checkers
            $checkService = new CheckService();
            $checkService->registerChecker(new HttpChecker());
            $checkService->registerChecker(new PingChecker());
            $checkService->registerChecker(new PortChecker());
            $checkService->registerChecker(new HeartbeatChecker());
            $checkService->registerChecker(new KeywordChecker());
            $checkService->registerChecker(new SslCertChecker());
            $checkService->registerChecker(new RestApiChecker());

            // Execute the check
            $checkResult = $checkService->executeCheck($monitor);

            // Save check result
            $this->saveCheckResult($monitor, $checkResult, $regionId);

            // Invalidate cache
            $cacheService = new MonitorCacheService();
            $cacheService->invalidateMonitor($monitor->id);
            if ($monitor->organization_id) {
                $cacheService->invalidateDashboard((int)$monitor->organization_id);
            }

            // Track old status before updating
            $oldStatus = $monitor->getOriginal('status') ?? $monitor->status;

            // Update monitor status
            $this->updateMonitorStatus($monitor, $checkResult);

            // Handle incidents and alerts
            $this->handleIncidentsAndAlerts($monitor, $checkResult, $oldStatus);

            Log::debug("MonitorCheckJob: Completed check for monitor {$monitorId}, status: {$checkResult['status']}");

            return Processor::ACK;
        } catch (\Exception $e) {
            Log::error("MonitorCheckJob: Failed for monitor {$monitorId}: {$e->getMessage()}");

            return Processor::REJECT;
        } finally {
            $lockService->release($lockKey);
        }
    }

    /**
     * Save a single check result to the database.
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor entity
     * @param array $checkResult The check result data
     * @param int|null $regionId Optional check region ID
     * @return void
     */
    protected function saveCheckResult($monitor, array $checkResult, ?int $regionId): void
    {
        $checksTable = $this->fetchTable('MonitorChecks');

        $status = match ($checkResult['status']) {
            'up' => 'success',
            'degraded' => 'success',
            'down' => 'failure',
            default => 'error',
        };

        $entity = $checksTable->newEntity([
            'organization_id' => $monitor->organization_id,
            'monitor_id' => $monitor->id,
            'region_id' => $regionId,
            'status' => $status,
            'response_time' => $checkResult['response_time'] ?? null,
            'status_code' => $checkResult['status_code'] ?? null,
            'error_message' => $checkResult['error_message'] ?? null,
            'details' => json_encode($checkResult['metadata'] ?? []),
            'checked_at' => $checkResult['checked_at'] ?? (new DateTime())->format('Y-m-d H:i:s'),
            'created' => (new DateTime())->format('Y-m-d H:i:s'),
        ]);

        if (!$checksTable->save($entity)) {
            Log::error("MonitorCheckJob: Failed to save check result for monitor {$monitor->id}", [
                'errors' => $entity->getErrors(),
            ]);
        }

        // Save error details to companion table if present
        $errorMessage = $checkResult['error_message'] ?? null;
        $details = json_encode($checkResult['metadata'] ?? []);

        if ($entity->id && ($errorMessage !== null || ($details !== 'null' && $details !== '[]'))) {
            $detailsTable = $this->fetchTable('MonitorCheckDetails');
            $detail = $detailsTable->newEntity([
                'check_id' => $entity->id,
                'error_message' => $errorMessage,
                'details' => $details,
                'created' => (new DateTime())->format('Y-m-d H:i:s'),
            ]);
            $detailsTable->save($detail);
        }
    }

    /**
     * Update monitor status and statistics.
     *
     * @param \App\Model\Entity\Monitor $monitor Monitor entity
     * @param array $checkResult Check result
     * @return void
     */
    protected function updateMonitorStatus($monitor, array $checkResult): void
    {
        $monitorsTable = $this->fetchTable('Monitors');

        $monitor->status = $checkResult['status'];
        $monitor->last_check_at = new DateTime($checkResult['checked_at']);

        if (!$monitorsTable->save($monitor)) {
            Log::error("MonitorCheckJob: Failed to update monitor {$monitor->id}", [
                'errors' => $monitor->getErrors(),
            ]);
        }
    }

    /**
     * Handle incidents and alert dispatching based on monitor status.
     *
     * @param \App\Model\Entity\Monitor $monitor Monitor entity
     * @param array $checkResult Check result
     * @param string $oldStatus Previous monitor status
     * @return void
     */
    protected function handleIncidentsAndAlerts($monitor, array $checkResult, string $oldStatus): void
    {
        try {
            $incidentService = new IncidentService();

            $newStatus = $checkResult['status'];

            if ($newStatus === 'down' || $newStatus === 'degraded') {
                $incident = $incidentService->createIncident($monitor);

                if ($incident !== null) {
                    $this->dispatchAlertOrQueue($monitor, $incident);
                    Log::info("MonitorCheckJob: Incident created and alerts dispatched for monitor {$monitor->id}");
                } else {
                    $existingIncident = $incidentService->getActiveIncidentForMonitor($monitor->id);
                    if ($existingIncident !== null && $oldStatus !== $newStatus) {
                        $this->dispatchAlertOrQueue($monitor, $existingIncident);
                    }
                }
            } elseif ($newStatus === 'up' && ($oldStatus === 'down' || $oldStatus === 'degraded')) {
                $activeIncident = $incidentService->getActiveIncidentForMonitor($monitor->id);
                $incidentService->autoResolveIncidents($monitor);

                if ($activeIncident !== null) {
                    $incidentsTable = $this->fetchTable('Incidents');
                    $resolvedIncident = $incidentsTable->get($activeIncident->id);
                    $this->dispatchAlertOrQueue($monitor, $resolvedIncident);
                    Log::info("MonitorCheckJob: Incident resolved and recovery alerts dispatched for monitor {$monitor->id}");
                }
            }
        } catch (\Exception $e) {
            Log::error("MonitorCheckJob: Failed to handle incidents/alerts for monitor {$monitor->id}: {$e->getMessage()}");
        }
    }

    /**
     * Dispatch alert via queue (async) or fall back to synchronous AlertService.
     *
     * @param \App\Model\Entity\Monitor $monitor The monitor entity
     * @param \App\Model\Entity\Incident $incident The incident entity
     * @return void
     */
    protected function dispatchAlertOrQueue($monitor, $incident): void
    {
        $useQueue = !empty(Configure::read('Queue.notifications'));

        if ($useQueue) {
            try {
                QueueManager::push(NotificationJob::class, [
                    'data' => [
                        'monitor_id' => $monitor->id,
                        'incident_id' => $incident->id,
                        'organization_id' => $monitor->organization_id,
                    ],
                ], ['config' => 'notifications']);

                Log::debug("MonitorCheckJob: Queued NotificationJob for monitor {$monitor->id}, incident {$incident->id}");

                return;
            } catch (\Exception $e) {
                Log::warning("MonitorCheckJob: Failed to push NotificationJob to queue, falling back to sync: {$e->getMessage()}");
            }
        }

        // Fallback: synchronous dispatch
        $alertService = new AlertService();
        $alertService->registerChannel(new EmailAlertChannel());
        $alertService->registerChannel(new SlackAlertChannel());
        $alertService->registerChannel(new DiscordAlertChannel());
        $alertService->registerChannel(new TelegramAlertChannel());
        $alertService->registerChannel(new WebhookAlertChannel());
        $alertService->registerChannel(new SmsAlertChannel());
        $alertService->registerChannel(new WhatsAppAlertChannel());
        $alertService->registerChannel(new PagerDutyAlertChannel());
        $alertService->registerChannel(new OpsGenieAlertChannel());
        $alertService->registerChannel(new VoiceCallAlertChannel());

        $alertService->dispatch($monitor, $incident);
    }
}
