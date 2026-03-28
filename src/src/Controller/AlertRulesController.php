<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\AlertRule;

/**
 * AlertRules Controller
 *
 * Admin UI for managing alert rules per organization.
 *
 * @property \App\Model\Table\AlertRulesTable $AlertRules
 */
class AlertRulesController extends AppController
{
    /**
     * Index method - list alert rules for current org
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $this->viewBuilder()->setLayout('admin');

        $query = $this->AlertRules->find()
            ->contain(['Monitors']);

        // Filter by channel
        $channel = $this->request->getQuery('channel');
        if ($channel) {
            $query->where(['AlertRules.channel' => $channel]);
        }

        // Filter by active status
        $active = $this->request->getQuery('active');
        if ($active !== null && $active !== '') {
            $query->where(['AlertRules.active' => (bool)$active]);
        }

        // Search by monitor name
        $search = $this->request->getQuery('search');
        if (!empty($search)) {
            $query->where([
                'OR' => [
                    'Monitors.name LIKE' => '%' . $search . '%',
                    'AlertRules.recipient LIKE' => '%' . $search . '%',
                ],
            ]);
        }

        $query->orderBy(['AlertRules.created' => 'DESC']);

        $alertRules = $this->paginate($query);

        // Get monitors list for filter dropdown
        $monitors = $this->AlertRules->Monitors->find('list', keyField: 'id', valueField: 'name')->toArray();

        $this->set(compact('alertRules', 'monitors'));
    }

    /**
     * Add method - create new alert rule
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $this->viewBuilder()->setLayout('admin');

        $alertRule = $this->AlertRules->newEmptyEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            // Convert recipients textarea to JSON array
            if (isset($data['recipients_text'])) {
                $recipients = array_filter(
                    array_map('trim', explode("\n", $data['recipients_text'])),
                    fn($r) => !empty($r)
                );
                $data['recipients'] = json_encode(array_values($recipients));
                unset($data['recipients_text']);
            }

            $alertRule = $this->AlertRules->patchEntity($alertRule, $data);

            if ($this->AlertRules->save($alertRule)) {
                $this->Flash->success(__('Alert rule created successfully.'));
                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__('Could not create the alert rule. Please try again.'));
        }

        $monitors = $this->AlertRules->Monitors->find('list', keyField: 'id', valueField: 'name')->toArray();
        $channels = $this->getChannelOptions();
        $triggers = $this->getTriggerOptions();

        $this->set(compact('alertRule', 'monitors', 'channels', 'triggers'));
    }

    /**
     * Edit method - modify alert rule
     *
     * @param string|null $id Alert rule id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $this->viewBuilder()->setLayout('admin');

        $alertRule = $this->AlertRules->get($id, contain: ['Monitors']);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            // Convert recipients textarea to JSON array
            if (isset($data['recipients_text'])) {
                $recipients = array_filter(
                    array_map('trim', explode("\n", $data['recipients_text'])),
                    fn($r) => !empty($r)
                );
                $data['recipients'] = json_encode(array_values($recipients));
                unset($data['recipients_text']);
            }

            $alertRule = $this->AlertRules->patchEntity($alertRule, $data);

            if ($this->AlertRules->save($alertRule)) {
                $this->Flash->success(__('Alert rule updated successfully.'));
                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__('Could not update the alert rule. Please try again.'));
        }

        $monitors = $this->AlertRules->Monitors->find('list', keyField: 'id', valueField: 'name')->toArray();
        $channels = $this->getChannelOptions();
        $triggers = $this->getTriggerOptions();

        $this->set(compact('alertRule', 'monitors', 'channels', 'triggers'));
    }

    /**
     * Delete method - remove alert rule
     *
     * @param string|null $id Alert rule id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $alertRule = $this->AlertRules->get($id);

        if ($this->AlertRules->delete($alertRule)) {
            $this->Flash->success(__('Alert rule deleted successfully.'));
        } else {
            $this->Flash->error(__('Could not delete the alert rule. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Get channel options for select dropdown
     *
     * @return array
     */
    private function getChannelOptions(): array
    {
        return [
            AlertRule::CHANNEL_EMAIL => 'Email',
            AlertRule::CHANNEL_SLACK => 'Slack',
            AlertRule::CHANNEL_DISCORD => 'Discord',
            AlertRule::CHANNEL_TELEGRAM => 'Telegram',
            AlertRule::CHANNEL_WEBHOOK => 'Webhook',
            AlertRule::CHANNEL_SMS => 'SMS',
            AlertRule::CHANNEL_WHATSAPP => 'WhatsApp',
            AlertRule::CHANNEL_PHONE => 'Phone',
        ];
    }

    /**
     * Get trigger options for select dropdown
     *
     * @return array
     */
    private function getTriggerOptions(): array
    {
        return [
            AlertRule::TRIGGER_ON_DOWN => 'When Down',
            AlertRule::TRIGGER_ON_UP => 'When Up',
            AlertRule::TRIGGER_ON_DEGRADED => 'When Degraded',
            AlertRule::TRIGGER_ON_CHANGE => 'On Status Change',
        ];
    }
}
