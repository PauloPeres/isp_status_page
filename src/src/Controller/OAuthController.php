<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\OAuthService;

/**
 * OAuthController
 *
 * Handles OAuth/social login redirect and callback flows
 * for Google and GitHub providers (TASK-704).
 *
 * @property \App\Model\Table\UsersTable $Users
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
     * Before filter — allow unauthenticated access.
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->Authentication->addUnauthenticatedActions(['redirectToProvider', 'callback']);
    }

    /**
     * Redirect user to OAuth provider's authorization page.
     *
     * @param string|null $provider The OAuth provider (google, github).
     * @return \Cake\Http\Response
     */
    public function redirectToProvider(?string $provider = null)
    {
        if (!$provider || !$this->oauthService->isValidProvider($provider)) {
            $this->Flash->error(__('Unsupported OAuth provider.'));
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        try {
            $authUrl = $this->oauthService->getAuthorizationUrl($provider);
            return $this->response->withHeader('Location', $authUrl)->withStatus(302);
        } catch (\Exception $e) {
            $this->log("OAuth redirect error for {$provider}: {$e->getMessage()}", 'error');
            $this->Flash->error(__('Unable to connect to {0}. Please try again.', ucfirst($provider)));
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }
    }

    /**
     * Handle OAuth callback from provider.
     *
     * @param string|null $provider The OAuth provider (google, github).
     * @return \Cake\Http\Response|null
     */
    public function callback(?string $provider = null)
    {
        if (!$provider || !$this->oauthService->isValidProvider($provider)) {
            $this->Flash->error(__('Unsupported OAuth provider.'));
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        // Check for error from provider (user denied, etc.)
        $error = $this->request->getQuery('error');
        if (!empty($error)) {
            $errorDescription = $this->request->getQuery('error_description', 'Authentication was cancelled.');
            $this->Flash->error(__('Authentication failed: {0}', $errorDescription));
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        // Handle the callback
        $queryParams = $this->request->getQueryParams();
        $user = $this->oauthService->handleCallback($provider, $queryParams);

        if (!$user) {
            $this->Flash->error(__('Unable to authenticate with {0}. Please try again.', ucfirst($provider)));
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }

        // Log the user in via the Authentication plugin
        $this->Authentication->setIdentity($user);

        $this->Flash->success(__('Successfully signed in with {0}!', ucfirst($provider)));

        return $this->redirect(['controller' => 'Admin', 'action' => 'index']);
    }
}
