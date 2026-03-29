<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;

/**
 * SpaController
 *
 * Serves the Angular SPA index.html for all /app/* routes.
 * The Angular router handles client-side routing within the SPA.
 */
class SpaController extends AppController
{
    /**
     * Allow unauthenticated access — the SPA uses JWT for auth.
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @return void
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        // SPA doesn't need CakePHP session authentication — it uses JWT
        if ($this->components()->has('Authentication')) {
            $this->Authentication->addUnauthenticatedActions(['index']);
        }
    }

    /**
     * Serve the Angular index.html.
     *
     * All /app/* paths are routed here; the Angular SPA router
     * takes over once the browser loads index.html.
     *
     * @return \Cake\Http\Response|null
     */
    public function index()
    {
        $indexPath = WWW_ROOT . 'app' . DS . 'index.html';
        if (file_exists($indexPath)) {
            $this->response = $this->response
                ->withType('text/html')
                ->withStringBody(file_get_contents($indexPath));

            return $this->response;
        }

        // Fallback if Angular app is not built yet
        $this->Flash->error('Angular app not built. Run: cd frontend && npx ng build --configuration=production');

        return $this->redirect('/');
    }
}
