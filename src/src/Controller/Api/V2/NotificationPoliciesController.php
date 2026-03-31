<?php
declare(strict_types=1);

namespace App\Controller\Api\V2;

use Cake\Log\Log;

/**
 * NotificationPoliciesController
 *
 * CRUD for notification policies within the current organization.
 * Policies contain ordered steps, each referencing a notification channel.
 */
class NotificationPoliciesController extends AppController
{
    /**
     * GET /api/v2/notification-policies
     *
     * List all notification policies with step count and monitor count.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('NotificationPolicies');
        $policies = $table->find()
            ->where(['NotificationPolicies.organization_id' => $this->currentOrgId])
            ->contain(['NotificationPolicySteps', 'Monitors'])
            ->orderBy(['NotificationPolicies.name' => 'ASC'])
            ->all();

        // Build response with counts
        $result = [];
        foreach ($policies as $policy) {
            $data = $policy->toArray();
            $data['monitor_count'] = $policy->getMonitorCount();
            // step_count is a virtual field, already included
            // Remove full associations from list response to keep it lightweight
            unset($data['notification_policy_steps'], $data['monitors']);
            $result[] = $data;
        }

        $this->success(['notification_policies' => $result]);
    }

    /**
     * GET /api/v2/notification-policies/{id}
     *
     * Get a single notification policy with its steps and their channels.
     *
     * @param string $id Policy ID.
     * @return void
     */
    public function view(string $id): void
    {
        $this->request->allowMethod(['get']);

        $table = $this->fetchTable('NotificationPolicies');
        $policy = $table->find()
            ->where([
                'NotificationPolicies.id' => $id,
                'NotificationPolicies.organization_id' => $this->currentOrgId,
            ])
            ->contain([
                'NotificationPolicySteps' => [
                    'NotificationChannels',
                    'sort' => ['NotificationPolicySteps.step_order' => 'ASC'],
                ],
            ])
            ->first();

        if (!$policy) {
            $this->error('Notification policy not found', 404);

            return;
        }

        $this->success(['notification_policy' => $policy]);
    }

    /**
     * POST /api/v2/notification-policies
     *
     * Create a new notification policy with its steps.
     *
     * @return void
     */
    public function add(): void
    {
        $this->request->allowMethod(['post']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $data = $this->request->getData();
        $steps = $data['steps'] ?? [];
        unset($data['steps']);

        $table = $this->fetchTable('NotificationPolicies');
        $policy = $table->newEntity($data);
        $policy->set('organization_id', $this->currentOrgId);

        // Begin transaction for policy + steps
        $connection = $table->getConnection();
        $connection->begin();

        try {
            if (!$table->save($policy)) {
                $connection->rollback();
                $this->error('Validation failed', 422, $policy->getErrors());

                return;
            }

            // Create steps
            if (!empty($steps)) {
                $stepErrors = $this->createSteps($policy->id, $steps);
                if (!empty($stepErrors)) {
                    $connection->rollback();
                    $this->error('Step validation failed', 422, $stepErrors);

                    return;
                }
            }

            $connection->commit();

            // Reload with steps and channels
            $policy = $table->find()
                ->where(['NotificationPolicies.id' => $policy->id])
                ->contain([
                    'NotificationPolicySteps' => [
                        'NotificationChannels',
                        'sort' => ['NotificationPolicySteps.step_order' => 'ASC'],
                    ],
                ])
                ->first();

            $this->success(['notification_policy' => $policy], 201);
        } catch (\Exception $e) {
            $connection->rollback();
            Log::error("Failed to create notification policy: {$e->getMessage()}");
            $this->error('Failed to create notification policy', 500);
        }
    }

    /**
     * PUT|PATCH /api/v2/notification-policies/{id}
     *
     * Update a notification policy and replace its steps.
     *
     * @param string $id Policy ID.
     * @return void
     */
    public function edit(string $id): void
    {
        $this->request->allowMethod(['put', 'patch']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('NotificationPolicies');
        $policy = $table->find()
            ->where([
                'NotificationPolicies.id' => $id,
                'NotificationPolicies.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$policy) {
            $this->error('Notification policy not found', 404);

            return;
        }

        $data = $this->request->getData();
        $steps = $data['steps'] ?? null;
        unset($data['steps']);

        $policy = $table->patchEntity($policy, $data);

        // Begin transaction for policy + steps
        $connection = $table->getConnection();
        $connection->begin();

        try {
            if (!$table->save($policy)) {
                $connection->rollback();
                $this->error('Validation failed', 422, $policy->getErrors());

                return;
            }

            // Replace steps if provided (replace strategy: delete all, recreate)
            if ($steps !== null) {
                $stepsTable = $this->fetchTable('NotificationPolicySteps');
                $stepsTable->deleteAll(['notification_policy_id' => $policy->id]);

                if (!empty($steps)) {
                    $stepErrors = $this->createSteps($policy->id, $steps);
                    if (!empty($stepErrors)) {
                        $connection->rollback();
                        $this->error('Step validation failed', 422, $stepErrors);

                        return;
                    }
                }
            }

            $connection->commit();

            // Reload with steps and channels
            $policy = $table->find()
                ->where(['NotificationPolicies.id' => $policy->id])
                ->contain([
                    'NotificationPolicySteps' => [
                        'NotificationChannels',
                        'sort' => ['NotificationPolicySteps.step_order' => 'ASC'],
                    ],
                ])
                ->first();

            $this->success(['notification_policy' => $policy]);
        } catch (\Exception $e) {
            $connection->rollback();
            Log::error("Failed to update notification policy {$id}: {$e->getMessage()}");
            $this->error('Failed to update notification policy', 500);
        }
    }

    /**
     * DELETE /api/v2/notification-policies/{id}
     *
     * Delete a notification policy. Unsets notification_policy_id on associated monitors.
     *
     * @param string $id Policy ID.
     * @return void
     */
    public function delete(string $id): void
    {
        $this->request->allowMethod(['delete']);

        if (!$this->requireRole(['owner', 'admin'])) {
            return;
        }

        $table = $this->fetchTable('NotificationPolicies');
        $policy = $table->find()
            ->where([
                'NotificationPolicies.id' => $id,
                'NotificationPolicies.organization_id' => $this->currentOrgId,
            ])
            ->first();

        if (!$policy) {
            $this->error('Notification policy not found', 404);

            return;
        }

        // Unset notification_policy_id on monitors that reference this policy
        $monitorsTable = $this->fetchTable('Monitors');
        $monitorsTable->updateAll(
            ['notification_policy_id' => null],
            ['notification_policy_id' => $policy->id]
        );

        if (!$table->delete($policy)) {
            $this->error('Failed to delete notification policy', 500);

            return;
        }

        $this->success(['message' => 'Notification policy deleted']);
    }

    /**
     * Create steps for a notification policy.
     *
     * @param int $policyId The policy ID.
     * @param array $steps Array of step data.
     * @return array Errors array (empty if no errors).
     */
    private function createSteps(int $policyId, array $steps): array
    {
        $stepsTable = $this->fetchTable('NotificationPolicySteps');
        $errors = [];

        foreach ($steps as $index => $stepData) {
            $stepData['notification_policy_id'] = $policyId;
            $stepData['step_order'] = $stepData['step_order'] ?? ($index + 1);

            $step = $stepsTable->newEntity($stepData);
            if (!$stepsTable->save($step)) {
                $errors["step_{$index}"] = $step->getErrors();
            }
        }

        return $errors;
    }
}
