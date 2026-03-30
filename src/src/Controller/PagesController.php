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
