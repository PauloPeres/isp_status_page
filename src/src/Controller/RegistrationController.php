<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\AuditLogService;
use Cake\Log\Log;
use Cake\Routing\Router;

/**
 * Registration Controller
 *
 * Handles public user registration, email verification, and initial
 * organization creation for the SaaS onboarding flow.
 *
 * All UI is rendered by the Angular SPA. This controller only processes
 * server-side actions (email verification tokens) and redirects.
 */
class RegistrationController extends AppController
{
    /**
     * Audit log service instance.
     *
     * @var \App\Service\AuditLogService
     */
    private AuditLogService $audit;

    /**
     * Initialize method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->audit = new AuditLogService();
    }

    /**
     * Before filter callback
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);

        // Allow public access to registration and email verification
        $this->Authentication->addUnauthenticatedActions(['register', 'verifyEmail', 'resendVerification']);
    }

    /**
     * Register action — redirect to Angular registration page.
     *
     * Registration form submission is handled by the API v2 endpoint.
     *
     * @return \Cake\Http\Response
     */
    public function register()
    {
        // If user is already logged in, redirect to dashboard
        $result = $this->Authentication->getResult();
        if ($result && $result->isValid()) {
            return $this->redirect('/app/dashboard');
        }

        return $this->redirect('/app/register');
    }

    /**
     * Verify email action
     *
     * Processes the token server-side and redirects to Angular with the result.
     *
     * @param string|null $token Verification token
     * @return \Cake\Http\Response
     */
    public function verifyEmail($token = null)
    {
        if ($token === null) {
            // No token — redirect to Angular verify-email page
            $email = $this->request->getQuery('email', '');

            return $this->redirect('/app/verify-email' . ($email ? '?email=' . urlencode($email) : ''));
        }

        // Find user by verification token
        $usersTable = $this->fetchTable('Users');
        $user = $usersTable->find()
            ->where(['email_verification_token' => $token])
            ->first();

        if (!$user) {
            return $this->redirect('/app/login?error=invalid_token');
        }

        if (!$user->isEmailVerificationTokenValid()) {
            return $this->redirect('/app/login?error=expired_token');
        }

        // Mark email as verified
        $user->markEmailVerified();

        if ($usersTable->save($user)) {
            // Audit log email verification
            $this->audit->log(
                'email_verified',
                (int)$user->id,
                $this->request->clientIp(),
                $this->request->getHeaderLine('User-Agent'),
                ['email' => $user->email]
            );

            // Auto-login the user
            $this->Authentication->setIdentity($user);

            return $this->redirect('/app/dashboard?verified=true');
        }

        return $this->redirect('/app/login?error=verification_failed');
    }

    /**
     * Resend verification email action
     *
     * Processes the request server-side and redirects to Angular.
     *
     * @return \Cake\Http\Response
     */
    public function resendVerification()
    {
        $email = $this->request->getQuery('email', '');

        if (empty($email)) {
            return $this->redirect('/app/login');
        }

        $usersTable = $this->fetchTable('Users');
        $user = $usersTable->find()
            ->where(['email' => $email, 'email_verified' => false])
            ->first();

        if (!$user) {
            // Don't reveal whether the email exists
            return $this->redirect('/app/verify-email?email=' . urlencode($email) . '&resent=true');
        }

        // Rate limit: max 1 resend per 60 seconds
        if ($user->email_verification_sent_at) {
            $sentAt = $user->email_verification_sent_at;
            if ($sentAt instanceof \Cake\I18n\DateTime || $sentAt instanceof \DateTimeInterface) {
                $secondsSinceSent = time() - $sentAt->getTimestamp();
                if ($secondsSinceSent < 60) {
                    return $this->redirect('/app/verify-email?email=' . urlencode($email) . '&rate_limited=true');
                }
            }
        }

        // Regenerate token and resend
        $user->generateEmailVerificationToken();

        if ($usersTable->save($user)) {
            $this->sendVerificationEmail($user);
        }

        return $this->redirect('/app/verify-email?email=' . urlencode($email) . '&resent=true');
    }

    /**
     * Generate a unique slug for an organization based on a username
     *
     * @param \Cake\ORM\Table $organizationsTable Organizations table
     * @param string $username Username to base the slug on
     * @return string Unique slug
     */
    private function generateUniqueSlug($organizationsTable, string $username): string
    {
        $baseSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($username)));
        $baseSlug = trim($baseSlug, '-');

        if (strlen($baseSlug) < 3) {
            $baseSlug = $baseSlug . '-org';
        }

        $slug = $baseSlug;
        $counter = 1;

        while ($organizationsTable->find()->where(['slug' => $slug])->count() > 0) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Send verification email to a user
     *
     * @param \App\Model\Entity\User $user User entity
     * @return void
     */
    private function sendVerificationEmail($user): void
    {
        try {
            $emailService = new \App\Service\EmailService();
            $verifyLink = Router::url([
                'controller' => 'Registration',
                'action' => 'verifyEmail',
                $user->email_verification_token,
            ], true);

            $result = $emailService->sendEmailVerification($user, $verifyLink);

            if ($result['success']) {
                Log::info("Verification email sent successfully to {$user->email}");
            } else {
                Log::error("Failed to send verification email to {$user->email}: " .
                    ($result['technical_error'] ?? $result['message']));
                Log::info("Email verification link generated for {$user->email} (email delivery failed)");
            }
        } catch (\Exception $e) {
            Log::error("Error sending verification email: " . $e->getMessage());
            Log::info("Email verification link generated for {$user->email} (email send error)");
        }
    }
}
