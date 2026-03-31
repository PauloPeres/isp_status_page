<?php
/**
 * Routes configuration.
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * It's loaded within the context of `Application::routes()` method which
 * receives a `RouteBuilder` instance `$routes` as method argument.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

/*
 * This file is loaded in the context of the `Application` class.
  * So you can use  `$this` to reference the application class instance
  * if required.
 */
return function (RouteBuilder $routes): void {
    /*
     * The default class to use for all routes
     *
     * The following route classes are supplied with CakePHP and are appropriate
     * to set as the default:
     *
     * - Route
     * - InflectedRoute
     * - DashedRoute
     *
     * If no call is made to `Router::defaultRouteClass()`, the class used is
     * `Route` (`Cake\Routing\Route\Route`)
     *
     * Note that `Route` does not do any inflections on URLs which will result in
     * inconsistently cased URLs when used with `{plugin}`, `{controller}` and
     * `{action}` markers.
     */
    $routes->setRouteClass(DashedRoute::class);

    $routes->scope('/', function (RouteBuilder $builder): void {
        /*
         * Here, we are connecting '/' (base path) to redirect to login or admin
         * based on authentication status
         */
        $builder->connect('/', ['controller' => 'Pages', 'action' => 'home']);

        /*
         * Incident acknowledgement routes (TASK-260)
         */
        // Public: token-based acknowledge (no auth)
        $builder->connect(
            '/incidents/acknowledge/{id}/{token}',
            ['controller' => 'Incidents', 'action' => 'acknowledge'],
            ['pass' => ['id', 'token'], 'id' => '\d+', 'token' => '[a-f0-9]{64}']
        );
        // Admin: authenticated acknowledge
        $builder->connect(
            '/incidents/{id}/acknowledge-admin',
            ['controller' => 'Incidents', 'action' => 'acknowledgeAdmin'],
            ['pass' => ['id'], 'id' => '\d+']
        );
        // Incident timeline update
        $builder->connect(
            '/incidents/{id}/update',
            ['controller' => 'Incidents', 'action' => 'addUpdate'],
            ['pass' => ['id'], 'id' => '\d+']
        );

        /*
         * OAuth/Social login routes (TASK-704)
         */
        $builder->connect(
            '/auth/{provider}/redirect',
            ['controller' => 'OAuth', 'action' => 'redirectToProvider'],
            ['pass' => ['provider'], 'provider' => '(google|github)']
        );
        $builder->connect(
            '/auth/{provider}/callback',
            ['controller' => 'OAuth', 'action' => 'callback'],
            ['pass' => ['provider'], 'provider' => '(google|github)']
        );

        /*
         * Public registration routes (TASK-700)
         */
        $builder->connect(
            '/register',
            ['controller' => 'Registration', 'action' => 'register']
        );
        $builder->connect(
            '/verify-email/*',
            ['controller' => 'Registration', 'action' => 'verifyEmail']
        );
        $builder->connect(
            '/resend-verification',
            ['controller' => 'Registration', 'action' => 'resendVerification']
        );

        /*
         * Onboarding routes (TASK-701)
         */
        $builder->connect(
            '/onboarding/step1',
            ['controller' => 'Onboarding', 'action' => 'step1']
        );
        $builder->connect(
            '/onboarding/step2',
            ['controller' => 'Onboarding', 'action' => 'step2']
        );
        $builder->connect(
            '/onboarding/step3',
            ['controller' => 'Onboarding', 'action' => 'step3']
        );
        $builder->connect(
            '/onboarding/complete',
            ['controller' => 'Onboarding', 'action' => 'complete']
        );

        /*
         * Badge routes (TASK-1005)
         */
        $builder->connect(
            '/badges/{token}/uptime.svg',
            ['controller' => 'Badges', 'action' => 'uptime'],
            ['pass' => ['token'], 'token' => '[a-f0-9]{32,64}']
        );
        $builder->connect(
            '/badges/{token}/status.svg',
            ['controller' => 'Badges', 'action' => 'status'],
            ['pass' => ['token'], 'token' => '[a-f0-9]{32,64}']
        );
        $builder->connect(
            '/badges/{token}/response-time.svg',
            ['controller' => 'Badges', 'action' => 'responseTime'],
            ['pass' => ['token'], 'token' => '[a-f0-9]{32,64}']
        );

        /*
         * Billing routes (TASK-802)
         */
        $builder->connect(
            '/billing',
            ['controller' => 'Billing', 'action' => 'plans']
        );
        $builder->connect(
            '/billing/plans',
            ['controller' => 'Billing', 'action' => 'plans']
        );
        $builder->connect(
            '/billing/checkout/{planSlug}',
            ['controller' => 'Billing', 'action' => 'checkout'],
            ['pass' => ['planSlug'], 'planSlug' => '[a-z]+']
        );
        $builder->connect(
            '/billing/portal',
            ['controller' => 'Billing', 'action' => 'portal']
        );
        $builder->connect(
            '/billing/success',
            ['controller' => 'Billing', 'action' => 'success']
        );
        $builder->connect(
            '/billing/cancel',
            ['controller' => 'Billing', 'action' => 'cancel']
        );

        /*
         * Two-Factor Authentication routes (TASK-AUTH-MFA)
         */
        $builder->connect(
            '/two-factor/setup',
            ['controller' => 'TwoFactor', 'action' => 'setup']
        );
        $builder->connect(
            '/two-factor/verify',
            ['controller' => 'TwoFactor', 'action' => 'verify']
        );
        $builder->connect(
            '/two-factor/disable',
            ['controller' => 'TwoFactor', 'action' => 'disable']
        );
        $builder->connect(
            '/two-factor/recovery-codes',
            ['controller' => 'TwoFactor', 'action' => 'recoveryCodes']
        );

        /*
         * Embeddable status widget routes (P3-012)
         */
        $builder->connect(
            '/widget/status/{slug}',
            ['controller' => 'Widget', 'action' => 'status'],
            ['pass' => ['slug'], 'slug' => '[a-z0-9][a-z0-9\-]*[a-z0-9]']
        );
        $builder->connect(
            '/widget/status/{slug}.js',
            ['controller' => 'Widget', 'action' => 'statusJs'],
            ['pass' => ['slug'], 'slug' => '[a-z0-9][a-z0-9\-]*[a-z0-9]']
        );

        /*
         * Public status page route
         */
        $builder->connect(
            '/s/{slug}',
            ['controller' => 'StatusPages', 'action' => 'show'],
            ['pass' => ['slug'], 'slug' => '[a-z0-9][a-z0-9\-]*[a-z0-9]']
        );

        /*
         * Heartbeat ping route (TASK-1000)
         */
        $builder->connect(
            '/heartbeat/{token}',
            ['controller' => 'Heartbeat', 'action' => 'ping'],
            ['pass' => ['token'], 'token' => '[a-f0-9]{64}']
        );

        /*
         * API documentation route (TASK-903)
         */
        $builder->connect(
            '/api/docs',
            ['controller' => 'Docs', 'action' => 'index', 'prefix' => 'Api']
        );

        /*
         * Legal pages (Terms of Service, Privacy Policy)
         */
        $builder->connect(
            '/terms',
            ['controller' => 'Pages', 'action' => 'terms']
        );
        $builder->connect(
            '/privacy',
            ['controller' => 'Pages', 'action' => 'privacy']
        );

        /*
         * Stripe webhook route (TASK-803)
         */
        $builder->connect(
            '/webhooks/stripe',
            ['controller' => 'Webhooks', 'action' => 'stripe']
        );

        /*
         * Invitation routes (TASK-702)
         */
        $builder->connect(
            '/invite/{token}',
            ['controller' => 'Invitations', 'action' => 'accept'],
            ['pass' => ['token'], 'token' => '[a-f0-9]{64}']
        );
        $builder->connect(
            '/invitations',
            ['controller' => 'Invitations', 'action' => 'index']
        );
        $builder->connect(
            '/invitations/send',
            ['controller' => 'Invitations', 'action' => 'send']
        );
        $builder->connect(
            '/invitations/revoke/{id}',
            ['controller' => 'Invitations', 'action' => 'revoke'],
            ['pass' => ['id'], 'id' => '\d+']
        );

        /*
         * Organization switcher routes (TASK-705)
         */
        $builder->connect(
            '/organizations/select',
            ['controller' => 'OrganizationSwitcher', 'action' => 'select']
        );
        $builder->connect(
            '/organizations/switch/{orgId}',
            ['controller' => 'OrganizationSwitcher', 'action' => 'switch'],
            ['pass' => ['orgId'], 'orgId' => '\d+']
        );

        /*
         * RSS Feed route (P3-013)
         */
        $builder->connect(
            '/feed/incidents.rss',
            ['controller' => 'Feed', 'action' => 'incidents']
        );

        /*
         * SLA Tracking routes (P4-004)
         */
        $builder->connect(
            '/sla',
            ['controller' => 'Sla', 'action' => 'index']
        );
        $builder->connect(
            '/sla/add',
            ['controller' => 'Sla', 'action' => 'add']
        );
        $builder->connect(
            '/sla/edit/{id}',
            ['controller' => 'Sla', 'action' => 'edit'],
            ['pass' => ['id'], 'id' => '\d+']
        );
        $builder->connect(
            '/sla/delete/{id}',
            ['controller' => 'Sla', 'action' => 'delete'],
            ['pass' => ['id'], 'id' => '\d+']
        );
        $builder->connect(
            '/sla/report/{id}',
            ['controller' => 'Sla', 'action' => 'report'],
            ['pass' => ['id'], 'id' => '\d+']
        );
        $builder->connect(
            '/sla/export/{id}',
            ['controller' => 'Sla', 'action' => 'exportReport'],
            ['pass' => ['id'], 'id' => '\d+']
        );

        /*
         * Escalation Policies routes (P4-007)
         */
        $builder->connect(
            '/escalation-policies',
            ['controller' => 'EscalationPolicies', 'action' => 'index']
        );
        $builder->connect(
            '/escalation-policies/add',
            ['controller' => 'EscalationPolicies', 'action' => 'add']
        );
        $builder->connect(
            '/escalation-policies/edit/{id}',
            ['controller' => 'EscalationPolicies', 'action' => 'edit'],
            ['pass' => ['id'], 'id' => '\d+']
        );
        $builder->connect(
            '/escalation-policies/delete/{id}',
            ['controller' => 'EscalationPolicies', 'action' => 'delete'],
            ['pass' => ['id'], 'id' => '\d+']
        );
        $builder->connect(
            '/escalation-policies/view/{id}',
            ['controller' => 'EscalationPolicies', 'action' => 'view'],
            ['pass' => ['id'], 'id' => '\d+']
        );

        /*
         * Scheduled Reports routes (P4-010)
         */
        $builder->connect(
            '/scheduled-reports',
            ['controller' => 'ScheduledReports', 'action' => 'index']
        );
        $builder->connect(
            '/scheduled-reports/add',
            ['controller' => 'ScheduledReports', 'action' => 'add']
        );
        $builder->connect(
            '/scheduled-reports/edit/{id}',
            ['controller' => 'ScheduledReports', 'action' => 'edit'],
            ['pass' => ['id'], 'id' => '\d+']
        );
        $builder->connect(
            '/scheduled-reports/delete/{id}',
            ['controller' => 'ScheduledReports', 'action' => 'delete'],
            ['pass' => ['id'], 'id' => '\d+']
        );
        $builder->connect(
            '/scheduled-reports/preview/{id}',
            ['controller' => 'ScheduledReports', 'action' => 'preview'],
            ['pass' => ['id'], 'id' => '\d+']
        );
        $builder->connect(
            '/scheduled-reports/send-now/{id}',
            ['controller' => 'ScheduledReports', 'action' => 'sendNow'],
            ['pass' => ['id'], 'id' => '\d+']
        );

        /*
         * Monitor bulk operations routes (P2-013)
         */
        $builder->connect(
            '/monitors/bulk-action',
            ['controller' => 'Monitors', 'action' => 'bulkAction']
        );
        $builder->connect(
            '/monitors/import',
            ['controller' => 'Monitors', 'action' => 'import']
        );

        /*
         * SPA catch-all — Angular app served at /app/* (TASK-NG-050)
         * Must be BEFORE the fallbacks so /app/* doesn't hit CakePHP controllers.
         */
        $builder->connect('/app', ['controller' => 'Spa', 'action' => 'index']);
        $builder->connect('/app/*', ['controller' => 'Spa', 'action' => 'index']);

        /*
         * ...and connect the rest of 'Pages' controller's URLs.
         */
        $builder->connect('/pages/*', 'Pages::display');

        /*
         * Connect catchall routes for all controllers.
         *
         * The `fallbacks` method is a shortcut for
         *
         * ```
         * $builder->connect('/{controller}', ['action' => 'index']);
         * $builder->connect('/{controller}/{action}/*', []);
         * ```
         *
         * You can remove these routes once you've connected the
         * routes you want in your application.
         */
        $builder->fallbacks();
    });

    /*
     * Super Admin routes (TASK-SA-005)
     */
    $routes->prefix('SuperAdmin', ['path' => '/super-admin'], function (RouteBuilder $builder): void {
        $builder->connect('/', ['controller' => 'Dashboard', 'action' => 'index']);
        $builder->connect('/organizations', ['controller' => 'Organizations', 'action' => 'index']);
        $builder->connect('/organizations/{id}', ['controller' => 'Organizations', 'action' => 'view'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/organizations/{id}/impersonate', ['controller' => 'Organizations', 'action' => 'impersonate'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/organizations/stop-impersonation', ['controller' => 'Organizations', 'action' => 'stopImpersonation']);
        $builder->connect('/users', ['controller' => 'Users', 'action' => 'index']);
        $builder->connect('/users/{id}', ['controller' => 'Users', 'action' => 'view'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/revenue', ['controller' => 'Revenue', 'action' => 'index']);
        $builder->connect('/health', ['controller' => 'Health', 'action' => 'index']);
        $builder->connect('/settings', ['controller' => 'Settings', 'action' => 'index']);
        $builder->connect('/settings/save', ['controller' => 'Settings', 'action' => 'save']);
        $builder->connect('/settings/test-email', ['controller' => 'Settings', 'action' => 'testEmail']);
        $builder->connect('/settings/test-ftp', ['controller' => 'Settings', 'action' => 'testFtp']);
        $builder->fallbacks();
    });

    /*
     * REST API v1 routes (TASK-902)
     *
     * Auth handled by ApiAuthMiddleware; CSRF skipped for /api/* in Application.php.
     */
    $routes->scope('/api/v1', function (RouteBuilder $builder): void {
        $builder->setExtensions(['json']);

        // --- Monitors ---
        $builder->connect('/monitors', ['controller' => 'Monitors', 'action' => 'index', 'prefix' => 'Api/V1', '_method' => 'GET']);
        $builder->connect('/monitors', ['controller' => 'Monitors', 'action' => 'add', 'prefix' => 'Api/V1', '_method' => 'POST']);
        $builder->connect('/monitors/{id}', ['controller' => 'Monitors', 'action' => 'view', 'prefix' => 'Api/V1', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/monitors/{id}', ['controller' => 'Monitors', 'action' => 'edit', 'prefix' => 'Api/V1', '_method' => 'PUT'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/monitors/{id}', ['controller' => 'Monitors', 'action' => 'delete', 'prefix' => 'Api/V1', '_method' => 'DELETE'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/monitors/{id}/checks', ['controller' => 'Monitors', 'action' => 'checks', 'prefix' => 'Api/V1', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/monitors/{id}/pause', ['controller' => 'Monitors', 'action' => 'pause', 'prefix' => 'Api/V1', '_method' => 'POST'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/monitors/{id}/resume', ['controller' => 'Monitors', 'action' => 'resume', 'prefix' => 'Api/V1', '_method' => 'POST'], ['pass' => ['id'], 'id' => '\d+']);

        // --- Incidents ---
        $builder->connect('/incidents', ['controller' => 'Incidents', 'action' => 'index', 'prefix' => 'Api/V1', '_method' => 'GET']);
        $builder->connect('/incidents', ['controller' => 'Incidents', 'action' => 'add', 'prefix' => 'Api/V1', '_method' => 'POST']);
        $builder->connect('/incidents/{id}', ['controller' => 'Incidents', 'action' => 'view', 'prefix' => 'Api/V1', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/incidents/{id}', ['controller' => 'Incidents', 'action' => 'edit', 'prefix' => 'Api/V1', '_method' => 'PUT'], ['pass' => ['id'], 'id' => '\d+']);

        // --- Checks (read-only) ---
        $builder->connect('/checks', ['controller' => 'Checks', 'action' => 'index', 'prefix' => 'Api/V1', '_method' => 'GET']);
        $builder->connect('/checks/{id}', ['controller' => 'Checks', 'action' => 'view', 'prefix' => 'Api/V1', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);

        // --- Alert Rules ---
        $builder->connect('/alert-rules', ['controller' => 'AlertRules', 'action' => 'index', 'prefix' => 'Api/V1', '_method' => 'GET']);
        $builder->connect('/alert-rules', ['controller' => 'AlertRules', 'action' => 'add', 'prefix' => 'Api/V1', '_method' => 'POST']);
        $builder->connect('/alert-rules/{id}', ['controller' => 'AlertRules', 'action' => 'view', 'prefix' => 'Api/V1', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/alert-rules/{id}', ['controller' => 'AlertRules', 'action' => 'edit', 'prefix' => 'Api/V1', '_method' => 'PUT'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/alert-rules/{id}', ['controller' => 'AlertRules', 'action' => 'delete', 'prefix' => 'Api/V1', '_method' => 'DELETE'], ['pass' => ['id'], 'id' => '\d+']);
    });

    /*
     * REST API v2 routes (TASK-NG-001 / TASK-NG-002)
     *
     * JWT authentication handled by JwtAuthMiddleware; CSRF skipped for /api/* in Application.php.
     * Auth endpoints (login, refresh) do not require JWT.
     */
    $routes->scope('/api/v2', function (RouteBuilder $builder): void {
        $builder->setExtensions(['json']);

        // --- Auth (no JWT required for login/refresh/register) ---
        $builder->connect('/auth/register', ['controller' => 'Auth', 'action' => 'register', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/auth/login', ['controller' => 'Auth', 'action' => 'login', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/auth/refresh', ['controller' => 'Auth', 'action' => 'refresh', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/auth/logout', ['controller' => 'Auth', 'action' => 'logout', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/auth/me', ['controller' => 'Auth', 'action' => 'me', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/auth/switch-org', ['controller' => 'Auth', 'action' => 'switchOrg', 'prefix' => 'Api/V2', '_method' => 'POST']);

        // --- OAuth (no JWT required) ---
        $builder->connect(
            '/auth/oauth/{provider}/redirect',
            ['controller' => 'OAuth', 'action' => 'redirect', 'prefix' => 'Api/V2'],
            ['pass' => ['provider'], 'provider' => '(google|github|microsoft)']
        );
        $builder->connect(
            '/auth/oauth/{provider}/callback',
            ['controller' => 'OAuth', 'action' => 'callback', 'prefix' => 'Api/V2'],
            ['pass' => ['provider'], 'provider' => '(google|github|microsoft)']
        );

        // --- Dashboard (TASK-NG-003) ---
        $builder->connect('/dashboard/summary', ['controller' => 'Dashboard', 'action' => 'summary', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/dashboard/uptime', ['controller' => 'Dashboard', 'action' => 'uptime', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/dashboard/response-times', ['controller' => 'Dashboard', 'action' => 'responseTimes', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/dashboard/recent-checks', ['controller' => 'Dashboard', 'action' => 'recentChecks', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/dashboard/recent-alerts', ['controller' => 'Dashboard', 'action' => 'recentAlerts', 'prefix' => 'Api/V2', '_method' => 'GET']);

        // --- Monitors (TASK-NG-004) ---
        $builder->connect('/monitors', ['controller' => 'Monitors', 'action' => 'index', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/monitors', ['controller' => 'Monitors', 'action' => 'add', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/monitors/bulk-action', ['controller' => 'Monitors', 'action' => 'bulkAction', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/monitors/import', ['controller' => 'Monitors', 'action' => 'import', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/monitors/import-competitor', ['controller' => 'Monitors', 'action' => 'importCompetitor', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/monitors/{id}', ['controller' => 'Monitors', 'action' => 'view', 'prefix' => 'Api/V2', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/monitors/{id}', ['controller' => 'Monitors', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PUT'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/monitors/{id}', ['controller' => 'Monitors', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PATCH'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/monitors/{id}', ['controller' => 'Monitors', 'action' => 'delete', 'prefix' => 'Api/V2', '_method' => 'DELETE'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/monitors/{id}/checks', ['controller' => 'Monitors', 'action' => 'checks', 'prefix' => 'Api/V2', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/monitors/{id}/pause', ['controller' => 'Monitors', 'action' => 'pause', 'prefix' => 'Api/V2', '_method' => 'POST'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/monitors/{id}/resume', ['controller' => 'Monitors', 'action' => 'resume', 'prefix' => 'Api/V2', '_method' => 'POST'], ['pass' => ['id'], 'id' => '\d+']);

        // --- Incidents (TASK-NG-005) ---
        $builder->connect('/incidents', ['controller' => 'Incidents', 'action' => 'index', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/incidents', ['controller' => 'Incidents', 'action' => 'add', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/incidents/{id}', ['controller' => 'Incidents', 'action' => 'view', 'prefix' => 'Api/V2', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/incidents/{id}', ['controller' => 'Incidents', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PUT'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/incidents/{id}', ['controller' => 'Incidents', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PATCH'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/incidents/{id}', ['controller' => 'Incidents', 'action' => 'delete', 'prefix' => 'Api/V2', '_method' => 'DELETE'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/incidents/{id}/acknowledge', ['controller' => 'Incidents', 'action' => 'acknowledge', 'prefix' => 'Api/V2', '_method' => 'POST'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/incidents/{id}/updates', ['controller' => 'Incidents', 'action' => 'addUpdate', 'prefix' => 'Api/V2', '_method' => 'POST'], ['pass' => ['id'], 'id' => '\d+']);

        // Checks (read-only)
        $builder->connect('/checks', ['controller' => 'Checks', 'action' => 'index', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/checks/{id}', ['controller' => 'Checks', 'action' => 'view', 'prefix' => 'Api/V2', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);

        // Integrations
        $builder->connect('/integrations', ['controller' => 'Integrations', 'action' => 'index', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/integrations', ['controller' => 'Integrations', 'action' => 'add', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/integrations/{id}', ['controller' => 'Integrations', 'action' => 'view', 'prefix' => 'Api/V2', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/integrations/{id}', ['controller' => 'Integrations', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PUT'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/integrations/{id}', ['controller' => 'Integrations', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PATCH'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/integrations/{id}', ['controller' => 'Integrations', 'action' => 'delete', 'prefix' => 'Api/V2', '_method' => 'DELETE'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/integrations/{id}/test', ['controller' => 'Integrations', 'action' => 'test', 'prefix' => 'Api/V2', '_method' => 'POST'], ['pass' => ['id'], 'id' => '\d+']);

        // Alert Rules
        $builder->connect('/alert-rules', ['controller' => 'AlertRules', 'action' => 'index', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/alert-rules', ['controller' => 'AlertRules', 'action' => 'add', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/alert-rules/{id}', ['controller' => 'AlertRules', 'action' => 'view', 'prefix' => 'Api/V2', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/alert-rules/{id}', ['controller' => 'AlertRules', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PUT'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/alert-rules/{id}', ['controller' => 'AlertRules', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PATCH'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/alert-rules/{id}', ['controller' => 'AlertRules', 'action' => 'delete', 'prefix' => 'Api/V2', '_method' => 'DELETE'], ['pass' => ['id'], 'id' => '\d+']);

        // Escalation Policies
        $builder->connect('/escalation-policies', ['controller' => 'EscalationPolicies', 'action' => 'index', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/escalation-policies', ['controller' => 'EscalationPolicies', 'action' => 'add', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/escalation-policies/{id}', ['controller' => 'EscalationPolicies', 'action' => 'view', 'prefix' => 'Api/V2', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/escalation-policies/{id}', ['controller' => 'EscalationPolicies', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PUT'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/escalation-policies/{id}', ['controller' => 'EscalationPolicies', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PATCH'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/escalation-policies/{id}', ['controller' => 'EscalationPolicies', 'action' => 'delete', 'prefix' => 'Api/V2', '_method' => 'DELETE'], ['pass' => ['id'], 'id' => '\d+']);

        // SLA
        $builder->connect('/sla', ['controller' => 'Sla', 'action' => 'index', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/sla', ['controller' => 'Sla', 'action' => 'add', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/sla/{id}', ['controller' => 'Sla', 'action' => 'view', 'prefix' => 'Api/V2', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/sla/{id}', ['controller' => 'Sla', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PUT'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/sla/{id}', ['controller' => 'Sla', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PATCH'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/sla/{id}', ['controller' => 'Sla', 'action' => 'delete', 'prefix' => 'Api/V2', '_method' => 'DELETE'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/sla/{id}/report', ['controller' => 'Sla', 'action' => 'report', 'prefix' => 'Api/V2', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/sla/{id}/export', ['controller' => 'Sla', 'action' => 'export', 'prefix' => 'Api/V2', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);

        // Settings
        $builder->connect('/settings', ['controller' => 'Settings', 'action' => 'index', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/settings', ['controller' => 'Settings', 'action' => 'save', 'prefix' => 'Api/V2', '_method' => 'PUT']);
        $builder->connect('/settings', ['controller' => 'Settings', 'action' => 'save', 'prefix' => 'Api/V2', '_method' => 'PATCH']);

        // Billing
        $builder->connect('/billing/plans', ['controller' => 'Billing', 'action' => 'plans', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/billing/checkout', ['controller' => 'Billing', 'action' => 'checkout', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/billing/portal', ['controller' => 'Billing', 'action' => 'portal', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/billing/usage', ['controller' => 'Billing', 'action' => 'usage', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/billing/credits', ['controller' => 'Billing', 'action' => 'credits', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/billing/credits/buy', ['controller' => 'Billing', 'action' => 'buyCredits', 'prefix' => 'Api/V2', '_method' => 'POST']);

        // Users (Team)
        $builder->connect('/users', ['controller' => 'Users', 'action' => 'index', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/users/{id}', ['controller' => 'Users', 'action' => 'view', 'prefix' => 'Api/V2', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/users/{id}/role', ['controller' => 'Users', 'action' => 'updateRole', 'prefix' => 'Api/V2', '_method' => 'PUT'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/users/{id}', ['controller' => 'Users', 'action' => 'remove', 'prefix' => 'Api/V2', '_method' => 'DELETE'], ['pass' => ['id'], 'id' => '\d+']);

        // Invitations
        $builder->connect('/invitations', ['controller' => 'Invitations', 'action' => 'index', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/invitations', ['controller' => 'Invitations', 'action' => 'send', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/invitations/{id}', ['controller' => 'Invitations', 'action' => 'revoke', 'prefix' => 'Api/V2', '_method' => 'DELETE'], ['pass' => ['id'], 'id' => '\d+']);

        // API Keys
        $builder->connect('/api-keys', ['controller' => 'ApiKeys', 'action' => 'index', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/api-keys', ['controller' => 'ApiKeys', 'action' => 'add', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/api-keys/{id}', ['controller' => 'ApiKeys', 'action' => 'delete', 'prefix' => 'Api/V2', '_method' => 'DELETE'], ['pass' => ['id'], 'id' => '\d+']);

        // Reports
        $builder->connect('/reports/uptime', ['controller' => 'Reports', 'action' => 'uptime', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/reports/incidents', ['controller' => 'Reports', 'action' => 'incidents', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/reports/response-times', ['controller' => 'Reports', 'action' => 'responseTimes', 'prefix' => 'Api/V2', '_method' => 'GET']);

        // Scheduled Reports
        $builder->connect('/scheduled-reports', ['controller' => 'ScheduledReports', 'action' => 'index', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/scheduled-reports', ['controller' => 'ScheduledReports', 'action' => 'add', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/scheduled-reports/{id}', ['controller' => 'ScheduledReports', 'action' => 'view', 'prefix' => 'Api/V2', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/scheduled-reports/{id}', ['controller' => 'ScheduledReports', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PUT'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/scheduled-reports/{id}', ['controller' => 'ScheduledReports', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PATCH'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/scheduled-reports/{id}', ['controller' => 'ScheduledReports', 'action' => 'delete', 'prefix' => 'Api/V2', '_method' => 'DELETE'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/scheduled-reports/{id}/send-now', ['controller' => 'ScheduledReports', 'action' => 'sendNow', 'prefix' => 'Api/V2', '_method' => 'POST'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/scheduled-reports/{id}/preview', ['controller' => 'ScheduledReports', 'action' => 'preview', 'prefix' => 'Api/V2', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);

        // Maintenance Windows
        $builder->connect('/maintenance-windows', ['controller' => 'MaintenanceWindows', 'action' => 'index', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/maintenance-windows', ['controller' => 'MaintenanceWindows', 'action' => 'add', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/maintenance-windows/{id}', ['controller' => 'MaintenanceWindows', 'action' => 'view', 'prefix' => 'Api/V2', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/maintenance-windows/{id}', ['controller' => 'MaintenanceWindows', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PUT'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/maintenance-windows/{id}', ['controller' => 'MaintenanceWindows', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PATCH'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/maintenance-windows/{id}', ['controller' => 'MaintenanceWindows', 'action' => 'delete', 'prefix' => 'Api/V2', '_method' => 'DELETE'], ['pass' => ['id'], 'id' => '\d+']);

        // Status Pages
        $builder->connect('/status-pages', ['controller' => 'StatusPages', 'action' => 'index', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/status-pages', ['controller' => 'StatusPages', 'action' => 'add', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/status-pages/{id}', ['controller' => 'StatusPages', 'action' => 'view', 'prefix' => 'Api/V2', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/status-pages/{id}', ['controller' => 'StatusPages', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PUT'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/status-pages/{id}', ['controller' => 'StatusPages', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PATCH'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/status-pages/{id}', ['controller' => 'StatusPages', 'action' => 'delete', 'prefix' => 'Api/V2', '_method' => 'DELETE'], ['pass' => ['id'], 'id' => '\d+']);

        // Two-Factor Auth
        $builder->connect('/2fa/setup', ['controller' => 'TwoFactor', 'action' => 'setup', 'prefix' => 'Api/V2', '_method' => ['GET', 'POST']]);
        $builder->connect('/2fa/verify', ['controller' => 'TwoFactor', 'action' => 'verify', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/2fa/disable', ['controller' => 'TwoFactor', 'action' => 'disable', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/2fa/recovery-codes', ['controller' => 'TwoFactor', 'action' => 'recoveryCodes', 'prefix' => 'Api/V2', '_method' => ['GET', 'POST']]);

        // SSE Events Stream (A-01)
        $builder->connect('/events/stream', ['controller' => 'Events', 'action' => 'stream', 'prefix' => 'Api/V2', '_method' => 'GET']);

        // Activity Log
        $builder->connect('/activity-log', ['controller' => 'ActivityLog', 'action' => 'index', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/activity-log/export', ['controller' => 'ActivityLog', 'action' => 'export', 'prefix' => 'Api/V2', '_method' => 'GET']);

        // Organizations
        $builder->connect('/organizations', ['controller' => 'Organizations', 'action' => 'index', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/organizations/current', ['controller' => 'Organizations', 'action' => 'current', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/organizations/switch', ['controller' => 'Organizations', 'action' => 'switchOrg', 'prefix' => 'Api/V2', '_method' => 'POST']);

        // Notification Schedules (C-05)
        $builder->connect('/notification-schedules', ['controller' => 'NotificationSchedules', 'action' => 'index', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/notification-schedules', ['controller' => 'NotificationSchedules', 'action' => 'add', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/notification-schedules/{id}', ['controller' => 'NotificationSchedules', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PUT'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/notification-schedules/{id}', ['controller' => 'NotificationSchedules', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PATCH'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/notification-schedules/{id}', ['controller' => 'NotificationSchedules', 'action' => 'delete', 'prefix' => 'Api/V2', '_method' => 'DELETE'], ['pass' => ['id'], 'id' => '\d+']);

        // Telegram Bot Webhook (C-04) - no JWT auth, verified by URL token
        $builder->connect('/telegram/webhook/{org_id}/{token}', ['controller' => 'TelegramWebhook', 'action' => 'webhook', 'prefix' => 'Api/V2', '_method' => 'POST'], ['pass' => ['org_id', 'token']]);

        // Webhook Endpoints (C-04)
        $builder->connect('/webhook-endpoints', ['controller' => 'WebhookEndpoints', 'action' => 'index', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/webhook-endpoints', ['controller' => 'WebhookEndpoints', 'action' => 'add', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/webhook-endpoints/{id}', ['controller' => 'WebhookEndpoints', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PUT'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/webhook-endpoints/{id}', ['controller' => 'WebhookEndpoints', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PATCH'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/webhook-endpoints/{id}', ['controller' => 'WebhookEndpoints', 'action' => 'delete', 'prefix' => 'Api/V2', '_method' => 'DELETE'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/webhook-endpoints/{id}/test', ['controller' => 'WebhookEndpoints', 'action' => 'test', 'prefix' => 'Api/V2', '_method' => 'POST'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/webhook-endpoints/{id}/deliveries', ['controller' => 'WebhookEndpoints', 'action' => 'deliveries', 'prefix' => 'Api/V2', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);

        // Check Regions (C-01)
        $builder->connect('/check-regions', ['controller' => 'CheckRegions', 'action' => 'index', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/check-regions', ['controller' => 'CheckRegions', 'action' => 'add', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/check-regions/{id}', ['controller' => 'CheckRegions', 'action' => 'view', 'prefix' => 'Api/V2', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/check-regions/{id}', ['controller' => 'CheckRegions', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PUT'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/check-regions/{id}', ['controller' => 'CheckRegions', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PATCH'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/check-regions/{id}', ['controller' => 'CheckRegions', 'action' => 'delete', 'prefix' => 'Api/V2', '_method' => 'DELETE'], ['pass' => ['id'], 'id' => '\d+']);

        // Notification Channels
        $builder->connect('/notification-channels', ['controller' => 'NotificationChannels', 'action' => 'index', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/notification-channels', ['controller' => 'NotificationChannels', 'action' => 'add', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/notification-channels/{id}', ['controller' => 'NotificationChannels', 'action' => 'view', 'prefix' => 'Api/V2', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/notification-channels/{id}', ['controller' => 'NotificationChannels', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PUT'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/notification-channels/{id}', ['controller' => 'NotificationChannels', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PATCH'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/notification-channels/{id}', ['controller' => 'NotificationChannels', 'action' => 'delete', 'prefix' => 'Api/V2', '_method' => 'DELETE'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/notification-channels/{id}/test', ['controller' => 'NotificationChannels', 'action' => 'test', 'prefix' => 'Api/V2', '_method' => 'POST'], ['pass' => ['id'], 'id' => '\d+']);

        // Notification Policies
        $builder->connect('/notification-policies', ['controller' => 'NotificationPolicies', 'action' => 'index', 'prefix' => 'Api/V2', '_method' => 'GET']);
        $builder->connect('/notification-policies', ['controller' => 'NotificationPolicies', 'action' => 'add', 'prefix' => 'Api/V2', '_method' => 'POST']);
        $builder->connect('/notification-policies/{id}', ['controller' => 'NotificationPolicies', 'action' => 'view', 'prefix' => 'Api/V2', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/notification-policies/{id}', ['controller' => 'NotificationPolicies', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PUT'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/notification-policies/{id}', ['controller' => 'NotificationPolicies', 'action' => 'edit', 'prefix' => 'Api/V2', '_method' => 'PATCH'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/notification-policies/{id}', ['controller' => 'NotificationPolicies', 'action' => 'delete', 'prefix' => 'Api/V2', '_method' => 'DELETE'], ['pass' => ['id'], 'id' => '\d+']);

        // Super Admin
        $builder->connect('/super-admin/dashboard', ['controller' => 'Dashboard', 'action' => 'index', 'prefix' => 'Api/V2/SuperAdmin', '_method' => 'GET']);
        $builder->connect('/super-admin/organizations', ['controller' => 'Organizations', 'action' => 'index', 'prefix' => 'Api/V2/SuperAdmin', '_method' => 'GET']);
        $builder->connect('/super-admin/organizations/{id}', ['controller' => 'Organizations', 'action' => 'view', 'prefix' => 'Api/V2/SuperAdmin', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/super-admin/organizations/{id}/impersonate', ['controller' => 'Organizations', 'action' => 'impersonate', 'prefix' => 'Api/V2/SuperAdmin', '_method' => 'POST'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/super-admin/organizations/{id}/grant-credits', ['controller' => 'Organizations', 'action' => 'grantCredits', 'prefix' => 'Api/V2/SuperAdmin', '_method' => 'POST'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/super-admin/stop-impersonation', ['controller' => 'Organizations', 'action' => 'stopImpersonation', 'prefix' => 'Api/V2/SuperAdmin', '_method' => 'POST']);
        $builder->connect('/super-admin/users', ['controller' => 'Users', 'action' => 'index', 'prefix' => 'Api/V2/SuperAdmin', '_method' => 'GET']);
        $builder->connect('/super-admin/users/{id}', ['controller' => 'Users', 'action' => 'view', 'prefix' => 'Api/V2/SuperAdmin', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/super-admin/revenue', ['controller' => 'Revenue', 'action' => 'index', 'prefix' => 'Api/V2/SuperAdmin', '_method' => 'GET']);
        $builder->connect('/super-admin/health', ['controller' => 'Health', 'action' => 'index', 'prefix' => 'Api/V2/SuperAdmin', '_method' => 'GET']);
        $builder->connect('/super-admin/settings', ['controller' => 'Settings', 'action' => 'index', 'prefix' => 'Api/V2/SuperAdmin', '_method' => 'GET']);
        $builder->connect('/super-admin/settings', ['controller' => 'Settings', 'action' => 'save', 'prefix' => 'Api/V2/SuperAdmin', '_method' => 'PUT']);
        $builder->connect('/super-admin/settings/test-email', ['controller' => 'Settings', 'action' => 'testEmail', 'prefix' => 'Api/V2/SuperAdmin', '_method' => 'POST']);
        $builder->connect('/super-admin/settings/test-ftp', ['controller' => 'Settings', 'action' => 'testFtp', 'prefix' => 'Api/V2/SuperAdmin', '_method' => 'POST']);
        $builder->connect('/super-admin/security-logs', ['controller' => 'SecurityLogs', 'action' => 'index', 'prefix' => 'Api/V2/SuperAdmin', '_method' => 'GET']);

        // Super Admin — Plans (D-02)
        $builder->connect('/super-admin/plans', ['controller' => 'Plans', 'action' => 'index', 'prefix' => 'Api/V2/SuperAdmin', '_method' => 'GET']);
        $builder->connect('/super-admin/plans', ['controller' => 'Plans', 'action' => 'add', 'prefix' => 'Api/V2/SuperAdmin', '_method' => 'POST']);
        $builder->connect('/super-admin/plans/{id}', ['controller' => 'Plans', 'action' => 'view', 'prefix' => 'Api/V2/SuperAdmin', '_method' => 'GET'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/super-admin/plans/{id}', ['controller' => 'Plans', 'action' => 'edit', 'prefix' => 'Api/V2/SuperAdmin', '_method' => 'PUT'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/super-admin/plans/{id}', ['controller' => 'Plans', 'action' => 'edit', 'prefix' => 'Api/V2/SuperAdmin', '_method' => 'PATCH'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/super-admin/plans/{id}', ['controller' => 'Plans', 'action' => 'delete', 'prefix' => 'Api/V2/SuperAdmin', '_method' => 'DELETE'], ['pass' => ['id'], 'id' => '\d+']);
        $builder->connect('/super-admin/plans/{id}/duplicate', ['controller' => 'Plans', 'action' => 'duplicate', 'prefix' => 'Api/V2/SuperAdmin', '_method' => 'POST'], ['pass' => ['id'], 'id' => '\d+']);

        $builder->fallbacks();
    });
};
