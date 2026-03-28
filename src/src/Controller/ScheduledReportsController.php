<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\ScheduledReportService;
use Cake\Http\Response;
use Cake\I18n\DateTime;

/**
 * ScheduledReports Controller
 *
 * Manages scheduled email report configurations (P4-010).
 *
 * @property \App\Model\Table\ScheduledReportsTable $ScheduledReports
 */
class ScheduledReportsController extends AppController
{
    /**
     * @var \App\Service\ScheduledReportService
     */
    private ScheduledReportService $reportService;

    /**
     * Initialize method
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->reportService = new ScheduledReportService();
    }

    /**
     * Get the current organization ID.
     *
     * @return int
     */
    private function getOrgId(): int
    {
        if (!empty($this->currentOrganization['id'])) {
            return (int)$this->currentOrganization['id'];
        }

        $identity = $this->request->getAttribute('identity');
        if ($identity && $identity->get('organization_id')) {
            return (int)$identity->get('organization_id');
        }

        return 0;
    }

    /**
     * Index — list all scheduled reports for the current organization.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $this->viewBuilder()->setLayout('admin');
        $this->checkPermission('manage_resources');

        $orgId = $this->getOrgId();
        $scheduledReportsTable = $this->fetchTable('ScheduledReports');

        $query = $scheduledReportsTable->find()
            ->orderBy(['ScheduledReports.created' => 'DESC']);

        if ($orgId > 0) {
            $query->where(['ScheduledReports.organization_id' => $orgId]);
        }

        $scheduledReports = $query->all();

        $this->set(compact('scheduledReports'));
    }

    /**
     * Add — create a new scheduled report.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function add()
    {
        $this->viewBuilder()->setLayout('admin');
        $this->checkPermission('manage_resources');

        $scheduledReportsTable = $this->fetchTable('ScheduledReports');
        $report = $scheduledReportsTable->newEmptyEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            // Process recipients from comma-separated string to JSON array
            $recipientsRaw = $data['recipients'] ?? '';
            $recipientsArray = array_filter(array_map('trim', explode(',', $recipientsRaw)));
            $data['recipients'] = json_encode(array_values($recipientsArray));

            // Set organization
            $data['organization_id'] = $this->getOrgId();

            // Calculate next send time
            $frequency = $data['frequency'] ?? 'weekly';
            $data['next_send_at'] = $this->reportService->calculateNextSendAt($frequency);

            $report = $scheduledReportsTable->patchEntity($report, $data);

            if ($scheduledReportsTable->save($report)) {
                $this->Flash->success(__('The scheduled report has been saved.'));
                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__('The scheduled report could not be saved. Please try again.'));
        }

        $this->set(compact('report'));
    }

    /**
     * Edit — modify an existing scheduled report.
     *
     * @param string|null $id Report ID
     * @return \Cake\Http\Response|null|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException
     */
    public function edit(?string $id = null)
    {
        $this->viewBuilder()->setLayout('admin');
        $this->checkPermission('manage_resources');

        $scheduledReportsTable = $this->fetchTable('ScheduledReports');
        $report = $scheduledReportsTable->get((int)$id);

        // Verify ownership
        $orgId = $this->getOrgId();
        if ($orgId > 0 && $report->organization_id !== $orgId) {
            $this->Flash->error(__('You do not have access to this report.'));
            return $this->redirect(['action' => 'index']);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            // Process recipients
            $recipientsRaw = $data['recipients'] ?? '';
            $recipientsArray = array_filter(array_map('trim', explode(',', $recipientsRaw)));
            $data['recipients'] = json_encode(array_values($recipientsArray));

            // Recalculate next send time if frequency changed
            $frequency = $data['frequency'] ?? $report->frequency;
            if ($frequency !== $report->frequency) {
                $data['next_send_at'] = $this->reportService->calculateNextSendAt($frequency);
            }

            $report = $scheduledReportsTable->patchEntity($report, $data);

            if ($scheduledReportsTable->save($report)) {
                $this->Flash->success(__('The scheduled report has been updated.'));
                return $this->redirect(['action' => 'index']);
            }

            $this->Flash->error(__('The scheduled report could not be updated. Please try again.'));
        }

        // Convert recipients JSON to comma-separated for form display
        $recipientsDisplay = implode(', ', $report->getRecipientsArray());

        $this->set(compact('report', 'recipientsDisplay'));
    }

