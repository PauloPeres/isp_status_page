<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Checks Controller
 *
 * Redirects legacy admin routes to the Angular SPA.
 */
class ChecksController extends AppController
{
    /**
     * @return \Cake\Http\Response
     */
    public function index()
    {
        return $this->redirect('/app/checks');
    }

    /**
     * @param string|null $id Check id.
     * @return \Cake\Http\Response
     */
    public function view($id = null)
    {
        return $this->redirect('/app/checks');
    }
}
