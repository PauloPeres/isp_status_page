<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\User;
use Cake\Http\Client;
use Cake\Http\Session;
use Cake\Log\LogTrait;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * OAuthService
 *
 * Handles OAuth authorization URL generation and callback processing
 * for Google and GitHub providers (TASK-704).
 */
class OAuthService
{
    use LocatorAwareTrait;
    use LogTrait;

    /**
     * TASK-AUTH-019: Flash message to show after OAuth callback.
     *
     * When an existing password-based user is found by email but OAuth
     * fields are NOT auto-linked, this message explains next steps.
     *
     * @var string|null
     */
    private ?string $pendingLinkMessage = null;

    /**
     * Supported OAuth providers.
     */
    public const PROVIDER_GOOGLE = 'google';
    public const PROVIDER_GITHUB = 'github';

    /**
     * Google OAuth endpoints.
     */
    private const GOOGLE_AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const GOOGLE_TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const GOOGLE_USERINFO_URL = 'https://www.googleapis.com/oauth2/v2/userinfo';

    /**
     * GitHub OAuth endpoints.
     */
    private const GITHUB_AUTH_URL = 'https://github.com/login/oauth/authorize';
    private const GITHUB_TOKEN_URL = 'https://github.com/login/oauth/access_token';
    private const GITHUB_USERINFO_URL = 'https://api.github.com/user';
    private const GITHUB_EMAILS_URL = 'https://api.github.com/user/emails';

    /**
     * HTTP client instance.
     *
     * @var \Cake\Http\Client
     */
    private Client $httpClient;

    /**
     * Constructor.
     *
     * @param \Cake\Http\Client|null $httpClient Optional HTTP client for testing.
     */
    public function __construct(?Client $httpClient = null)
    {
        $this->httpClient = $httpClient ?? new Client();
    }

    /**
     * Get the authorization URL for the given provider.
     *
     * @param string $provider The OAuth provider (google, github).
     * @param \Cake\Http\Session|null $session The session to store OAuth state in (TASK-AUTH-002).
     * @return string The authorization URL to redirect the user to.
     * @throws \InvalidArgumentException If provider is not supported.
     */
    public function getAuthorizationUrl(string $provider, ?Session $session = null): string
    {
        return match ($provider) {
            self::PROVIDER_GOOGLE => $this->getGoogleAuthUrl($session),
            self::PROVIDER_GITHUB => $this->getGitHubAuthUrl($session),
            default => throw new \InvalidArgumentException("Unsupported OAuth provider: {$provider}"),
        };
    }

    /**
     * Verify the OAuth state parameter to prevent CSRF attacks (TASK-AUTH-002).
     *
     * @param array $queryParams The callback query parameters.
     * @param \Cake\Http\Session|null $session The session containing the stored state.
     * @return bool True if state is valid, false otherwise.
     */
    public function verifyState(array $queryParams, ?Session $session = null): bool
    {
        if (!$session) {
            $this->log('OAuth state verification failed: no session provided', 'warning');

            return false;
        }

        $queryState = $queryParams['state'] ?? null;
        $sessionState = $session->read('oauth_state');

        // Always delete the state from session after reading (one-time use)
        $session->delete('oauth_state');

        if (empty($queryState) || empty($sessionState)) {
            $this->log('OAuth state verification failed: missing state parameter', 'warning');

            return false;
        }

        if (!hash_equals($sessionState, $queryState)) {
            $this->log('OAuth state verification failed: state mismatch', 'warning');

            return false;
        }

        return true;
    }

    /**
     * Handle the OAuth callback and return a User entity (existing or newly created).
     *
     * @param string $provider The OAuth provider.
     * @param array $queryParams The callback query parameters (contains 'code').
     * @return \App\Model\Entity\User|null The authenticated user, or null on failure.
     */
    public function handleCallback(string $provider, array $queryParams): ?User
    {
        try {
            $code = $queryParams['code'] ?? null;
            if (empty($code)) {
                $this->log('OAuth callback missing authorization code', 'warning');
                return null;
            }

            // Exchange code for access token
            $accessToken = $this->exchangeCodeForToken($provider, $code);
            if (empty($accessToken)) {
                $this->log("OAuth token exchange failed for provider: {$provider}", 'error');
                return null;
            }

            // Fetch user info from provider
            $providerUser = $this->fetchUserInfo($provider, $accessToken);
            if (empty($providerUser) || empty($providerUser['email'])) {
                $this->log("OAuth user info fetch failed for provider: {$provider}", 'error');
                return null;
            }

            // Find or create user
            return $this->findOrCreateUser($provider, $providerUser);
        } catch (\Exception $e) {
            $this->log("OAuth callback error for {$provider}: {$e->getMessage()}", 'error');
            return null;
        }
    }

