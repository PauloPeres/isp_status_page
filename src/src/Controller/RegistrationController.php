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
     * Register action
     *
     * GET: Show registration form
     * POST: Create user + organization + organization_user, send verification email
     *
     * @return \Cake\Http\Response|null|void
     */
    public function register()
    {
        // Disable layout - use standalone HTML (same pattern as login)
        $this->viewBuilder()->disableAutoLayout();

        $this->request->allowMethod(['get', 'post']);

        // If user is already logged in, redirect to dashboard
        $result = $this->Authentication->getResult();
        if ($result && $result->isValid()) {
            return $this->redirect('/dashboard');
        }

        $usersTable = $this->fetchTable('Users');
        $user = $usersTable->newEmptyEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            // Validate password confirmation
            if (empty($data['password']) || empty($data['password_confirm'])) {
                $this->Flash->error(__('Please fill in all required fields.'));
                $this->set(compact('user'));
                return;
            }

            if ($data['password'] !== $data['password_confirm']) {
                $this->Flash->error(__('Passwords do not match.'));
                $this->set(compact('user'));
                return;
            }

            if (strlen($data['password']) < 8) {
                $this->Flash->error(__('Password must be at least 8 characters long.'));
                $this->set(compact('user'));
                return;
            }

            // Prepare user data - only mass-assignable fields
            $userData = [
                'username' => $data['username'] ?? '',
                'email' => $data['email'] ?? '',
                'password' => $data['password'],
            ];

            $user = $usersTable->patchEntity($user, $userData);

            // Set sensitive fields directly (not mass-assignable)
            $user->set('role', 'admin'); // Default role for self-registered users
            $user->set('active', true);
            $user->set('email_verified', false);
            $user->set('force_password_change', false);

            // Generate email verification token
            $user->generateEmailVerificationToken();

            // Use a transaction to create User + Organization + OrganizationUser atomically
            $connection = $usersTable->getConnection();
            try {
                $connection->begin();

                // Save the user first
                if (!$usersTable->save($user)) {
                    $connection->rollback();
                    $this->Flash->error(__('Could not create account. Please check the errors below.'));
                    $this->set(compact('user'));
                    return;
                }

                // Create organization
                $organizationsTable = $this->fetchTable('Organizations');
                $orgName = ($data['username'] ?? 'User') . "'s Organization";
                $orgSlug = $this->generateUniqueSlug($organizationsTable, $data['username'] ?? 'user');

                $organization = $organizationsTable->newEntity([
                    'name' => $orgName,
                    'slug' => $orgSlug,
                    'plan' => 'free',
                    'timezone' => 'UTC',
                    'language' => 'en',
                    'active' => true,
                ]);

                if (!$organizationsTable->save($organization)) {
                    $connection->rollback();
                    $this->Flash->error(__('Could not create organization. Please try again.'));
                    $this->set(compact('user'));
                    return;
                }

                // Create organization_user link (role=owner)
                $orgUsersTable = $this->fetchTable('OrganizationUsers');
                $orgUser = $orgUsersTable->newEntity([
                    'organization_id' => $organization->id,
                    'user_id' => $user->id,
                    'role' => 'owner',
                    'accepted_at' => new \DateTime(),
                ]);

                if (!$orgUsersTable->save($orgUser)) {
                    $connection->rollback();
                    $this->Flash->error(__('Could not complete registration. Please try again.'));
                    $this->set(compact('user'));
                    return;
                }

                $connection->commit();
            } catch (\Exception $e) {
                $connection->rollback();
                Log::error('Registration failed: ' . $e->getMessage());
                $this->Flash->error(__('An error occurred during registration. Please try again.'));
                $this->set(compact('user'));
                return;
            }

            // TASK-AUTH-018: Audit log registration
            $this->audit->log(
                'registration',
                (int)$user->id,
                $this->request->clientIp(),
                $this->request->getHeaderLine('User-Agent'),
                ['email' => $user->email, 'username' => $user->username]
            );

            // Send verification email
            $this->sendVerificationEmail($user);

            // Redirect to "check your email" page
            $this->set('email', $user->email);
            return $this->redirect(['action' => 'verifyEmail']);
        }

        $this->set(compact('user'));
    }

    /**
     * Verify email action
     *
     * GET /registration/verify-email - Show "check your email" page
     * GET /registration/verify-email/{token} - Verify email with token
     *
     * @param string|null $token Verification token
     * @return \Cake\Http\Response|null|void
     */
    public function verifyEmail($token = null)
    {
        // Disable layout - use standalone HTML
        $this->viewBuilder()->disableAutoLayout();

        if ($token === null) {
            // Show "check your email" confirmation page
            $email = $this->request->getQuery('email', '');
            $this->set(compact('email'));
            return;
        }

        // Find user by verification token
        $usersTable = $this->fetchTable('Users');
        $user = $usersTable->find()
            ->where(['email_verification_token' => $token])
            ->first();

        if (!$user) {
            $this->Flash->error(__('Invalid or expired verification link.'));
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        if (!$user->isEmailVerificationTokenValid()) {
            $this->Flash->error(__('Verification link has expired. Please register again or request a new link.'));
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        // Mark email as verified
        $user->markEmailVerified();

        if ($usersTable->save($user)) {
            // TASK-AUTH-018: Audit log email verification
            $this->audit->log(
                'email_verified',
                (int)$user->id,
                $this->request->clientIp(),
                $this->request->getHeaderLine('User-Agent'),
                ['email' => $user->email]
            );

            // Auto-login the user
            $this->Authentication->setIdentity($user);

            $this->Flash->success(__('Email verified successfully! Welcome to ISP Status.'));
            return $this->redirect('/dashboard');
        }

        $this->Flash->error(__('Could not verify email. Please try again.'));
        return $this->redirect(['controller' => 'Users', 'action' => 'login']);
    }

    /**
     * Resend verification email action (TASK-AUTH-013)
     *
     * Rate limited: max 1 resend per 60 seconds
     *
     * @return \Cake\Http\Response|null|void
     */
    public function resendVerification()
    {
        $this->viewBuilder()->disableAutoLayout();

        $email = $this->request->getQuery('email', '');

        if (empty($email)) {
            $this->Flash->error(__('Email address is required.'));
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        $usersTable = $this->fetchTable('Users');
        $user = $usersTable->find()
            ->where(['email' => $email, 'email_verified' => false])
            ->first();

        if (!$user) {
            // Don't reveal whether the email exists - show generic success
            $this->Flash->success(__('If your email is registered and unverified, a new verification link has been sent.'));
            return $this->redirect(['action' => 'verifyEmail', '?' => ['email' => $email]]);
        }

        // Rate limit: max 1 resend per 60 seconds
        if ($user->email_verification_sent_at) {
            $sentAt = $user->email_verification_sent_at;
            if ($sentAt instanceof \Cake\I18n\DateTime || $sentAt instanceof \DateTimeInterface) {
                $secondsSinceSent = time() - $sentAt->getTimestamp();
                if ($secondsSinceSent < 60) {
                    $waitSeconds = 60 - $secondsSinceSent;
                    $this->Flash->warning(__('Please wait {0} seconds before requesting another verification email.', $waitSeconds));
                    return $this->redirect(['action' => 'verifyEmail', '?' => ['email' => $email]]);
                }
            }
        }

        // Regenerate token and resend
        $user->generateEmailVerificationToken();

        if ($usersTable->save($user)) {
            $this->sendVerificationEmail($user);
            $this->Flash->success(__('A new verification email has been sent. Please check your inbox.'));
        } else {
            $this->Flash->error(__('Could not resend verification email. Please try again.'));
        }

        return $this->redirect(['action' => 'verifyEmail', '?' => ['email' => $email]]);
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
        // Convert to lowercase, replace non-alphanumeric with hyphens
        $baseSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($username)));
        $baseSlug = trim($baseSlug, '-');

        // Ensure minimum length
        if (strlen($baseSlug) < 3) {
            $baseSlug = $baseSlug . '-org';
        }

        $slug = $baseSlug;
        $counter = 1;

        // Check for uniqueness
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
                // Log that verification was attempted (do not log the actual token)
                Log::info("Email verification link generated for {$user->email} (email delivery failed)");
            }
        } catch (\Exception $e) {
            Log::error("Error sending verification email: " . $e->getMessage());
            // Log that verification was attempted (do not log the actual token)
            Log::info("Email verification link generated for {$user->email} (email send error)");
        }
    }
}
