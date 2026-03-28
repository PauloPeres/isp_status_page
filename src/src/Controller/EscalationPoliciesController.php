<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

/**
 * EscalationPolicies Controller
 *
 * Manages escalation policies for alert escalation workflows.
 * Each policy defines a sequence of steps (email, SMS, Slack, etc.)
 * that trigger at timed intervals when an incident is not acknowledged.
 *
 * @property \App\Model\Table\EscalationPoliciesTable $EscalationPolicies
 */
class EscalationPoliciesController extends AppController
{
    /**
     * Get the current organization ID from the authenticated user.
     *
     * @return int
     */
    private function getOrgId(): int
    {
        $identity = $this->request->getAttribute('identity');
        if ($identity && $identity->get('organization_id')) {
            return (int)$identity->get('organization_id');
        }

        return 0;
    }

    /**
     * Index — list all escalation policies with step and monitor counts.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $this->viewBuilder()->setLayout('admin');

        $query = $this->EscalationPolicies->find()
            ->contain(['EscalationSteps', 'Monitors'])
            ->orderBy(['EscalationPolicies.created' => 'DESC']);

        $escalationPolicies = $this->paginate($query);

        $this->set(compact('escalationPolicies'));
    }

    /**
     * View — show policy details with step timeline and linked monitors.
     *
     * @param string|null $id Escalation Policy ID
     * @return \Cake\Http\Response|null|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $this->viewBuilder()->setLayout('admin');

        $escalationPolicy = $this->EscalationPolicies->get($id, contain: [
            'EscalationSteps' => ['sort' => ['EscalationSteps.step_number' => 'ASC']],
            'Monitors',
        ]);

        $this->set(compact('escalationPolicy'));
    }

    /**
     * Add — create a new escalation policy with dynamic steps.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function add()
    {
        $this->viewBuilder()->setLayout('admin');

        $escalationPolicy = $this->EscalationPolicies->newEmptyEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            // Set organization_id
            $orgId = $this->getOrgId();
            if ($orgId > 0) {
                $data['organization_id'] = $orgId;
            } else {
                $data['organization_id'] = $data['organization_id'] ?? 1;
            }

            // Process escalation steps from the dynamic form
            $data = $this->processStepsData($data);

            $escalationPolicy = $this->EscalationPolicies->patchEntity($escalationPolicy, $data, [
                'associated' => ['EscalationSteps'],
            ]);

            if ($this->EscalationPolicies->save($escalationPolicy)) {
                $this->Flash->success(__('The escalation policy has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The escalation policy could not be saved. Please try again.'));
        }

        $this->set(compact('escalationPolicy'));
    }

    /**
     * Edit — modify an existing escalation policy and its steps.
     *
     * @param string|null $id Escalation Policy ID
     * @return \Cake\Http\Response|null|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $this->viewBuilder()->setLayout('admin');

        $escalationPolicy = $this->EscalationPolicies->get($id, contain: [
            'EscalationSteps' => ['sort' => ['EscalationSteps.step_number' => 'ASC']],
        ]);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            // Process escalation steps from the dynamic form
            $data = $this->processStepsData($data);

            // Delete existing steps before saving new ones
            $stepsTable = $this->fetchTable('EscalationSteps');
            $stepsTable->deleteAll(['escalation_policy_id' => $id]);

            $escalationPolicy = $this->EscalationPolicies->patchEntity($escalationPolicy, $data, [
                'associated' => ['EscalationSteps'],
            ]);

            if ($this->EscalationPolicies->save($escalationPolicy)) {
                $this->Flash->success(__('The escalation policy has been updated.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The escalation policy could not be updated. Please try again.'));
        }

        $this->set(compact('escalationPolicy'));
    }

    /**
     * Delete — remove an escalation policy.
     *
     * @param string|null $id Escalation Policy ID
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);

        $escalationPolicy = $this->EscalationPolicies->get($id);

        // Remove policy reference from monitors before deleting
        $monitorsTable = $this->fetchTable('Monitors');
        $monitorsTable->updateAll(
            ['escalation_policy_id' => null],
            ['escalation_policy_id' => $id]
        );

        if ($this->EscalationPolicies->delete($escalationPolicy)) {
            $this->Flash->success(__('The escalation policy has been deleted.'));
        } else {
            $this->Flash->error(__('The escalation policy could not be deleted. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Process the dynamic steps form data into the format expected by CakePHP's
     * associated save (EscalationSteps).
     *
     * @param array $data The request data
     * @return array Modified data with properly structured escalation_steps
     */
    private function processStepsData(array $data): array
    {
        $steps = [];

        if (!empty($data['steps']) && is_array($data['steps'])) {
            $stepNumber = 1;
            foreach ($data['steps'] as $stepData) {
                // Skip empty/removed steps
                if (empty($stepData['channel'])) {
                    continue;
                }

                // Ensure recipients is a JSON array
                $recipients = $stepData['recipients'] ?? '';
                if (is_string($recipients) && !empty($recipients)) {
                    // Try to parse as JSON first
                    $decoded = json_decode($recipients, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        // Not JSON; treat as comma-separated list
                        $recipientsList = array_values(array_filter(array_map('trim', explode(',', $recipients))));
                        $recipients = json_encode($recipientsList);
                    }
                }

                $steps[] = [
                    'step_number' => $stepNumber,
                    'wait_minutes' => (int)($stepData['wait_minutes'] ?? 0),
                    'channel' => $stepData['channel'],
                    'recipients' => $recipients,
                    'message_template' => $stepData['message_template'] ?? null,
                ];
                $stepNumber++;
            }
        }

        $data['escalation_steps'] = $steps;
        unset($data['steps']);

        return $data;
    }
}
