<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

/**
 * SubscribersController — API v2
 *
 * Manage status page subscribers for the current organization.
 */
class SubscribersController extends AppController
{
    /**
     * GET /api/v2/subscribers
     *
     * List subscribers with search and pagination.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('Subscribers');
        $query = $table->find()->orderBy(['Subscribers.created' => 'DESC']);

        // Search by email or name
        $search = $this->request->getQuery('search');
        if ($search) {
            $search = str_replace(['%', '_'], ['\\%', '\\_'], $search);
            $query->where([
                'OR' => [
                    'Subscribers.email LIKE' => '%' . $search . '%',
                    'Subscribers.name LIKE' => '%' . $search . '%',
                ],
            ]);
        }

        // Filter by active status
        $active = $this->request->getQuery('active');
        if ($active !== null && $active !== '') {
            $query->where(['Subscribers.active' => (bool)$active]);
        }

        // Filter by verified status
        $verified = $this->request->getQuery('verified');
        if ($verified !== null && $verified !== '') {
            $query->where(['Subscribers.verified' => (bool)$verified]);
        }

        $page = max(1, (int)$this->request->getQuery('page', 1));
        $limit = min((int)$this->request->getQuery('limit', 25), 100);

        $total = $query->count();
        $subscribers = $query->limit($limit)->offset(($page - 1) * $limit)->toArray();

        $this->success([
            'subscribers' => $subscribers,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int)ceil($total / $limit),
            ],
        ]);
    }

    /**
     * DELETE /api/v2/subscribers/{id}
     *
     * Delete a subscriber.
     *
     * @param string $id Subscriber ID.
     * @return void
     */
    public function delete(string $id): void
    {
        $this->request->allowMethod(['delete']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('Subscribers');
        $subscriber = $this->resolveEntity('Subscribers', $id);

        if (!$subscriber) {
            $this->error('Subscriber not found', 404);

            return;
        }

        if ($table->delete($subscriber)) {
            $this->success(['message' => 'Subscriber deleted']);
        } else {
            $this->error('Unable to delete subscriber', 500);
        }
    }

    /**
     * PUT /api/v2/subscribers/{id}/toggle
     *
     * Toggle a subscriber's active/inactive status.
     *
     * @param string $id Subscriber ID.
     * @return void
     */
    public function toggle(string $id): void
    {
        $this->request->allowMethod(['put']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('Subscribers');
        $subscriber = $this->resolveEntity('Subscribers', $id);

        if (!$subscriber) {
            $this->error('Subscriber not found', 404);

            return;
        }

        $subscriber->active = !$subscriber->active;

        if ($table->save($subscriber)) {
            $this->success(['subscriber' => $subscriber]);
        } else {
            $this->error('Unable to update subscriber', 500);
        }
    }
}
