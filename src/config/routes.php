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
         * Heartbeat ping route (TASK-1000)
         */
        $builder->connect(
            '/heartbeat/{token}',
            ['controller' => 'Heartbeat', 'action' => 'ping'],
            ['pass' => ['token'], 'token' => '[a-f0-9]{64}']
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
};
