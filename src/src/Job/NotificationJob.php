<?php
declare(strict_types=1);

namespace App\Job;

use App\Service\Alert\AlertService;
use App\Service\Alert\DiscordAlertChannel;
use App\Service\Alert\EmailAlertChannel;
use App\Service\Alert\OpsGenieAlertChannel;
use App\Service\Alert\PagerDutyAlertChannel;
use App\Service\Alert\SlackAlertChannel;
use App\Service\Alert\SmsAlertChannel;
use App\Service\Alert\TelegramAlertChannel;
use App\Service\Alert\WebhookAlertChannel;
use App\Service\Alert\WhatsAppAlertChannel;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Queue\Job\JobInterface;
use Cake\Queue\Job\Message;
use Interop\Queue\Processor;

/**
 * Notification Job
 *
 * Dispatches alert notifications for a single monitor + incident
 * combination via the AlertService. Handles throttle, quiet hours,
 * and all channel routing through the existing AlertService logic.
 */
class NotificationJob implements JobInterface
{
    use LocatorAwareTrait;

    /**
     * Maximum number of retry attempts.
     *
     * @var int|null
     */
    public static ?int $maxAttempts = 3;

    /**
     * Execute the notification job.
     *
     * @param \Cake\Queue\Job\Message $message The queue message
     * @return string Processor result (ACK or REJECT)
     */
    public function execute(Message $message): string
    {
        $data = $message->getArgument('data') ?? [];
        $monitorId = $data['monitor_id'] ?? null;
        $incidentId = $data['incident_id'] ?? null;

        if ($monitorId === null || $incidentId === null) {
            Log::error('NotificationJob: Missing monitor_id or incident_id in message data');

            return Processor::REJECT;
        }

        try {
            // Load the monitor and incident
            $monitorsTable = $this->fetchTable('Monitors');
            $incidentsTable = $this->fetchTable('Incidents');

            $monitor = $monitorsTable->get($monitorId);
            $incident = $incidentsTable->get($incidentId);

            // Initialize alert service with all channels
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

            // Dispatch alerts — AlertService handles throttle, quiet hours, etc.
            $dispatched = $alertService->dispatch($monitor, $incident);

            Log::info("NotificationJob: Dispatched {$dispatched} alert(s) for monitor {$monitorId}, incident {$incidentId}");

            return Processor::ACK;
        } catch (\Cake\Datasource\Exception\RecordNotFoundException $e) {
            Log::error("NotificationJob: Record not found — monitor {$monitorId} or incident {$incidentId}: {$e->getMessage()}");

            return Processor::REJECT;
        } catch (\Exception $e) {
            Log::error("NotificationJob: Failed for monitor {$monitorId}, incident {$incidentId}: {$e->getMessage()}");

            return Processor::REJECT;
        }
    }
}
