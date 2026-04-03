<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

/**
 * NotificationSchedulesController (C-05)
 *
 * CRUD for per-channel, per-severity notification schedules.
 */
class NotificationSchedulesController extends AppController
{
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('NotificationSchedules');
        $schedules = $table->find()
            ->orderBy(['NotificationSchedules.name' => 'ASC'])
            ->all()
            ->toArray();

        $this->success([
            'items' => $schedules,
            'pagination' => [
                'page' => 1,
                'limit' => count($schedules),
                'total' => count($schedules),
                'pages' => 1,
            ],
        ]);
    }

    public function add(): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('NotificationSchedules');
        $data = $this->request->getData();

        // Encode arrays to JSON for storage
        foreach (['channels', 'severities', 'days_of_week'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = json_encode($data[$field]);
            }
        }

        $schedule = $table->newEntity($data);
        $schedule->set('organization_id', $this->currentOrgId);

        if (!$table->save($schedule)) {
            $this->error('Validation failed', 422, $schedule->getErrors());
            return;
        }

        $this->success(['notification_schedule' => $schedule], 201);
    }

    public function edit(string $id): void
    {
        $this->request->allowMethod(['put', 'patch']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('NotificationSchedules');
        $schedule = $this->resolveOrgEntity('NotificationSchedules', $id);

        if (!$schedule) {
            $this->error('Schedule not found', 404);
            return;
        }

        $data = $this->request->getData();
        foreach (['channels', 'severities', 'days_of_week'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = json_encode($data[$field]);
            }
        }

        $schedule = $table->patchEntity($schedule, $data);

        if (!$table->save($schedule)) {
            $this->error('Validation failed', 422, $schedule->getErrors());
            return;
        }

        $this->success(['notification_schedule' => $schedule]);
    }

    public function delete(string $id): void
    {
        $this->request->allowMethod(['delete']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('NotificationSchedules');
        $schedule = $this->resolveOrgEntity('NotificationSchedules', $id);

        if (!$schedule) {
            $this->error('Schedule not found', 404);
            return;
        }

        if ($table->delete($schedule)) {
            $this->success(['message' => 'Schedule deleted']);
        } else {
            $this->error('Failed to delete', 500);
        }
    }
}
