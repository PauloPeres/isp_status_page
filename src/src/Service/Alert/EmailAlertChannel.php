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
use Cake\Core\Configure;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Email Alert Channel
 *
 * Sends alert notifications via email using CakePHP's Mailer system.
 * SMTP settings are loaded from SettingService.
 */
class EmailAlertChannel implements ChannelInterface
{
    use LocatorAwareTrait;

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
        // Use resolved recipients if available, fall back to legacy getRecipients()
        /** @var \App\Service\Alert\ResolvedRecipient[]|null $resolvedRecipients */
        $resolvedRecipients = $rule->_resolvedRecipients ?? null;

        if ($resolvedRecipients !== null && !empty($resolvedRecipients)) {
            return $this->sendToResolvedRecipients($resolvedRecipients, $rule, $monitor, $incident);
        }

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
                    'user_id' => null,
                ];

                Log::info("Alert email sent to {$recipientEmail} for monitor {$monitor->name}");
            } catch (\Exception $e) {
                $allSuccess = false;

                $results[] = [
                    'recipient' => $recipientEmail,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'user_id' => null,
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
     * Send alert emails using resolved recipients with user tracking.
     *
     * @param array<\App\Service\Alert\ResolvedRecipient> $resolvedRecipients Resolved recipients
     * @param \App\Model\Entity\AlertRule $rule The alert rule
     * @param \App\Model\Entity\Monitor $monitor The monitor
     * @param \App\Model\Entity\Incident $incident The incident
     * @return array Result with success flag and per-recipient results
     */
    protected function sendToResolvedRecipients(
        array $resolvedRecipients,
        AlertRule $rule,
        Monitor $monitor,
        Incident $incident
    ): array {
        $this->configureTransport();

        $results = [];
        $allSuccess = true;

        foreach ($resolvedRecipients as $recipient) {
            $recipientEmail = $recipient->address;

            try {
                // Build per-user acknowledge URL with user identifier
                $userParam = $recipient->userId !== null ? (string)$recipient->userId : null;
                $this->sendToRecipient($recipientEmail, $monitor, $incident, $userParam);

                $results[] = [
                    'recipient' => $recipientEmail,
                    'status' => 'sent',
                    'error' => null,
                    'user_id' => $recipient->userId,
                ];

                Log::info("Alert email sent to {$recipientEmail} (user_id: {$recipient->userId}) for monitor {$monitor->name}");
            } catch (\Exception $e) {
                $allSuccess = false;

                $results[] = [
                    'recipient' => $recipientEmail,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'user_id' => $recipient->userId,
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
     * @param string|null $userParam Optional user identifier for per-user acknowledge URL
     * @return void
     * @throws \Exception If sending fails
     */
    protected function sendToRecipient(string $recipientEmail, Monitor $monitor, Incident $incident, ?string $userParam = null): void
    {
        $mailer = new Mailer();
        $mailer->setTransport('alert');

        $fromEmail = $this->settingService->getString('email_from', Configure::read('Brand.noreplyEmail', 'noreply@usekeeup.com'));
        $fromName = $this->settingService->getString('email_from_name', Configure::read('Brand.emailFromName', 'KeepUp'));
        $siteName = $this->settingService->getString('site_name', Configure::read('Brand.name', 'KeepUp'));

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

        // Build acknowledge URL for down alerts (with per-user tracking)
        $acknowledgeUrl = '';
        if ($isDown) {
            $acknowledgeUrl = $this->buildAcknowledgeUrl($incident, $siteName);
            if (!empty($acknowledgeUrl) && $userParam !== null) {
                $separator = str_contains($acknowledgeUrl, '?') ? '&' : '?';
                $acknowledgeUrl .= $separator . 'u=' . urlencode($userParam);
            }
        }

        $mailer->setViewVars([
            'monitor' => $monitor,
            'incident' => $incident,
            'siteName' => $siteName,
            'acknowledgeUrl' => $acknowledgeUrl,
        ]);

        $mailer->deliver();
    }

    /**
     * Build the acknowledge URL for an incident
     *
     * Generates a token if the incident doesn't have one yet, saves it,
     * and returns the full public URL.
     *
     * @param \App\Model\Entity\Incident $incident The incident
     * @param string $siteName Site name for logging
     * @return string The acknowledge URL
     */
    protected function buildAcknowledgeUrl(Incident $incident, string $siteName): string
    {
        try {
            // Generate token if not already set
            if (empty($incident->acknowledgement_token)) {
                $incident->generateAcknowledgementToken();

                $incidentsTable = $this->fetchTable('Incidents');
                $incidentsTable->save($incident);
            }

            $siteUrl = rtrim($this->settingService->getString('site_url', ''), '/');

            return "{$siteUrl}/incidents/acknowledge/{$incident->id}/{$incident->acknowledgement_token}";
        } catch (\Exception $e) {
            Log::error("Failed to build acknowledge URL: {$e->getMessage()}");

            return '';
        }
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