    /**
     * Check if a provider is supported.
     *
     * @param string $provider The provider name.
     * @return bool
     */
    public function isValidProvider(string $provider): bool
    {
        return in_array($provider, [self::PROVIDER_GOOGLE, self::PROVIDER_GITHUB], true);
    }

    /**
     * TASK-AUTH-019: Get any pending-link flash message set during callback.
     *
     * @return string|null
     */
    public function getPendingLinkMessage(): ?string
    {
        return $this->pendingLinkMessage;
    }

    /**
     * Get Google OAuth authorization URL.
     *
     * @param \Cake\Http\Session|null $session The session to store state in (TASK-AUTH-002).
     * @return string
     */
    private function getGoogleAuthUrl(?Session $session = null): string
    {
        $params = [
            'client_id' => env('GOOGLE_CLIENT_ID', ''),
            'redirect_uri' => $this->getCallbackUrl(self::PROVIDER_GOOGLE),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'offline',
            'prompt' => 'select_account',
        ];

        // TASK-AUTH-002: Generate and store state parameter for CSRF protection
        if ($session) {
            $state = bin2hex(random_bytes(16));
            $session->write('oauth_state', $state);
            $params['state'] = $state;
        }

        return self::GOOGLE_AUTH_URL . '?' . http_build_query($params);
    }