    /**
     * Delete — remove a scheduled report.
     *
     * @param string|null $id Report ID
     * @return \Cake\Http\Response|null
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $this->checkPermission('manage_resources');

        $scheduledReportsTable = $this->fetchTable('ScheduledReports');
        $report = $scheduledReportsTable->get((int)$id);

        // Verify ownership
        $orgId = $this->getOrgId();
        if ($orgId > 0 && $report->organization_id !== $orgId) {
            $this->Flash->error(__('You do not have access to this report.'));
            return $this->redirect(['action' => 'index']);
        }

        if ($scheduledReportsTable->delete($report)) {
            $this->Flash->success(__('The scheduled report has been deleted.'));
        } else {
            $this->Flash->error(__('The scheduled report could not be deleted. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Preview — show a report preview in the browser.
     *
     * @param string|null $id Report ID
     * @return \Cake\Http\Response|null|void
     */
    public function preview(?string $id = null)
    {
        $this->viewBuilder()->setLayout('admin');
        $this->checkPermission('manage_resources');

        $scheduledReportsTable = $this->fetchTable('ScheduledReports');
        $report = $scheduledReportsTable->get((int)$id);

        // Verify ownership
        $orgId = $this->getOrgId();
        if ($orgId > 0 && $report->organization_id !== $orgId) {
            $this->Flash->error(__('You do not have access to this report.'));
            return $this->redirect(['action' => 'index']);
        }

        $data = $this->reportService->generateReportData(
            $report->organization_id,
            $report->frequency,
            [
                'include_uptime' => $report->include_uptime,
                'include_response_time' => $report->include_response_time,
                'include_incidents' => $report->include_incidents,
                'include_sla' => $report->include_sla,
            ]
        );

        // Get org name
        $orgName = 'Organization';
        try {
            $orgsTable = $this->fetchTable('Organizations');
            $org = $orgsTable->find()
                ->select(['name'])
                ->where(['id' => $report->organization_id])
                ->first();
            if ($org) {
                $orgName = $org->name;
            }
        } catch (\Exception $e) {
            // Use default
        }

        $periodLabel = '';
        if (isset($data['period']['start']) && isset($data['period']['end'])) {
            $start = $data['period']['start'];
            $end = $data['period']['end'];
            if ($start instanceof DateTime && $end instanceof DateTime) {
                $periodLabel = $start->format('M j') . ' - ' . $end->format('M j, Y');
            }
        }

        $siteName = (new \App\Service\SettingService())->get('site_name', 'ISP Status');

        $this->set(compact('report', 'data', 'orgName', 'periodLabel', 'siteName'));
    }

    /**
     * Send Now — manually trigger a report send.
     *
     * @param string|null $id Report ID
     * @return \Cake\Http\Response|null
     */
    public function sendNow(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $this->checkPermission('manage_resources');

        $scheduledReportsTable = $this->fetchTable('ScheduledReports');
        $report = $scheduledReportsTable->get((int)$id);

        // Verify ownership
        $orgId = $this->getOrgId();
        if ($orgId > 0 && $report->organization_id !== $orgId) {
            $this->Flash->error(__('You do not have access to this report.'));
            return $this->redirect(['action' => 'index']);
        }

        if ($this->reportService->sendReport($report)) {
            $this->Flash->success(__('The report has been sent successfully.'));
        } else {
            $this->Flash->error(__('Failed to send the report. Please check the SMTP settings and try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
