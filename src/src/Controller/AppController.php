<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Http\Exception\ForbiddenException;
use Cake\I18n\DateTime;
use Cake\I18n\I18n;
use App\Service\PermissionService;
use App\Service\SettingService;
use App\Tenant\TenantContext;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/4/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
    /**
     * The current organization (set from TenantContext).
     *
     * @var array|null
     */
    protected ?array $currentOrganization = null;

    /**
     * The current user's role in the current organization.
     *
     * @var string|null
     */
    protected ?string $currentUserRole = null;

    /**
     * Permission service instance.
     *
     * @var \App\Service\PermissionService|null
     */
    protected ?PermissionService $permissionService = null;

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('FormProtection');`
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');
        $this->loadComponent('Authentication.Authentication');

        // Determine language and timezone: user → org → system default
        $language = 'pt_BR';
        $timezone = 'UTC';

        try {
            $settingService = new SettingService();
            $language = $settingService->get('site_language', 'pt_BR');
        } catch (\Exception $e) {
            // Fallback to default language if settings fail to load
        }

        // Load current organization from TenantContext (set by TenantMiddleware)
        if (TenantContext::isSet()) {
            $this->currentOrganization = TenantContext::getCurrentOrganization();
            $this->set('currentOrganization', $this->currentOrganization);

            // Organization-level language and timezone (TASK-1102)
            if (!empty($this->currentOrganization['language'])) {
                $language = $this->currentOrganization['language'];
            }
            if (!empty($this->currentOrganization['timezone'])) {
                $timezone = $this->currentOrganization['timezone'];
            }

            // Load the current user's role in this organization
            $this->permissionService = new PermissionService();
            $identity = $this->request->getAttribute('identity');
            if ($identity && $this->currentOrganization) {
                $userId = (int)$identity->getIdentifier();
                $orgId = (int)$this->currentOrganization['id'];
                $this->currentUserRole = $this->permissionService->getUserRole($userId, $orgId);
                $this->set('currentUserRole', $this->currentUserRole);

                // Per-user language and timezone override (TASK-1100)
                try {
                    $usersTable = $this->fetchTable('Users');
                    $user = $usersTable->find()
                        ->select(['language', 'timezone', 'is_super_admin'])
                        ->where(['id' => $userId])
                        ->disableHydration()
                        ->first();

                    if ($user) {
                        if (!empty($user['language'])) {
                            $language = $user['language'];
                        }
                        if (!empty($user['timezone'])) {
                            $timezone = $user['timezone'];
                        }
                        if (!empty($user['is_super_admin'])) {
                            $this->set('isSuperAdmin', true);
                        }
                    }
                } catch (\Exception $e) {
                    // User table may not have new columns yet; ignore
                }
            }
        }

        // Apply locale and timezone
        I18n::setLocale($language);
        if ($timezone && $timezone !== 'UTC') {
            date_default_timezone_set($timezone);
        }

        /*
         * Enable the following component for recommended CakePHP form protection settings.
         * see https://book.cakephp.org/4/en/controllers/components/form-protection.html
         */
        //$this->loadComponent('FormProtection');
    }

    /**
     * Before filter callback.
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);

        // Allow public access to the display action (status page) and home (root redirect)
        // Login and logout will be configured in UsersController
        $this->Authentication->addUnauthenticatedActions(['display', 'home', 'terms', 'privacy']);
    }

    /**
     * Check if the current user has permission to perform an action.
     * Throws ForbiddenException if not authorized.
     *
     * @param string $action The action to check (use PermissionService::ACTION_* constants).
     * @return void
     * @throws \Cake\Http\Exception\ForbiddenException If user is not authorized.
     */
    protected function checkPermission(string $action): void
    {
        if ($this->currentUserRole === null) {
            throw new ForbiddenException(__('You do not have access to this organization.'));
        }

        if ($this->permissionService === null) {
            $this->permissionService = new PermissionService();
        }

        if (!$this->permissionService->canWithRole($this->currentUserRole, $action)) {
            throw new ForbiddenException(__('You do not have permission to perform this action.'));
        }
    }
}