    /**
     * Get GitHub OAuth authorization URL.
     *
     * @param \Cake\Http\Session|null $session The session to store state in (TASK-AUTH-002).
     * @return string
     */
    private function getGitHubAuthUrl(?Session $session = null): string
    {
        $params = [
            'client_id' => env('GITHUB_CLIENT_ID', ''),
            'redirect_uri' => $this->getCallbackUrl(self::PROVIDER_GITHUB),
            'scope' => 'user:email read:user',
        ];

        // TASK-AUTH-002: Generate and store state parameter for CSRF protection
        if ($session) {
            $state = bin2hex(random_bytes(16));
            $session->write('oauth_state', $state);
            $params['state'] = $state;
        }

        return self::GITHUB_AUTH_URL . '?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token.
     *
     * @param string $provider The OAuth provider.
     * @param string $code The authorization code.
     * @return string|null The access token, or null on failure.
     */
    private function exchangeCodeForToken(string $provider, string $code): ?string
    {
        if ($provider === self::PROVIDER_GOOGLE) {
            return $this->exchangeGoogleToken($code);
        }

        if ($provider === self::PROVIDER_GITHUB) {
            return $this->exchangeGitHubToken($code);
        }

        return null;
    }

    /**
     * Exchange Google authorization code for token.
     *
     * @param string $code The authorization code.
     * @return string|null
     */
    private function exchangeGoogleToken(string $code): ?string
    {
        $response = $this->httpClient->post(self::GOOGLE_TOKEN_URL, [
            'client_id' => env('GOOGLE_CLIENT_ID', ''),
            'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->getCallbackUrl(self::PROVIDER_GOOGLE),
        ]);

        if (!$response->isOk()) {
            $this->log('Google token exchange failed: ' . $response->getStringBody(), 'error');
            return null;
        }

        $data = $response->getJson();
        return $data['access_token'] ?? null;
    }

    /**
     * Exchange GitHub authorization code for token.
     *
     * @param string $code The authorization code.
     * @return string|null
     */
    private function exchangeGitHubToken(string $code): ?string
    {
        $response = $this->httpClient->post(self::GITHUB_TOKEN_URL, json_encode([
            'client_id' => env('GITHUB_CLIENT_ID', ''),
            'client_secret' => env('GITHUB_CLIENT_SECRET', ''),
            'code' => $code,
            'redirect_uri' => $this->getCallbackUrl(self::PROVIDER_GITHUB),
        ]), [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        if (!$response->isOk()) {
            $this->log('GitHub token exchange failed: ' . $response->getStringBody(), 'error');
            return null;
        }

        $data = $response->getJson();
        return $data['access_token'] ?? null;
    }

    /**
     * Fetch user info from the OAuth provider.
     *
     * @param string $provider The OAuth provider.
     * @param string $accessToken The access token.
     * @return array|null Array with 'id', 'email', 'name' keys.
     */
    private function fetchUserInfo(string $provider, string $accessToken): ?array
    {
        if ($provider === self::PROVIDER_GOOGLE) {
            return $this->fetchGoogleUserInfo($accessToken);
        }

        if ($provider === self::PROVIDER_GITHUB) {
            return $this->fetchGitHubUserInfo($accessToken);
        }

        return null;
    }

    /**
     * Fetch Google user info.
     *
     * @param string $accessToken The access token.
     * @return array|null
     */
    private function fetchGoogleUserInfo(string $accessToken): ?array
    {
        $response = $this->httpClient->get(self::GOOGLE_USERINFO_URL, [], [
            'headers' => ['Authorization' => "Bearer {$accessToken}"],
        ]);

        if (!$response->isOk()) {
            return null;
        }

        $data = $response->getJson();

        return [
            'id' => $data['id'] ?? null,
            'email' => $data['email'] ?? null,
            'name' => $data['name'] ?? ($data['given_name'] ?? 'User'),
        ];
    }

    /**
     * Fetch GitHub user info.
     *
     * @param string $accessToken The access token.
     * @return array|null
     */
    private function fetchGitHubUserInfo(string $accessToken): ?array
    {
        $headers = [
            'headers' => [
                'Authorization' => "Bearer {$accessToken}",
                'Accept' => 'application/json',
                'User-Agent' => 'ISP-Status-Page',
            ],
        ];

        // Get user profile
        $response = $this->httpClient->get(self::GITHUB_USERINFO_URL, [], $headers);
        if (!$response->isOk()) {
            return null;
        }

        $data = $response->getJson();
        $email = $data['email'] ?? null;

        // If email is not public, fetch from emails endpoint
        if (empty($email)) {
            $emailResponse = $this->httpClient->get(self::GITHUB_EMAILS_URL, [], $headers);
            if ($emailResponse->isOk()) {
                $emails = $emailResponse->getJson();
                foreach ($emails as $emailEntry) {
                    if (!empty($emailEntry['primary']) && !empty($emailEntry['verified'])) {
                        $email = $emailEntry['email'];
                        break;
                    }
                }
            }
        }

        return [
            'id' => (string)($data['id'] ?? ''),
            'email' => $email,
            'name' => $data['name'] ?? $data['login'] ?? 'User',
        ];
    }

    /**
     * Find an existing user or create a new one based on OAuth data.
     *
     * If a user with the same email exists, link the OAuth credentials.
     * If no user exists, create a new user and organization.
     *
     * @param string $provider The OAuth provider.
     * @param array $providerUser The provider user data (id, email, name).
     * @return \App\Model\Entity\User|null
     */
    private function findOrCreateUser(string $provider, array $providerUser): ?User
    {
        $usersTable = $this->fetchTable('Users');

        // First, check if we already have a user linked with this OAuth provider+id
        $existingOAuthUser = $usersTable->find()
            ->where([
                'oauth_provider' => $provider,
                'oauth_id' => $providerUser['id'],
            ])
            ->first();

        if ($existingOAuthUser) {
            return $existingOAuthUser;
        }

        // Check if a user with this email already exists
        $existingEmailUser = $usersTable->find()
            ->where(['email' => $providerUser['email']])
            ->first();

        if ($existingEmailUser) {
            // TASK-AUTH-019: Don't auto-link OAuth if user already has a different provider
            if (!empty($existingEmailUser->oauth_provider) && $existingEmailUser->oauth_provider !== $provider) {
                // User already linked to a different OAuth provider -- don't overwrite
                $this->log(
                    "OAuth {$provider} login: user {$existingEmailUser->email} already linked to {$existingEmailUser->oauth_provider}, logging in without re-linking",
                    'info'
                );
                return $existingEmailUser;
            }

            // TASK-AUTH-019: If user has no OAuth provider, don't auto-link;
            // just log them in and suggest manual linking from their profile.
            if (empty($existingEmailUser->oauth_provider)) {
                $providerName = ucfirst($provider);
                $this->pendingLinkMessage = "We found your existing account. Sign in with your password to link your {$providerName} account from your profile settings.";
                $this->log("OAuth {$provider} login: existing user {$existingEmailUser->email} found, not auto-linking", 'info');
                return $existingEmailUser;
            }

            // Same provider -- update the oauth_id in case it changed
            $existingEmailUser->set('oauth_id', $providerUser['id']);
            if ($usersTable->save($existingEmailUser)) {
                $this->log("Updated OAuth {$provider} ID for existing user: {$existingEmailUser->email}", 'info');
                return $existingEmailUser;
            }

            return null;
        }

        // Create a new user + organization
        return $this->createNewOAuthUser($provider, $providerUser);
    }

    /**
     * Create a new user and organization from OAuth data.
     *
     * @param string $provider The OAuth provider.
     * @param array $providerUser The provider user data.
     * @return \App\Model\Entity\User|null
     */
    private function createNewOAuthUser(string $provider, array $providerUser): ?User
    {
        $usersTable = $this->fetchTable('Users');
        $organizationsTable = $this->fetchTable('Organizations');
        $orgUsersTable = $this->fetchTable('OrganizationUsers');

        $connection = $usersTable->getConnection();

        try {
            return $connection->transactional(function () use (
                $usersTable,
                $organizationsTable,
                $orgUsersTable,
                $provider,
                $providerUser
            ) {
                // Generate a username from the name or email
                $username = $this->generateUsername($providerUser['name'], $providerUser['email']);

                // Create user (no password needed for OAuth-only accounts)
                // Only mass-assign safe fields; set sensitive fields directly
                $user = $usersTable->newEntity([
                    'username' => $username,
                    'email' => $providerUser['email'],
                    'password' => bin2hex(random_bytes(32)), // Random password (won't be used)
                ]);
                $user->set('role', 'admin');
                $user->set('active', true);
                $user->set('email_verified', true);
                $user->set('oauth_provider', $provider);
                $user->set('oauth_id', $providerUser['id']);

                if (!$usersTable->save($user)) {
                    $this->log('Failed to create OAuth user: ' . json_encode($user->getErrors()), 'error');
                    return null;
                }

                // Create organization
                $orgSlug = $this->generateOrgSlug($providerUser['name'], $providerUser['email']);
                $organization = $organizationsTable->newEntity([
                    'name' => $providerUser['name'] . "'s Organization",
                    'slug' => $orgSlug,
                    'plan' => 'free',
                    'active' => true,
                ]);

                if (!$organizationsTable->save($organization)) {
                    $this->log('Failed to create organization for OAuth user', 'error');
                    return null;
                }

                // Link user to organization as owner
                $orgUser = $orgUsersTable->newEntity([
                    'organization_id' => $organization->id,
                    'user_id' => $user->id,
                    'role' => 'owner',
                ]);

                if (!$orgUsersTable->save($orgUser)) {
                    $this->log('Failed to create organization user link', 'error');
                    return null;
                }

                $this->log("Created new OAuth user via {$provider}: {$user->email}", 'info');

                return $user;
            });
        } catch (\Exception $e) {
            $this->log("Error creating OAuth user: {$e->getMessage()}", 'error');
            return null;
        }
    }

    /**
     * Generate a unique username from name/email.
     *
     * @param string $name The user's name.
     * @param string $email The user's email.
     * @return string
     */
    private function generateUsername(string $name, string $email): string
    {
        // Start with the name, lowercase, alphanumeric only
        $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
        if (empty($base)) {
            // Fall back to the local part of the email
            $base = strtolower(explode('@', $email)[0]);
            $base = preg_replace('/[^a-zA-Z0-9]/', '', $base);
        }

        $usersTable = $this->fetchTable('Users');
        $username = $base;
        $counter = 1;

        while ($usersTable->exists(['username' => $username])) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Generate a unique organization slug.
     *
     * @param string $name The user's name.
     * @param string $email The user's email.
     * @return string
     */
    private function generateOrgSlug(string $name, string $email): string
    {
        $base = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '-', $name));
        $base = preg_replace('/-+/', '-', trim($base, '-'));
        if (empty($base)) {
            $base = strtolower(explode('@', $email)[0]);
            $base = preg_replace('/[^a-zA-Z0-9-]/', '-', $base);
        }

        $organizationsTable = $this->fetchTable('Organizations');
        $slug = $base;
        $counter = 1;

        while ($organizationsTable->exists(['slug' => $slug])) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get the callback URL for a provider.
     *
     * @param string $provider The OAuth provider.
     * @return string
     */
    private function getCallbackUrl(string $provider): string
    {
        return \Cake\Routing\Router::url("/auth/{$provider}/callback", true);
    }
}
