<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\I18n\DateTime;
use Cake\Log\Log;

/**
 * Heartbeat Controller
 *
 * Provides a public ping endpoint for heartbeat/cron monitoring.
 * Services send HTTP requests to this endpoint to report they are alive.
 */
class HeartbeatController extends AppController
{
    /**
     * Before filter callback.
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);

        // Ping endpoint is public — no authentication required
        $this->Authentication->addUnauthenticatedActions(['ping']);
    }

    /**
     * Ping endpoint — public, no auth required.
     *
     * Finds heartbeat by token and updates last_ping_at.
     *
     * @param string $token The heartbeat token.
     * @return \Cake\Http\Response|null
     */
    public function ping(string $token)
    {
        $this->request->allowMethod(['get']);
        $this->autoRender = false;

        $heartbeatsTable = $this->fetchTable('Heartbeats');

        $heartbeat = $heartbeatsTable->find()
            ->where(['token' => $token])
            ->first();

        if ($heartbeat === null) {
            Log::warning("Heartbeat ping received for unknown token: {$token}");

            return $this->response
                ->withType('application/json')
                ->withStatus(404)
                ->withStringBody(json_encode(['ok' => false, 'error' => 'Heartbeat not found']));
        }

        $heartbeat->last_ping_at = DateTime::now();
        $heartbeatsTable->save($heartbeat);

        Log::debug("Heartbeat ping received for monitor_id: {$heartbeat->monitor_id}");

        return $this->response
            ->withType('application/json')
            ->withStatus(200)
            ->withStringBody(json_encode(['ok' => true]));
    }
}
