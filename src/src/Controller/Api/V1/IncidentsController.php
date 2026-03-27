<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Model\Entity\ApiKey;
use Cake\Datasource\Exception\RecordNotFoundException;

/**
 * Incidents API Controller
 *
 * Provides list, view, create, and update endpoints for incidents.
 * Tenant scoping is handled automatically by TenantScopeBehavior.
 */
class IncidentsController extends AppController
{
    /**
     * List all incidents for the current tenant.
     *
     * GET /api/v1/incidents
     *
     * @return void
     */
    public function index(): void
    {
        if (!$this->requirePermission(ApiKey::PERMISSION_READ)) {
            return;
        }

        $query = $this->fetchTable('Incidents')
            ->find()
            ->contain(['Monitors'])
            ->orderBy(['Incidents.started_at' => 'DESC']);

        $status = $this->request->getQuery('status');
        if ($status) {
            $query->where(['Incidents.status' => $status]);
        }

        $monitorId = $this->request->getQuery('monitor_id');
        if ($monitorId) {
            $query->where(['Incidents.monitor_id' => (int)$monitorId]);
        }

        $incidents = $query->all()->toArray();

        $this->success($incidents);
    }

    /**
     * View a single incident.
     *
     * GET /api/v1/incidents/{id}
     *
     * @param string $id Incident ID.
     * @return void
     */
    public function view(string $id): void
    {
        if (!$this->requirePermission(ApiKey::PERMISSION_READ)) {
            return;
        }

        try {
            $incident = $this->fetchTable('Incidents')->get((int)$id, contain: ['Monitors']);
        } catch (RecordNotFoundException $e) {
            $this->error('Incident not found', 404);

            return;
        }

        $this->success($incident);
    }

    /**
     * Create a manual incident.
     *
     * POST /api/v1/incidents
     *
     * @return void
     */
    public function add(): void
    {
        if (!$this->requirePermission(ApiKey::PERMISSION_WRITE)) {
            return;
        }

        $incidentsTable = $this->fetchTable('Incidents');
        $incident = $incidentsTable->newEntity($this->request->getData());

        if ($incidentsTable->save($incident)) {
            $this->success($incident, 201);
        } else {
            $this->error('Validation failed: ' . $this->formatErrors($incident->getErrors()), 422);
        }
    }

    /**
     * Update an incident (e.g., change status).
     *
     * PUT /api/v1/incidents/{id}
     *
     * @param string $id Incident ID.
     * @return void
     */
    public function edit(string $id): void
    {
        if (!$this->requirePermission(ApiKey::PERMISSION_WRITE)) {
            return;
        }

        $incidentsTable = $this->fetchTable('Incidents');

        try {
            $incident = $incidentsTable->get((int)$id);
        } catch (RecordNotFoundException $e) {
            $this->error('Incident not found', 404);

            return;
        }

        $incident = $incidentsTable->patchEntity($incident, $this->request->getData());

        if ($incidentsTable->save($incident)) {
            $this->success($incident);
        } else {
            $this->error('Validation failed: ' . $this->formatErrors($incident->getErrors()), 422);
        }
    }

    /**
     * Format entity validation errors into a readable string.
     *
     * @param array $errors The validation errors array.
     * @return string
     */
    private function formatErrors(array $errors): string
    {
        $messages = [];
        foreach ($errors as $field => $fieldErrors) {
            foreach ($fieldErrors as $rule => $message) {
                $messages[] = "{$field}: {$message}";
            }
        }

        return implode('; ', $messages);
    }
}
