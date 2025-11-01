<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Subscriber;
use App\Model\Entity\Incident;
use Cake\Mailer\Mailer;
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
            $mailer = new Mailer('default');

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
            $mailer = new Mailer('default');

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
     */
    public function sendTestEmail(string $toEmail): bool
    {
        try {
            $mailer = new Mailer('default');

            $siteName = $this->settingService->get('site_name', 'ISP Status');
            $fromEmail = $this->settingService->get('email_from', 'noreply@localhost');
            $fromName = $this->settingService->get('email_from_name', $siteName);

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
        } catch (\Exception $e) {
            error_log('Error sending test email: ' . $e->getMessage());
            return false;
        }
    }
}
