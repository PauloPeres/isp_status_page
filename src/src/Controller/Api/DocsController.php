<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;

/**
 * API Documentation Controller
 *
 * Serves the Swagger UI page for interactive API documentation.
 * Public access — no authentication required.
 */
class DocsController extends AppController
{
    /**
     * Render the Swagger UI page.
     *
     * @return void
     */
    public function index(): void
    {
        $this->viewBuilder()->setLayout(false);
    }

    /**
     * Authentication setup — docs are public.
     *
     * @param \Cake\Event\EventInterface $event The beforeFilter event.
     * @return void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event): void
    {
        parent::beforeFilter($event);

        // Allow unauthenticated access to API docs
        $this->Authentication->addUnauthenticatedActions(['index']);
    }
}
