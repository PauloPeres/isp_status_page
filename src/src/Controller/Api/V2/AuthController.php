<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

use App\Service\JwtService;
use App\Service\TwoFactorService;
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
            $field = $existing->email === $email ? 'email' : 'username';
            $this->error("A user with this {$field} already exists", 422);

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
        $user->set('email_verified', true);

        if (!$usersTable->save($user)) {
            $this->error('Registration failed', 422, $user->getErrors());

            return;
        }

        // Create organization
        $orgsTable = $this->fetchTable('Organizations');
        $slug = strtolower(preg_replace('/[^a-z0-9]/', '-', $username));
        $org = $orgsTable->newEntity([
            'name' => $username . "'s Organization",
            'slug' => $slug . '-' . substr(bin2hex(random_bytes(3)), 0, 6),
            'plan' => 'free',
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

        // Generate tokens
        $jwtService = new JwtService();
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

        $email = $this->request->getData('email');
        $password = $this->request->getData('password');

        if (empty($email) || empty($password)) {
            $this->error('Email and password are required', 400);

            return;
        }

        // Find user by email or username
        $usersTable = $this->fetchTable('Users');
        $user = $usersTable->find()
            ->where(['OR' => ['Users.email' => $email, 'Users.username' => $email]])
            ->where(['Users.active' => true, 'Users.email_verified' => true])
            ->first();

        if (!$user || !password_verify($password, $user->password)) {
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
                $this->error('Invalid two-factor code', 401);

                return;
            }
        }

        // Get user's organization and role
        $orgUser = $this->fetchTable('OrganizationUsers')->find()
            ->where(['OrganizationUsers.user_id' => $user->id])
            ->first();

        $orgId = $orgUser ? $orgUser->organization_id : 0;
        $role = $orgUser ? $orgUser->role : 'viewer';

        // Generate tokens
        $jwtService = new JwtService();
        $accessToken = $jwtService->generateAccessToken(
            $user->id,
            $orgId,
            $role,
            (bool)$user->is_super_admin
        );
        $refreshToken = $jwtService->generateRefreshToken(
            $user->id,
            $this->request->clientIp(),
            $this->request->getHeaderLine('User-Agent')
        );

        // Update last_login timestamp
        $user->set('last_login', DateTime::now());
        $usersTable->save($user);

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

        $refreshToken = $this->request->getData('refresh_token');
        if (empty($refreshToken)) {
            $this->error('Refresh token is required', 400);

            return;
        }

        $jwtService = new JwtService();
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

        $jwtService = new JwtService();

        $refreshToken = $this->request->getData('refresh_token');
        $revokeAll = (bool)$this->request->getData('revoke_all', false);

        if ($revokeAll && $this->currentUserId > 0) {
            $jwtService->revokeAllUserTokens($this->currentUserId);
        } elseif (!empty($refreshToken)) {
            $jwtService->revokeRefreshToken($refreshToken);
        }

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

        $organizations = [];
        foreach ($orgUsers as $ou) {
            $organizations[] = [
                'id' => $ou->organization_id,
                'name' => $ou->organization->name ?? '',
                'role' => $ou->role,
                'is_current' => ($ou->organization_id === $this->currentOrgId),
            ];
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
            ],
            'current_organization' => [
                'id' => $this->currentOrgId,
                'role' => $this->currentRole,
            ],
            'organizations' => $organizations,
        ]);
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
        $jwtService = new JwtService();
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
