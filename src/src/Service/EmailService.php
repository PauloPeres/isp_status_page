<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Subscriber;
use App\Model\Entity\Incident;
use Cake\Mailer\Mailer;
use Cake\Mailer\TransportFactory;
use Cake\Routing\Router;

/**
 * Email Service
 *
 * Handles sending emails to subscribers for verification and notifications.
 */
class EmailService
{
    /**
     * Settings service for accessing email configuration
     */
    private SettingService $settingService;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->settingService = new SettingService();
    }

    /**
     * Get SMTP transport configuration from database settings
     *
     * @return array SMTP transport configuration
     */
    private function getSMTPConfig(): array
    {
        // Get SMTP settings from database
        $host = $this->settingService->get('smtp_host', 'localhost');
        $port = (int)$this->settingService->get('smtp_port', 587);
        $username = $this->settingService->get('smtp_username', '');
        $password = $this->settingService->get('smtp_password', '');
        $encryption = strtolower($this->settingService->get('smtp_encryption', ''));

        // Build transport configuration
        $config = [
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'password' => $password,
            'className' => 'Smtp',
            'timeout' => 30,
        ];

        // Add TLS/SSL if configured
        if ($encryption === 'tls') {
            $config['tls'] = true;
        } elseif ($encryption === 'ssl') {
            $config['tls'] = true;
        }

        return $config;
    }

    /**
     * Configure a Mailer instance with SMTP settings
     *
     * @param \Cake\Mailer\Mailer $mailer Mailer instance to configure
     * @return \Cake\Mailer\Mailer Configured mailer
     */
    private function configureSMTP(Mailer $mailer): Mailer
    {
        $config = $this->getSMTPConfig();

        // Create a unique transport configuration name
        $transportName = 'smtp_dynamic_' . uniqid();

        // Configure the transport factory
        TransportFactory::setConfig($transportName, $config);

        // Get the transport instance and apply it to the mailer
        $transport = TransportFactory::get($transportName);
        $mailer->setTransport($transport);

        return $mailer;
    }

    /**
     * Send verification email to subscriber
     *
     * @param \App\Model\Entity\Subscriber $subscriber Subscriber entity
     * @return bool True if email was sent successfully
     */
    public function sendVerificationEmail(Subscriber $subscriber): bool
    {
        if (empty($subscriber->verification_token)) {
            return false;
        }

        try {
            $mailer = new Mailer();
            $this->configureSMTP($mailer);

            // Get site settings
            $siteName = $this->settingService->get('site_name', 'ISP Status');
            $fromEmail = $this->settingService->get('email_from', 'noreply@localhost');
            $fromName = $this->settingService->get('email_from_name', $siteName);

            // Build verification URL
            $verifyUrl = Router::url([
                'controller' => 'Subscribers',
                'action' => 'verify',
                $subscriber->verification_token,
            ], true);

            $mailer
                ->setEmailFormat('html')
                ->setFrom([$fromEmail => $fromName])
                ->setTo($subscriber->email)
                ->setSubject("Verifique seu email - {$siteName}")
                ->setViewVars([
                    'subscriber' => $subscriber,
                    'verifyUrl' => $verifyUrl,
                    'siteName' => $siteName,
                ])
                ->viewBuilder()
                    ->setTemplate('subscriber_verification')
                    ->setLayout('default');

            $mailer->deliver();

            return true;
        } catch (\Exception $e) {
            // Log error
            error_log('Error sending verification email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send incident notification to subscribers
     *
     * @param \App\Model\Entity\Incident $incident Incident entity
     * @param array<\App\Model\Entity\Subscriber> $subscribers List of subscribers
     * @return int Number of emails sent successfully
     */
    public function sendIncidentNotification(Incident $incident, array $subscribers): int
    {
        $sent = 0;

        foreach ($subscribers as $subscriber) {
            if ($this->sendIncidentEmail($subscriber, $incident)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Send incident email to a single subscriber
     *
     * @param \App\Model\Entity\Subscriber $subscriber Subscriber entity
     * @param \App\Model\Entity\Incident $incident Incident entity
     * @return bool True if email was sent successfully
     */
    private function sendIncidentEmail(Subscriber $subscriber, Incident $incident): bool
    {
        try {
            $mailer = new Mailer();
            $this->configureSMTP($mailer);

            // Get site settings
            $siteName = $this->settingService->get('site_name', 'ISP Status');
            $fromEmail = $this->settingService->get('email_from', 'noreply@localhost');
            $fromName = $this->settingService->get('email_from_name', $siteName);

            // Build URLs
            $statusUrl = Router::url(['controller' => 'Status', 'action' => 'index'], true);
            $unsubscribeUrl = Router::url([
                'controller' => 'Subscribers',
                'action' => 'unsubscribe',
                $subscriber->unsubscribe_token,
            ], true);

            // Choose template based on incident status
            $template = $incident->status === 'resolved' ? 'incident_resolved' : 'incident_down';
            $subject = $incident->status === 'resolved'
                ? "✅ Resolvido: {$incident->title}"
                : "⚠️ Incidente: {$incident->title}";

            $mailer
                ->setEmailFormat('html')
                ->setFrom([$fromEmail => $fromName])
                ->setTo($subscriber->email)
                ->setSubject("{$subject} - {$siteName}")
                ->setViewVars([
                    'subscriber' => $subscriber,
                    'incident' => $incident,
                    'statusUrl' => $statusUrl,
                    'unsubscribeUrl' => $unsubscribeUrl,
                    'siteName' => $siteName,
                ])
                ->viewBuilder()
                    ->setTemplate($template)
                    ->setLayout('default');

            $mailer->deliver();

            return true;
        } catch (\Exception $e) {
            // Log error
            error_log("Error sending incident email to {$subscriber->email}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Test email configuration by sending a test email
     *
     * @param string $toEmail Email address to send test to
     * @return bool True if test email was sent successfully
     * @throws \Exception If email fails to send
     */
    public function sendTestEmail(string $toEmail): bool
    {
        $siteName = $this->settingService->get('site_name', 'ISP Status');
        $fromEmail = $this->settingService->get('email_from', 'noreply@localhost');
        $fromName = $this->settingService->get('email_from_name', $siteName);

        // Create and configure mailer
        $mailer = new Mailer();
        $this->configureSMTP($mailer);

        $mailer
            ->setEmailFormat('html')
            ->setFrom([$fromEmail => $fromName])
            ->setTo($toEmail)
            ->setSubject("Test Email - {$siteName}")
            ->setViewVars([
                'siteName' => $siteName,
            ])
            ->viewBuilder()
                ->setTemplate('test')
                ->setLayout('default');

        $mailer->deliver();

        return true;
    }
}
