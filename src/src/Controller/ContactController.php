<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Contact Controller
 *
 * Redirects legacy admin routes to the Angular SPA.
 */
class ContactController extends AppController
{
    public function beforeFilter(\Cake\Event\EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authentication->addUnauthenticatedActions(['sales']);
    }

    public function sales()
    {
        return $this->redirect('/app/contact');
    }
}
