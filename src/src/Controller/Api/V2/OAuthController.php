<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

use App\Service\JwtService;
use App\Service\OAuthService;

/**
 * OAuthController
 *
 * Handles OAuth redirect and callback flows for the Angular frontend.
 * Returns authorization URLs as JSON and handles callbacks by redirecting
 * to the Angular app with JWT tokens in the URL fragment.
 */
class OAuthController extends AppController
{
    /**
     * OAuth service instance.
     *
     * @var \App\Service\OAuthService
     */
    private OAuthService $oauthService;

    /**
     * Initialize controller.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->oauthService = new OAuthService();
    }

    /**
     * GET /api/v2/auth/oauth/{provider}/redirect
     *
     * Returns the authorization URL for the frontend to redirect to.
     *
     * @param string $provider The OAuth provider (google, github, microsoft).
     * @return void
     */
    public function redirect(string $provider): void
    {
        if (!$this->oauthService->isValidProvider($provider)) {
            $this->error("Unsupported OAuth provider: {$provider}", 422);

            return;
        }

        $url = $this->oauthService->getApiAuthorizationUrl($provider, $this->request->getSession());

        if (!$url) {
            $this->error("{$provider} OAuth is not configured", 422);

            return;
        }

        $this->success(['authorization_url' => $url]);
    }

    /**
     * GET /api/v2/auth/oauth/{provider}/callback
     *
     * Handles the OAuth callback from the provider.
     * Exchanges the authorization code for user info, generates JWT tokens,
     * and redirects to the Angular app with tokens in the URL fragment.
     *
     * @param string $provider The OAuth provider (google, github, microsoft).
     * @return \Cake\Http\Response|null
     */
    public function callback(string $provider)
    {
        $code = $this->request->getQuery('code');
        $state = $this->request->getQuery('state');
        $error = $this->request->getQuery('error');

        // Check for error from provider
        if (!empty($error)) {
            return $this->response
                ->withHeader('Location', '/app/login?oauth_error=provider_denied')
                ->withStatus(302);
        }

        if (!$code) {
            return $this->response
                ->withHeader('Location', '/app/login?oauth_error=no_code')
                ->withStatus(302);
        }

        if (!$this->oauthService->isValidProvider($provider)) {
            return $this->response
                ->withHeader('Location', '/app/login?oauth_error=invalid_provider')
                ->withStatus(302);
        }

        // Verify state parameter
        $queryParams = $this->request->getQueryParams();
        $session = $this->request->getSession();
        if (!$this->oauthService->verifyState($queryParams, $session)) {
            return $this->response
                ->withHeader('Location', '/app/login?oauth_error=invalid_state')
                ->withStatus(302);
        }

        // Build redirect URI matching what was used in the authorization URL
        $redirectUri = rtrim((string)env('APP_URL', 'http://localhost:8765'), '/')
            . "/api/v2/auth/oauth/{$provider}/callback";

        // Handle callback -- find or create user
        $user = $this->oauthService->handleApiCallback($provider, [
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ]);

        if (!$user) {
            return $this->response
                ->withHeader('Location', '/app/login?oauth_error=auth_failed')
                ->withStatus(302);
        }

        // Get organization
        $orgUser = $this->fetchTable('OrganizationUsers')->find()
            ->where(['user_id' => $user->id])
            ->first();

        $orgId = $orgUser ? (int)$orgUser->organization_id : 0;
        $role = $orgUser ? (string)$orgUser->role : 'owner';

        // Generate JWT tokens
        $jwtService = new JwtService();
        $accessToken = $jwtService->generateAccessToken(
            (int)$user->id,
            $orgId,
            $role,
            (bool)$user->is_super_admin
        );
        $refreshToken = $jwtService->generateRefreshToken((int)$user->id);

        // Redirect to Angular with tokens in URL fragment (not query params for security)
        return $this->response
            ->withHeader('Location', "/app/oauth-callback#access_token={$accessToken}&refresh_token={$refreshToken}")
            ->withStatus(302);
    }
}
