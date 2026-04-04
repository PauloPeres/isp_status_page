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

use Cake\Core\Configure;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\View\Exception\MissingTemplateException;

/**
 * Static content controller
 *
 * This controller will render views from templates/Pages/
 *
 * @link https://book.cakephp.org/4/en/controllers/pages-controller.html
 */
class PagesController extends AppController
{
    /**
     * Home page - redirects based on authentication status
     *
     * @return \Cake\Http\Response
     */
    public function home(): ?Response
    {
        // Check if user is authenticated
        $identity = $this->Authentication->getIdentity();

        if ($identity) {
            // User is logged in, redirect to Angular dashboard
            return $this->redirect('/app/dashboard');
        }

        // User is not logged in, render the landing page
        $this->viewBuilder()->disableAutoLayout();

        // Load plans from DB so pricing is always consistent
        $plans = [];
        try {
            $plansTable = $this->fetchTable('Plans');
            $plans = $plansTable->find()
                ->where(['Plans.active' => true])
                ->orderBy(['Plans.display_order' => 'ASC', 'Plans.price_monthly' => 'ASC'])
                ->all()
                ->toArray();
        } catch (\Exception $e) {
            // Plans table may not exist yet
        }
        $this->set('plans', $plans);

        return $this->render('landing');
    }

    /**
     * Terms of Service page
     *
     * @return \Cake\Http\Response|null
     */
    public function terms(): ?Response
    {
        $this->viewBuilder()->disableAutoLayout();

        return $this->render('terms');
    }

    /**
     * Privacy Policy page
     *
     * @return \Cake\Http\Response|null
     */
    public function privacy(): ?Response
    {
        $this->viewBuilder()->disableAutoLayout();

        return $this->render('privacy');
    }

    /**
     * Before filter — allow unauthenticated access to marketing pages.
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);

        $this->Authentication->addUnauthenticatedActions([
            'about', 'changelog', 'alternatives', 'useCases', 'featurePage',
            'pt', 'sitemap', 'robots',
        ]);
    }

    /**
     * About page.
     *
     * @return \Cake\Http\Response|null
     */
    public function about(): ?Response
    {
        $this->viewBuilder()->setLayout('marketing');

        return null;
    }

    /**
     * Public changelog page.
     *
     * @return \Cake\Http\Response|null
     */
    public function changelog(): ?Response
    {
        $this->viewBuilder()->setLayout('marketing');

        return null;
    }

    /**
     * Competitor comparison pages.
     *
     * @param string $competitor The competitor slug.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException If competitor page not found.
     */
    public function alternatives(string $competitor): ?Response
    {
        $this->viewBuilder()->setLayout('marketing');

        $allowed = ['uptimerobot', 'pingdom', 'statuspage-io'];
        if (!in_array($competitor, $allowed, true)) {
            throw new NotFoundException(__('Page not found.'));
        }

        $template = 'alternatives_' . str_replace('-', '_', $competitor);

        return $this->render($template);
    }

    /**
     * Use case pages.
     *
     * @param string $useCase The use case slug.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException If use case page not found.
     */
    public function useCases(string $useCase): ?Response
    {
        $this->viewBuilder()->setLayout('marketing');

        $allowed = ['isp', 'saas'];
        if (!in_array($useCase, $allowed, true)) {
            throw new NotFoundException(__('Page not found.'));
        }

        $template = 'use_cases_' . $useCase;

        return $this->render($template);
    }

    /**
     * Feature pages.
     *
     * @param string $feature The feature slug.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException If feature page not found.
     */
    public function featurePage(string $feature): ?Response
    {
        $this->viewBuilder()->setLayout('marketing');

        $allowed = ['status-page', 'alerting', 'ai'];
        if (!in_array($feature, $allowed, true)) {
            throw new NotFoundException(__('Page not found.'));
        }

        $template = 'features_' . str_replace('-', '_', $feature);

        return $this->render($template);
    }

    /**
     * Portuguese marketing pages.
     *
     * @param string $page The page slug.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException If page not found.
     */
    public function pt(string $page): ?Response
    {
        $this->viewBuilder()->setLayout('marketing');

        $allowed = ['monitoramento', 'para-provedores'];
        if (!in_array($page, $allowed, true)) {
            throw new NotFoundException(__('Page not found.'));
        }

        $template = 'pt_' . str_replace('-', '_', $page);

        return $this->render($template);
    }

    /**
     * Dynamic XML sitemap.
     *
     * @return \Cake\Http\Response
     */
    public function sitemap(): Response
    {
        $this->viewBuilder()->disableAutoLayout();
        $this->response = $this->response->withType('xml');

        // Get published blog posts
        $blogPosts = [];
        try {
            $blogPosts = $this->fetchTable('BlogPosts')
                ->find('published')
                ->all()
                ->toArray();
        } catch (\Exception $e) {
            // Table may not exist yet
        }

        $this->set(compact('blogPosts'));

        return $this->render('sitemap');
    }

    /**
     * Robots.txt
     *
     * @return \Cake\Http\Response
     */
    public function robots(): Response
    {
        $this->viewBuilder()->disableAutoLayout();
        $this->response = $this->response->withType('text/plain');

        return $this->render('robots');
    }

    /**
     * Displays a view
     *
     * @param string ...$path Path segments.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\ForbiddenException When a directory traversal attempt.
     * @throws \Cake\View\Exception\MissingTemplateException When the view file could not
     *   be found and in debug mode.
     * @throws \Cake\Http\Exception\NotFoundException When the view file could not
     *   be found and not in debug mode.
     * @throws \Cake\View\Exception\MissingTemplateException In debug mode.
     */
    public function display(string ...$path): ?Response
    {
        if (!$path) {
            return $this->redirect('/');
        }
        if (in_array('..', $path, true) || in_array('.', $path, true)) {
            throw new ForbiddenException();
        }
        $page = $subpage = null;

        if (!empty($path[0])) {
            $page = $path[0];
        }
        if (!empty($path[1])) {
            $subpage = $path[1];
        }
        $this->set(compact('page', 'subpage'));

        try {
            return $this->render(implode('/', $path));
        } catch (MissingTemplateException $exception) {
            if (Configure::read('debug')) {
                throw $exception;
            }
            throw new NotFoundException();
        }
    }
}
