<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\SlaService;
use Cake\Http\Response;

/**
 * SLA Controller
 *
 * Manages SLA definitions, reports, and exports for enterprise customers.
 *
 * @property \App\Model\Table\SlaDefinitionsTable $SlaDefinitions
 */
class SlaController extends AppController
{
    /**
     * @var \App\Service\SlaService
     */
    private SlaService $slaService;

    /**
     * Initialize method
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->slaService = new SlaService();
    }

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
     * Index — list all SLA definitions with current status.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $this->viewBuilder()->setLayout('admin');

        $slaDefinitionsTable = $this->fetchTable('SlaDefinitions');

        $query = $slaDefinitionsTable->find()
            ->contain(['Monitors'])
            ->orderBy(['SlaDefinitions.created' => 'DESC']);

        $orgId = $this->getOrgId();
        if ($orgId > 0) {
            $query->where(['SlaDefinitions.organization_id' => $orgId]);
        }

        $slaDefinitions = $query->all();

        // Calculate current status for each SLA
        $slaStatuses = [];
        foreach ($slaDefinitions as $slaDef) {
            $slaStatuses[$slaDef->id] = $this->slaService->calculateCurrentSla(
                $slaDef->monitor_id,
                $slaDef->measurement_period,
                (float)$slaDef->target_uptime,
                $slaDef->warning_threshold !== null ? (float)$slaDef->warning_threshold : null
            );
        }

        $this->set(compact('slaDefinitions', 'slaStatuses'));
    }

    /**
     * Add — create a new SLA definition.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function add()
    {
        $this->viewBuilder()->setLayout('admin');

        $slaDefinitionsTable = $this->fetchTable('SlaDefinitions');
        $slaDefinition = $slaDefinitionsTable->newEmptyEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            // Set organization_id
            $orgId = $this->getOrgId();
            if ($orgId > 0) {
                $data['organization_id'] = $orgId;
            } else {
                // Fallback: use org ID 1 for non-tenant context
                $data['organization_id'] = $data['organization_id'] ?? 1;
            }

            // Handle custom target uptime
            if (isset($data['target_uptime_preset'])) {
                if ($data['target_uptime_preset'] !== 'custom') {
                    $data['target_uptime'] = $data['target_uptime_preset'];
                }
                unset($data['target_uptime_preset']);
            }

            $slaDefinition = $slaDefinitionsTable->patchEntity($slaDefinition, $data);

            if ($slaDefinitionsTable->save($slaDefinition)) {
                $this->Flash->success(__('The SLA definition has been saved.'));

                // Generate initial report
                $this->slaService->generateReport($slaDefinition->id);

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The SLA definition could not be saved. Please try again.'));
        }

        $monitors = $this->getAvailableMonitors();

        $this->set(compact('slaDefinition', 'monitors'));
    }

    /**
     * Edit — modify an existing SLA definition.
     *
     * @param string|null $id SLA Definition ID
     * @return \Cake\Http\Response|null|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $this->viewBuilder()->setLayout('admin');

        $slaDefinitionsTable = $this->fetchTable('SlaDefinitions');
        $slaDefinition = $slaDefinitionsTable->get($id, contain: ['Monitors']);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            // Handle custom target uptime
            if (isset($data['target_uptime_preset'])) {
                if ($data['target_uptime_preset'] !== 'custom') {
                    $data['target_uptime'] = $data['target_uptime_preset'];
                }
                unset($data['target_uptime_preset']);
            }

            $slaDefinition = $slaDefinitionsTable->patchEntity($slaDefinition, $data);

            if ($slaDefinitionsTable->save($slaDefinition)) {
                $this->Flash->success(__('The SLA definition has been updated.'));

                // Regenerate report with new settings
                $this->slaService->generateReport($slaDefinition->id);

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The SLA definition could not be updated. Please try again.'));
        }

        $monitors = $this->getAvailableMonitors($slaDefinition->monitor_id);

        $this->set(compact('slaDefinition', 'monitors'));
    }

    /**
     * Delete — remove an SLA definition.
     *
     * @param string|null $id SLA Definition ID
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);

        $slaDefinitionsTable = $this->fetchTable('SlaDefinitions');
        $slaDefinition = $slaDefinitionsTable->get($id);

        if ($slaDefinitionsTable->delete($slaDefinition)) {
            $this->Flash->success(__('The SLA definition has been deleted.'));
        } else {
            $this->Flash->error(__('The SLA definition could not be deleted. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Report — detailed SLA report view with history.
     *
     * @param string|null $id SLA Definition ID
     * @return \Cake\Http\Response|null|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function report(?string $id = null)
    {
        $this->viewBuilder()->setLayout('admin');

        $slaDefinitionsTable = $this->fetchTable('SlaDefinitions');
        $slaDefinition = $slaDefinitionsTable->get($id, contain: ['Monitors']);

        // Current SLA status
        $currentSla = $this->slaService->calculateCurrentSla(
            $slaDefinition->monitor_id,
            $slaDefinition->measurement_period,
            (float)$slaDefinition->target_uptime,
            $slaDefinition->warning_threshold !== null ? (float)$slaDefinition->warning_threshold : null
        );

        // Generate/update current period report
        $this->slaService->generateReport($slaDefinition->id);

        // Historical reports (last 12 periods)
        $history = $this->slaService->getHistory($slaDefinition->id, 12);

        // Build chart data from history
        $chartData = [];
        foreach (array_reverse($history) as $report) {
            $chartData[] = [
                'period' => $report->period_start->format('Y-m'),
                'uptime' => (float)$report->actual_uptime,
                'target' => (float)$report->target_uptime,
            ];
        }

        $this->set(compact('slaDefinition', 'currentSla', 'history', 'chartData'));
    }

    /**
     * Export SLA report as CSV.
     *
     * @param string|null $id SLA Definition ID
     * @return \Cake\Http\Response
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function exportReport(?string $id = null): Response
    {
        $slaDefinitionsTable = $this->fetchTable('SlaDefinitions');
        $slaDefinition = $slaDefinitionsTable->get($id, contain: ['Monitors']);

        $history = $this->slaService->getHistory($slaDefinition->id, 24);

        // Build CSV
        $csv = "Period Start,Period End,Period Type,Target Uptime (%),Actual Uptime (%),"
             . "Total Minutes,Downtime (min),Allowed Downtime (min),Remaining Downtime (min),"
             . "Status,Incidents\n";

        foreach ($history as $report) {
            $csv .= sprintf(
                "%s,%s,%s,%.3f,%.3f,%d,%.2f,%.2f,%.2f,%s,%d\n",
                $report->period_start->format('Y-m-d'),
                $report->period_end->format('Y-m-d'),
                $report->period_type,
                (float)$report->target_uptime,
                (float)$report->actual_uptime,
                $report->total_minutes,
                (float)$report->downtime_minutes,
                (float)$report->allowed_downtime_minutes,
                (float)$report->remaining_downtime_minutes,
                $report->status,
                $report->incidents_count
            );
        }

        $filename = sprintf(
            'sla-report-%s-%s.csv',
            strtolower(str_replace(' ', '-', $slaDefinition->monitor->name ?? 'monitor')),
            date('Y-m-d')
        );

        $response = $this->response
            ->withType('text/csv')
            ->withHeader('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->withStringBody($csv);

        return $response;
    }

    /**
     * Get monitors available for SLA assignment.
     *
     * Excludes monitors that already have an SLA, unless editing the current one.
     *
     * @param int|null $currentMonitorId If editing, include this monitor in the list
     * @return array<int, string> Monitor options for dropdown
     */
    private function getAvailableMonitors(?int $currentMonitorId = null): array
    {
        $monitorsTable = $this->fetchTable('Monitors');
        $slaDefinitionsTable = $this->fetchTable('SlaDefinitions');

        // Get monitor IDs that already have SLAs
        $existingSlaMonitorIds = $slaDefinitionsTable->find()
            ->select(['monitor_id'])
            ->all()
            ->extract('monitor_id')
            ->toArray();

        // Build monitor query
        $query = $monitorsTable->find()
            ->where(['Monitors.active' => true])
            ->orderBy(['Monitors.name' => 'ASC']);

        // Exclude monitors with existing SLAs (unless it's the current one being edited)
        if (!empty($existingSlaMonitorIds)) {
            $excludeIds = array_filter($existingSlaMonitorIds, function ($mid) use ($currentMonitorId) {
                return $mid !== $currentMonitorId;
            });
            if (!empty($excludeIds)) {
                $query->where(['Monitors.id NOT IN' => array_values($excludeIds)]);
            }
        }

        return $query->find('list', keyField: 'id', valueField: 'name')->toArray();
    }
}
