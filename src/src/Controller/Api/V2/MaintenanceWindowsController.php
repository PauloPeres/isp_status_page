<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

/**
 * MaintenanceWindowsController (TASK-NG-012)
 *
 * CRUD for maintenance windows.
 */
class MaintenanceWindowsController extends AppController
{
    /**
     * GET /api/v2/maintenance-windows
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('MaintenanceWindows');
        $windows = $table->find()
            ->where(['MaintenanceWindows.organization_id' => $this->currentOrgId])
            ->orderBy(['MaintenanceWindows.starts_at' => 'DESC'])
            ->all();

        $this->success(['maintenance_windows' => $windows->toArray()]);
    }

    /**
     * GET /api/v2/maintenance-windows/{id}
     *
     * @param string $id Maintenance window ID.
     * @return void
     */
    public function view(string $id): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('MaintenanceWindows');
        $window = $table->find()
            ->where([
                'MaintenanceWindows.id' => $id,
                'MaintenanceWindows.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$window) {
            $this->error('Maintenance window not found', 404);

            return;
        }

        $this->success(['maintenance_window' => $window]);
    }

    /**
     * POST /api/v2/maintenance-windows
     *
     * @return void
     */
    public function add(): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('MaintenanceWindows');
        $data = $this->request->getData();

        // Set defaults for optional fields
        if (!isset($data['status'])) {
            $data['status'] = 'scheduled';
        }
        if (!isset($data['auto_suppress_alerts'])) {
            $data['auto_suppress_alerts'] = true;
        }
        if (!isset($data['notify_subscribers'])) {
            $data['notify_subscribers'] = false;
        }
        if (!isset($data['is_recurring'])) {
            $data['is_recurring'] = false;
        }

        $window = $table->newEntity($data);
        $window->set('organization_id', $this->currentOrgId);
        $window->set('created_by', $this->currentUserId);

        if (!$table->save($window)) {
            $this->error('Validation failed', 422, $window->getErrors());

            return;
        }

        $this->success(['maintenance_window' => $window], 201);
    }

    /**
     * PUT /api/v2/maintenance-windows/{id}
     *
     * @param string $id Maintenance window ID.
     * @return void
     */
    public function edit(string $id): void
    {
        $this->request->allowMethod(['put']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('MaintenanceWindows');
        $window = $table->find()
            ->where([
                'MaintenanceWindows.id' => $id,
                'MaintenanceWindows.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$window) {
            $this->error('Maintenance window not found', 404);

            return;
        }

        $window = $table->patchEntity($window, $this->request->getData());
        if (!$table->save($window)) {
            $this->error('Validation failed', 422, $window->getErrors());

            return;
        }

        $this->success(['maintenance_window' => $window]);
    }

    /**
     * DELETE /api/v2/maintenance-windows/{id}
     *
     * @param string $id Maintenance window ID.
     * @return void
     */
    public function delete(string $id): void
    {
        $this->request->allowMethod(['delete']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('MaintenanceWindows');
        $window = $table->find()
            ->where([
                'MaintenanceWindows.id' => $id,
                'MaintenanceWindows.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$window) {
            $this->error('Maintenance window not found', 404);

            return;
        }

        if (!$table->delete($window)) {
            $this->error('Failed to delete maintenance window', 500);

            return;
        }

        $this->success(['message' => 'Maintenance window deleted']);
    }
}
