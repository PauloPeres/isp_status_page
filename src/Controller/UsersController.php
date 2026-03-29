<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Users Controller
 *
 * All user management UI is handled by the Angular SPA.
 * This controller only processes server-side auth actions and redirects.
 *
 * @property \App\Model\Table\UsersTable $Users
 */
class UsersController extends AppController
{
    /**
     * Before filter callback
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);

        $this->Authentication->addUnauthenticatedActions([
            'login',
            'forgotPassword',
            'resetPassword',
        ]);
    }

    /**
     * Login action — redirect to Angular SPA login.
     *
     * POST requests are still processed server-side for session-based auth,
     * but all UI is handled by Angular.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function login()
    {
        $result = $this->Authentication->getResult();

        // If already logged in, go to Angular dashboard
        if ($result && $result->isValid()) {
            return $this->redirect('/app/dashboard');
        }

        // Redirect GET requests to Angular login
        if ($this->request->is('get')) {
            return $this->redirect('/app/login');
        }

        // POST: process authentication, then redirect
        $this->request->allowMethod(['post']);

        if ($result && $result->isValid()) {
            return $this->redirect('/app/dashboard');
        }

        // Auth failed — redirect back to Angular login with error flag
        return $this->redirect('/app/login?error=invalid_credentials');
    }

    /**
     * Logout action — clear session, redirect to Angular login.
     *
     * @return \Cake\Http\Response|null
     */
    public function logout()
    {
        $result = $this->Authentication->getResult();

        if ($result && $result->isValid()) {
            $this->Authentication->logout();
        }

        return $this->redirect('/app/login');
    }

    /**
     * Forgot password — redirect to Angular.
     *
     * @return \Cake\Http\Response
     */
    public function forgotPassword()
    {
        return $this->redirect('/app/forgot-password');
    }

    /**
     * Reset password — redirect to Angular with token.
     *
     * @param string|null $token Reset token from email link.
     * @return \Cake\Http\Response
     */
    public function resetPassword($token = null)
    {
        if ($token) {
            return $this->redirect('/app/reset-password?token=' . urlencode($token));
        }

        return $this->redirect('/app/forgot-password');
    }

    /**
     * Change password — redirect to Angular.
     *
     * @return \Cake\Http\Response
     */
    public function changePassword()
    {
        return $this->redirect('/app/change-password');
    }

    /**
     * Index — redirect to Angular user management.
     *
     * @return \Cake\Http\Response
     */
    public function index()
    {
        return $this->redirect('/app/team');
    }

    /**
     * View — redirect to Angular.
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response
     */
    public function view($id = null)
    {
        return $this->redirect('/app/team');
    }

    /**
     * Add — redirect to Angular.
     *
     * @return \Cake\Http\Response
     */
    public function add()
    {
        return $this->redirect('/app/team');
    }

    /**
     * Edit — redirect to Angular.
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response
     */
    public function edit($id = null)
    {
        return $this->redirect('/app/settings/profile');
    }

    /**
     * Delete — process server-side, redirect to Angular.
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->Users->get($id);

        $this->Users->delete($user);

        return $this->redirect('/app/team');
    }
}
