<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

use App\Service\AuditLogService;
use App\Service\JwtService;
use App\Service\LoginThrottleService;
use App\Service\TwoFactorService;
use Cake\Cache\Cache;
use Cake\Http\Cookie\Cookie;
use Cake\I18n\DateTime;
use Cake\Validation\Validator;

/**
 * AuthController
 *
 * Handles JWT-based authentication for the API v2 SPA endpoints:
 * login, refresh, logout, me, and switch-org.
 */
class AuthController extends AppController
{
    protected JwtService $jwtService;

    public function initialize(): void
    {
        parent::initialize();
        $this->jwtService = new JwtService();
    }

    /**
     * POST /api/v2/auth/register
     *
     * Create a new user account with an organization.
     * Returns JWT tokens on success so the user is immediately logged in.
     *
     * @return void
     */
    public function register(): void
    {
        $this->request->allowMethod(['post']);

        // IP-based rate limiting: max 5 registrations per IP per hour
        $ip = $this->request->clientIp();
        $cacheKey = 'register_attempts_' . md5($ip);
        $regData = Cache::read($cacheKey, 'default');
        if (!$regData || !is_array($regData)) {
            $regData = ['count' => 0, 'window_start' => time()];
        }
        // Reset window if more than 1 hour has passed
        if ((time() - (int)($regData['window_start'] ?? time())) > 3600) {
            $regData = ['count' => 0, 'window_start' => time()];
        }
        if ((int)($regData['count'] ?? 0) >= 5) {
            $this->error('Too many registration attempts. Please try again later.', 429);

            return;
        }
        $regData['count'] = ((int)($regData['count'] ?? 0)) + 1;
        Cache::write($cacheKey, $regData, 'default');

        $data = $this->request->getData();
        $username = trim((string)($data['username'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $password = (string)($data['password'] ?? '');

        if (empty($username) || empty($email) || empty($password)) {
            $this->error('Username, email, and password are required', 422);

            return;
        }

        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters', 422);

            return;
        }

        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $this->error('Password must contain at least one uppercase letter, one lowercase letter, and one number', 422);

            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address', 422);

            return;
        }

        // Check for existing user
        $usersTable = $this->fetchTable('Users');
        $existing = $usersTable->find()
            ->where(['OR' => ['Users.email' => $email, 'Users.username' => $username]])
            ->first();

        if ($existing) {
            $this->error('Registration failed. Please check your details and try again.', 422);

            return;
        }

        // Create user
        $user = $usersTable->newEntity([
            'username' => $username,
            'email' => $email,
            'password' => $password,
        ]);
        $user->set('role', 'admin');
        $user->set('active', true);
        $user->set('email_verified', false);

        if (!$usersTable->save($user)) {
            $this->error('Registration failed', 422, $user->getErrors());

            return;
        }

        // Create organization with 30-day free trial
        // Plan stays 'free' in DB; PlanService treats it as 'business' while trial is active
        $orgsTable = $this->fetchTable('Organizations');
        $slug = strtolower(preg_replace('/[^a-z0-9]/', '-', $username));
        $trialEndsAt = DateTime::now()->addDays(30);
        $org = $orgsTable->newEntity([
            'name' => $username . "'s Organization",
            'slug' => $slug . '-' . substr(bin2hex(random_bytes(3)), 0, 6),
            'plan' => 'free',
            'trial_ends_at' => $trialEndsAt,
            'active' => true,
        ]);

        if (!$orgsTable->save($org)) {
            // Rollback user creation
            $usersTable->delete($user);
            $this->error('Failed to create organization', 500);

            return;
        }

        // Link user to org
        $orgUsersTable = $this->fetchTable('OrganizationUsers');
        $orgUser = $orgUsersTable->newEntity([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'accepted_at' => DateTime::now(),
        ]);
        $orgUsersTable->save($orgUser);

        // Grant 50 trial notification credits for testing SMS/Voice
        try {
            $creditService = new \App\Service\Billing\NotificationCreditService();
            $creditService->addCredits(
                (int)$org->id,
                50,
                'trial_grant',
                'Trial credits: 50 credits to test SMS & Voice notifications'
            );
            \Cake\Log\Log::info("Granted 50 trial credits to org {$org->id} ({$org->name})");
        } catch (\Exception $e) {
            \Cake\Log\Log::warning('Failed to grant trial credits for new org: ' . $e->getMessage());
        }

        // Auto-create weekly status report for new organizations
        try {
            $scheduledReportsTable = $this->fetchTable('ScheduledReports');
            $weeklyReport = $scheduledReportsTable->newEntity([
                'organization_id' => $org->id,
                'name' => 'Weekly Status Report',
                'report_type' => 'uptime',
                'frequency' => 'weekly',
                'recipients' => json_encode([$data['email']]),
                'active' => true,
            ]);
            $scheduledReportsTable->save($weeklyReport);
        } catch (\Exception $e) {
            // Don't fail registration if report creation fails
            \Cake\Log\Log::warning('Failed to create weekly report for new org: ' . $e->getMessage());
        }

        // Generate tokens
        $jwtService = $this->jwtService;
        $accessToken = $jwtService->generateAccessToken(
            $user->id,
            $org->id,
            'owner',
            false
        );
        $refreshToken = $jwtService->generateRefreshToken(
            $user->id,
            $this->request->clientIp(),
            $this->request->getHeaderLine('User-Agent')
        );

        // Set refresh token as HttpOnly cookie
        $this->response = $this->response->withCookie(
            new Cookie(
                'refresh_token',
                $refreshToken,
                new \DateTime('+7 days'),
                '/',
                '',
                (bool)env('HTTPS_ONLY', false),
                true,
                'Lax'
            )
        );

        $this->success([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $jwtService->getAccessTokenTtl(),
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'is_super_admin' => false,
            ],
            'organization' => [
                'id' => $org->id,
                'role' => 'owner',
                'plan' => 'free',
                'effective_plan' => 'business',
                'is_trial' => true,
                'trial_expired' => false,
                'trial_days_remaining' => 30,
                'trial_ends_at' => $trialEndsAt->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * POST /api/v2/auth/login
     *
     * Authenticate a user with email/username + password, optionally
     * verifying a 2FA code. Returns JWT access token + refresh token.
     *
     * @return void
     */
    public function login(): void
    {
        $this->request->allowMethod(['post']);

        $email = $this->request->getData('email') ?? '';
        $throttle = new LoginThrottleService();
        if ($throttle->isLocked($email)) {
            $this->error('Too many failed attempts. Please try again in 15 minutes.', 429);

            return;
        }

        $password = $this->request->getData('password');

        if (empty($email) || empty($password)) {
            $this->error('Email and password are required', 400);

            return;
        }

        // Find user by email or username
        $usersTable = $this->fetchTable('Users');
        $user = $usersTable->find()
            ->where(['OR' => ['Users.email' => $email, 'Users.username' => $email]])
            ->where(['Users.active' => true])
            ->first();

        if (!$user || !password_verify($password, $user->password)) {
            $throttle->recordFailure($email);
            $this->error('Invalid credentials', 401);

            return;
        }

        // Check 2FA if enabled
        if ($user->two_factor_enabled) {
            $code = $this->request->getData('two_factor_code');
            if (!$code) {
                $this->error('Two-factor code required', 401, ['requires_2fa' => true]);

                return;
            }
            $tfService = new TwoFactorService();
            if (!$tfService->verifyCode($user->two_factor_secret, $code)) {
                $throttle->recordFailure($email);
                $this->error('Invalid two-factor code', 401);

                return;
            }
        }

        // Successful authentication — clear throttle
        $throttle->clearAttempts($email);

        // Get user's organization and role
        $orgUser = $this->fetchTable('OrganizationUsers')->find()
            ->where(['OrganizationUsers.user_id' => $user->id])
            ->first();

        $orgId = $orgUser ? $orgUser->organization_id : 0;
        $role = $orgUser ? $orgUser->role : 'viewer';

        // Remember me: shorter refresh token TTL if not checked
        $rememberMe = (bool)$this->request->getData('remember_me', true);
        $refreshTtl = $rememberMe ? 604800 : 7200; // 7 days vs 2 hours

        // Generate tokens
        $jwtService = $this->jwtService;
        $accessToken = $jwtService->generateAccessToken(
            $user->id,
            $orgId,
            $role,
            (bool)$user->is_super_admin
        );
        $refreshToken = $jwtService->generateRefreshToken(
            $user->id,
            $this->request->clientIp(),
            $this->request->getHeaderLine('User-Agent'),
            $refreshTtl
        );

        // Update last_login timestamp
        $user->set('last_login', DateTime::now());
        $usersTable->save($user);

        // Set refresh token as HttpOnly cookie
        $cookieExpiry = $rememberMe ? '+7 days' : '+2 hours';
        $this->response = $this->response->withCookie(
            new Cookie(
                'refresh_token',
                $refreshToken,
                new \DateTime($cookieExpiry),
                '/',
                '',
                (bool)env('HTTPS_ONLY', false),
                true,
                'Lax'
            )
        );

        $this->success([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $jwtService->getAccessTokenTtl(),
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'is_super_admin' => (bool)$user->is_super_admin,
            ],
            'organization' => $orgId ? [
                'id' => $orgId,
                'role' => $role,
            ] : null,
        ]);
    }

    /**
     * POST /api/v2/auth/refresh
     *
     * Exchange a valid refresh token for a new access token + refresh token pair.
     * The old refresh token is revoked (rotation).
     *
     * @return void
     */
    public function refresh(): void
    {
        $this->request->allowMethod(['post']);

        // Accept refresh token from body (backwards compat) or HttpOnly cookie
        $refreshToken = $this->request->getData('refresh_token')
            ?? $this->request->getCookie('refresh_token');
        if (empty($refreshToken)) {
            $this->error('Refresh token is required', 400);

            return;
        }

        $jwtService = $this->jwtService;
        $userId = $jwtService->validateRefreshToken($refreshToken);

        if ($userId === null) {
            $this->error('Invalid or expired refresh token', 401);

            return;
        }

        // Revoke the old refresh token (rotation)
        $jwtService->revokeRefreshToken($refreshToken);

        // Fetch user and org info for new access token
        $usersTable = $this->fetchTable('Users');
        $user = $usersTable->find()
            ->where(['Users.id' => $userId, 'Users.active' => true])
            ->first();

        if (!$user) {
            $this->error('User account is disabled', 401);

            return;
        }

        $orgUser = $this->fetchTable('OrganizationUsers')->find()
            ->where(['OrganizationUsers.user_id' => $user->id])
            ->first();

        $orgId = $orgUser ? $orgUser->organization_id : 0;
        $role = $orgUser ? $orgUser->role : 'viewer';

        // Generate new token pair
        $newAccessToken = $jwtService->generateAccessToken(
            $user->id,
            $orgId,
            $role,
            (bool)$user->is_super_admin
        );
        $newRefreshToken = $jwtService->generateRefreshToken(
            $user->id,
            $this->request->clientIp(),
            $this->request->getHeaderLine('User-Agent')
        );

        // Set new refresh token as HttpOnly cookie
        $this->response = $this->response->withCookie(
            new Cookie(
                'refresh_token',
                $newRefreshToken,
                new \DateTime('+7 days'),
                '/',
                '',
                (bool)env('HTTPS_ONLY', false),
                true,
                'Lax'
            )
        );

        $this->success([
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $jwtService->getAccessTokenTtl(),
        ]);
    }

    /**
     * POST /api/v2/auth/logout
     *
     * Revoke the provided refresh token (or all tokens for the user).
     *
     * @return void
     */
    public function logout(): void
    {
        $this->request->allowMethod(['post']);

        $jwtService = $this->jwtService;

        // Block the current access token so it cannot be reused after logout
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (str_starts_with($authHeader, 'Bearer ')) {
            $rawToken = substr($authHeader, 7);
            $payload = $jwtService->verifyAccessToken($rawToken);
            if ($payload !== null && isset($payload->exp)) {
                $tokenId = $jwtService->getTokenIdentifier($rawToken, $payload);
                $jwtService->blockToken($tokenId, (int)$payload->exp);
            }
        }

        // Accept refresh token from body (backwards compat) or HttpOnly cookie
        $refreshToken = $this->request->getData('refresh_token')
            ?? $this->request->getCookie('refresh_token');
        $revokeAll = (bool)$this->request->getData('revoke_all', false);

        if ($revokeAll && $this->currentUserId > 0) {
            $jwtService->revokeAllUserTokens($this->currentUserId);
        } elseif (!empty($refreshToken)) {
            $jwtService->revokeRefreshToken($refreshToken);
        }

        // Clear the refresh token cookie
        $this->response = $this->response->withExpiredCookie(
            new Cookie('refresh_token', '', null, '/')
        );

        $audit = new AuditLogService();
        $audit->log(
            'logout',
            $this->currentUserId > 0 ? $this->currentUserId : null,
            $this->request->clientIp(),
            $this->request->getHeaderLine('User-Agent'),
            ['revoke_all' => $revokeAll],
            $this->currentOrgId ?: null
        );

        $this->success(['message' => 'Logged out successfully']);
    }

    /**
     * GET /api/v2/auth/me
     *
     * Return the currently authenticated user's profile and organization info.
     *
     * @return void
     */
    public function me(): void
    {
        $this->request->allowMethod(['get']);

        if ($this->currentUserId === 0) {
            $this->error('Not authenticated', 401);

            return;
        }

        $usersTable = $this->fetchTable('Users');
        $user = $usersTable->find()
            ->where(['Users.id' => $this->currentUserId])
            ->first();

        if (!$user) {
            $this->error('User not found', 404);

            return;
        }

        // Get all organizations for the user
        $orgUsers = $this->fetchTable('OrganizationUsers')->find()
            ->contain(['Organizations'])
            ->where(['OrganizationUsers.user_id' => $user->id])
            ->all();

        $planService = new \App\Service\PlanService();

        $organizations = [];
        foreach ($orgUsers as $ou) {
            $orgData = [
                'id' => $ou->organization_id,
                'name' => $ou->organization->name ?? '',
                'role' => $ou->role,
                'is_current' => ($ou->organization_id === $this->currentOrgId),
            ];

            // Include trial info for the current organization
            if ($ou->organization_id === $this->currentOrgId) {
                $trialInfo = $planService->getTrialInfo($ou->organization_id);
                $orgData['plan'] = $ou->organization->plan ?? 'free';
                $orgData['effective_plan'] = $trialInfo['effective_plan'];
                $orgData['is_trial'] = $trialInfo['is_trial'];
                $orgData['trial_expired'] = $trialInfo['trial_expired'];
                $orgData['trial_days_remaining'] = $trialInfo['trial_days_remaining'];
                $orgData['trial_ends_at'] = $trialInfo['trial_ends_at'];
            }

            $organizations[] = $orgData;
        }

        $this->success([
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'is_super_admin' => (bool)$user->is_super_admin,
                'two_factor_enabled' => (bool)$user->two_factor_enabled,
                'language' => $user->language ?? 'pt_BR',
                'timezone' => $user->timezone ?? 'America/Sao_Paulo',
                'phone_number' => $user->phone_number ?? null,
            ],
            'current_organization' => [
                'id' => $this->currentOrgId,
                'role' => $this->currentRole,
            ],
            'organizations' => $organizations,
        ]);
    }

    /**
     * PUT /api/v2/auth/me
     *
     * Update the currently authenticated user's profile fields.
     *
     * @return void
     */
    public function updateMe(): void
    {
        $this->request->allowMethod(['put', 'patch']);

        if ($this->currentUserId === 0) {
            $this->error('Not authenticated', 401);

            return;
        }

        $usersTable = $this->fetchTable('Users');
        $user = $usersTable->find()
            ->where(['Users.id' => $this->currentUserId])
            ->first();

        if (!$user) {
            $this->error('User not found', 404);

            return;
        }

        $data = $this->request->getData();
        $allowed = ['language', 'timezone', 'phone_number'];
        $updateData = array_intersect_key($data, array_flip($allowed));

        $user = $usersTable->patchEntity($user, $updateData);

        if (!$usersTable->save($user)) {
            $this->error('Failed to update profile', 422, $user->getErrors());

            return;
        }

        $this->success([
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'language' => $user->language ?? 'pt_BR',
                'timezone' => $user->timezone ?? 'America/Sao_Paulo',
                'phone_number' => $user->phone_number ?? null,
            ],
        ]);
    }

    /**
     * POST /api/v2/auth/change-password
     *
     * Change the currently authenticated user's password.
     * Requires current_password and new_password.
     *
     * @return void
     */
    public function changePassword(): void
    {
        $this->request->allowMethod(['post']);

        if ($this->currentUserId === 0) {
            $this->error('Not authenticated', 401);

            return;
        }

        $currentPassword = (string)($this->request->getData('current_password') ?? '');
        $newPassword = (string)($this->request->getData('new_password') ?? '');

        if (empty($currentPassword) || empty($newPassword)) {
            $this->error('Current password and new password are required', 422);

            return;
        }

        if (strlen($newPassword) < 8) {
            $this->error('Password must be at least 8 characters', 422);

            return;
        }

        if (!preg_match('/[A-Z]/', $newPassword)) {
            $this->error('Password must contain at least one uppercase letter', 422);

            return;
        }

        if (!preg_match('/[a-z]/', $newPassword)) {
            $this->error('Password must contain at least one lowercase letter', 422);

            return;
        }

        if (!preg_match('/[0-9]/', $newPassword)) {
            $this->error('Password must contain at least one number', 422);

            return;
        }

        if (!preg_match('/[^A-Za-z0-9]/', $newPassword)) {
            $this->error('Password must contain at least one special character', 422);

            return;
        }

        $usersTable = $this->fetchTable('Users');
        $user = $usersTable->find()
            ->where(['Users.id' => $this->currentUserId])
            ->first();

        if (!$user) {
            $this->error('User not found', 404);

            return;
        }

        if (!password_verify($currentPassword, $user->password)) {
            $this->error('Current password is incorrect', 422);

            return;
        }

        $user = $usersTable->patchEntity($user, ['password' => $newPassword]);

        if (!$usersTable->save($user)) {
            $this->error('Failed to change password', 500);

            return;
        }

        $this->success(['message' => 'Password changed successfully']);
    }

    /**
     * POST /api/v2/auth/switch-org
     *
     * Switch the user's active organization. Returns a new access token
     * with the updated org_id and role.
     *
     * @return void
     */
    public function switchOrg(): void
    {
        $this->request->allowMethod(['post']);

        $orgId = (int)$this->request->getData('organization_id');
        if ($orgId <= 0) {
            $this->error('Organization ID is required', 400);

            return;
        }

        // Verify the user belongs to the target organization
        $orgUser = $this->fetchTable('OrganizationUsers')->find()
            ->where([
                'OrganizationUsers.user_id' => $this->currentUserId,
                'OrganizationUsers.organization_id' => $orgId,
            ])
            ->first();

        if (!$orgUser && !$this->isSuperAdmin) {
            $this->error('You do not belong to this organization', 403);

            return;
        }

        $role = $orgUser ? $orgUser->role : 'admin';

        // Generate a new access token with the target org
        $jwtService = $this->jwtService;
        $accessToken = $jwtService->generateAccessToken(
            $this->currentUserId,
            $orgId,
            $role,
            $this->isSuperAdmin
        );

        $this->success([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => $jwtService->getAccessTokenTtl(),
            'organization' => [
                'id' => $orgId,
                'role' => $role,
            ],
        ]);
    }
}
