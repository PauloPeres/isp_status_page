<?php
declare(strict_types=1);

namespace App\Service\Alert;

use App\Model\Entity\AlertRule;
use App\Model\Entity\Incident;
use App\Model\Entity\Monitor;
use App\Service\SettingService;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\Mailer\TransportFactory;

/**
 * Email Alert Channel
 *
 * Sends alert notifications via email using CakePHP's Mailer system.
 * SMTP settings are loaded from SettingService.
 */
class EmailAlertChannel implements ChannelInterface
{
    /**
     * Setting service instance
     *
     * @var \App\Service\SettingService
     */
    protected SettingService $settingService;

    /**
     * Constructor
     *
     * @param \App\Service\SettingService|null $settingService Setting service instance
     */
    public function __construct(?SettingService $settingService = null)
    {
        $this->settingService = $settingService ?? new SettingService();
    }

    /**
     * Send alert email to all recipients in the rule
     *
     * @param \App\Model\Entity\AlertRule $rule The alert rule
     * @param \App\Model\Entity\Monitor $monitor The monitor
     * @param \App\Model\Entity\Incident $incident The incident
     * @return array Result with success flag and per-recipient results
     */
    public function send(AlertRule $rule, Monitor $monitor, Incident $incident): array
    {
        $recipients = $rule->getRecipients();

        if (empty($recipients)) {
            Log::warning("Alert rule {$rule->id} has no recipients configured");

            return [
                'success' => false,
                'results' => [],
            ];
        }

        // Configure SMTP transport
        $this->configureTransport();

        $results = [];
        $allSuccess = true;

        foreach ($recipients as $recipientEmail) {
            try {
                $this->sendToRecipient($recipientEmail, $monitor, $incident);

                $results[] = [
                    'recipient' => $recipientEmail,
                    'status' => 'sent',
                    'error' => null,
                ];

                Log::info("Alert email sent to {$recipientEmail} for monitor {$monitor->name}");
            } catch (\Exception $e) {
                $allSuccess = false;

                $results[] = [
                    'recipient' => $recipientEmail,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];

                Log::error("Failed to send alert email to {$recipientEmail}: {$e->getMessage()}");
            }
        }

        return [
            'success' => $allSuccess,
            'results' => $results,
        ];
    }

    /**
     * Get the channel type identifier
     *
     * @return string
     */
    public function getType(): string
    {
        return 'email';
    }

    /**
     * Get human-readable name for this channel
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Email Alert Channel';
    }

    /**
     * Send email to a single recipient
     *
     * @param string $recipientEmail Recipient email address
     * @param \App\Model\Entity\Monitor $monitor The monitor
     * @param \App\Model\Entity\Incident $incident The incident
     * @return void
     * @throws \Exception If sending fails
     */
    protected function sendToRecipient(string $recipientEmail, Monitor $monitor, Incident $incident): void
    {
        $mailer = new Mailer();
        $mailer->setTransport('alert');

        $fromEmail = $this->settingService->getString('email_from', 'noreply@example.com');
        $fromName = $this->settingService->getString('email_from_name', 'ISP Status');
        $siteName = $this->settingService->getString('site_name', 'ISP Status');

        $mailer->setFrom([$fromEmail => $fromName]);
        $mailer->setTo($recipientEmail);
        $mailer->setEmailFormat('html');

        // Determine if this is a down or up alert
        $isDown = $incident->isOngoing();

        if ($isDown) {
            $mailer->setSubject("[{$siteName}] ALERTA: {$monitor->name} esta FORA DO AR");
            $mailer->viewBuilder()
                ->setTemplate('alert_incident_down')
                ->setLayout('default');
        } else {
            $mailer->setSubject("[{$siteName}] RESOLVIDO: {$monitor->name} esta ONLINE");
            $mailer->viewBuilder()
                ->setTemplate('alert_incident_up')
                ->setLayout('default');
        }

        $mailer->setViewVars([
            'monitor' => $monitor,
            'incident' => $incident,
            'siteName' => $siteName,
        ]);

        $mailer->deliver();
    }

    /**
     * Configure the SMTP transport from settings
     *
     * @return void
     */
    protected function configureTransport(): void
    {
        $host = $this->settingService->getString('smtp_host', 'localhost');
        $port = $this->settingService->getInt('smtp_port', 587);
        $username = $this->settingService->getString('smtp_username', '');
        $password = $this->settingService->getString('smtp_password', '');

        $config = [
            'className' => 'Smtp',
            'host' => $host,
            'port' => $port,
            'timeout' => 30,
        ];

        // Only add authentication if username is configured
        if (!empty($username)) {
            $config['username'] = $username;
            $config['password'] = $password;
            $config['tls'] = true;
        }

        // Drop existing transport if configured, then re-register
        try {
            TransportFactory::drop('alert');
        } catch (\Exception $e) {
            // Transport didn't exist yet, that's fine
        }

        TransportFactory::setConfig('alert', $config);
    }
}
