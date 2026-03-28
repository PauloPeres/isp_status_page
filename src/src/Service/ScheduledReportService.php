<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\ScheduledReport;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\DateTime;
use Cake\Log\LogTrait;
use Cake\Mailer\Mailer;
use Cake\Mailer\TransportFactory;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Scheduled Report Service
 *
 * Generates report data and sends scheduled email reports (P4-010).
 */
class ScheduledReportService
{
    use LocatorAwareTrait;
    use LogTrait;

    /**
     * @var \App\Service\SettingService
     */
    private SettingService $settingService;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->settingService = new SettingService();
    }

    /**
     * Generate report data for an organization.
     *
     * @param int $orgId Organization ID
     * @param string $frequency Report frequency (weekly or monthly)
     * @param array $options Include flags: include_uptime, include_response_time, include_incidents, include_sla
     * @return array Structured report data
     */
    public function generateReportData(int $orgId, string $frequency, array $options = []): array
    {
        $includeUptime = $options['include_uptime'] ?? true;
        $includeResponseTime = $options['include_response_time'] ?? true;
        $includeIncidents = $options['include_incidents'] ?? true;
        $includeSla = $options['include_sla'] ?? true;

        // Determine date range
        $end = DateTime::now();
        $days = $frequency === 'monthly' ? 30 : 7;
        $start = DateTime::now()->subDays($days)->startOfDay();

        $conn = ConnectionManager::get('default');

        // Get all active monitors for this organization
        $monitorsTable = $this->fetchTable('Monitors');
        $monitors = $monitorsTable->find()
            ->where([
                'Monitors.active' => true,
                'Monitors.organization_id' => $orgId,
            ])
            ->orderBy(['Monitors.name' => 'ASC'])
            ->all()
            ->toArray();

        $monitorData = [];
        $totalUptime = 0;
        $totalIncidents = 0;
        $totalResponseTime = 0;
        $monitorsWithResponseTime = 0;

        foreach ($monitors as $monitor) {
            $entry = [
                'name' => $monitor->name,
                'type' => $monitor->type ?? 'http',
                'uptime' => null,
                'avg_response' => null,
                'incidents' => 0,
                'status' => 'unknown',
            ];

            // Uptime calculation
            if ($includeUptime) {
                $stmt = $conn->execute(
                    "SELECT COUNT(*) as total,
                            SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_count
                     FROM monitor_checks
                     WHERE monitor_id = ? AND checked_at >= ?",
                    [$monitor->id, $start->format('Y-m-d H:i:s')]
                );
                $row = $stmt->fetch('assoc');
                $total = (int)($row['total'] ?? 0);
                $success = (int)($row['success_count'] ?? 0);
                $uptime = $total > 0 ? round(($success / $total) * 100, 2) : null;
                $entry['uptime'] = $uptime;

                if ($uptime !== null) {
                    $totalUptime += $uptime;
                }

                // Determine status based on uptime
                if ($uptime === null) {
                    $entry['status'] = 'unknown';
                } elseif ($uptime >= 99.9) {
                    $entry['status'] = 'operational';
                } elseif ($uptime >= 95.0) {
                    $entry['status'] = 'degraded';
                } else {
                    $entry['status'] = 'down';
                }
            }

            // Average response time
            if ($includeResponseTime) {
                $stmt = $conn->execute(
                    "SELECT ROUND(AVG(response_time)::numeric, 2) as avg_rt
                     FROM monitor_checks
                     WHERE monitor_id = ? AND checked_at >= ? AND response_time IS NOT NULL",
                    [$monitor->id, $start->format('Y-m-d H:i:s')]
                );
                $row = $stmt->fetch('assoc');
                $avgRt = $row['avg_rt'] ?? null;
                $entry['avg_response'] = $avgRt !== null ? (float)$avgRt : null;

                if ($avgRt !== null) {
                    $totalResponseTime += (float)$avgRt;
                    $monitorsWithResponseTime++;
                }
            }

            // Incident count
            if ($includeIncidents) {
                $incidentsTable = $this->fetchTable('Incidents');
                $count = $incidentsTable->find()
                    ->where([
                        'Incidents.monitor_id' => $monitor->id,
                        'Incidents.created >=' => $start->format('Y-m-d H:i:s'),
                    ])
                    ->count();
                $entry['incidents'] = $count;
                $totalIncidents += $count;
            }

            $monitorData[] = $entry;
        }

        // Overall averages
        $monitorCount = count($monitors);
        $avgUptime = $monitorCount > 0 ? round($totalUptime / $monitorCount, 2) : 0;
        $avgResponseTime = $monitorsWithResponseTime > 0
            ? round($totalResponseTime / $monitorsWithResponseTime, 2)
            : 0;

        // SLA status
        $slaStatus = [];
        if ($includeSla) {
            $slaStatus = $this->getSlaStatus($orgId, $days);
        }

        return [
            'period' => [
                'start' => $start,
                'end' => $end,
                'days' => $days,
                'frequency' => $frequency,
            ],
            'summary' => [
                'total_monitors' => $monitorCount,
                'avg_uptime' => $avgUptime,
                'avg_response_time' => $avgResponseTime,
                'total_incidents' => $totalIncidents,
            ],
            'monitors' => $monitorData,
            'sla_status' => $slaStatus,
        ];
    }

    /**
     * Get SLA status for an organization.
     *
     * @param int $orgId Organization ID
     * @param int $days Period in days
     * @return array SLA status data
     */
    private function getSlaStatus(int $orgId, int $days): array
    {
        $slaData = [];

        try {
            $slaDefinitionsTable = $this->fetchTable('SlaDefinitions');
            $slaDefinitions = $slaDefinitionsTable->find()
                ->contain(['Monitors'])
                ->where(['SlaDefinitions.organization_id' => $orgId, 'SlaDefinitions.active' => true])
                ->all();

            $slaService = new SlaService();

            foreach ($slaDefinitions as $slaDef) {
                $status = $slaService->calculateCurrentSla(
                    $slaDef->monitor_id,
                    $slaDef->measurement_period,
                    (float)$slaDef->target_uptime,
                    $slaDef->warning_threshold !== null ? (float)$slaDef->warning_threshold : null
                );

                $slaData[] = [
                    'name' => $slaDef->name,
                    'monitor_name' => $slaDef->monitor->name ?? 'Unknown',
                    'target' => (float)$slaDef->target_uptime,
                    'actual' => $status['actual_uptime'] ?? 0,
                    'status' => $status['status'] ?? 'unknown',
                ];
            }
        } catch (\Exception $e) {
            $this->log('Error fetching SLA status for report: ' . $e->getMessage(), 'warning');
        }

        return $slaData;
    }

    /**
     * Send a scheduled report.
     *
     * @param \App\Model\Entity\ScheduledReport $report The report entity
     * @return bool True if sent successfully
     */
    public function sendReport(ScheduledReport $report): bool
    {
        try {
            $data = $this->generateReportData(
                $report->organization_id,
                $report->frequency,
                [
                    'include_uptime' => $report->include_uptime,
                    'include_response_time' => $report->include_response_time,
                    'include_incidents' => $report->include_incidents,
                    'include_sla' => $report->include_sla,
                ]
            );

            $recipients = $report->getRecipientsArray();
            if (empty($recipients)) {
                $this->log("Scheduled report #{$report->id} has no recipients, skipping.", 'warning');
                return false;
            }

            // Get org name for the email
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

            // Build period label
            $periodLabel = $this->buildPeriodLabel($data['period']);

            // Send to each recipient
            $allSent = true;
            foreach ($recipients as $email) {
                if (!$this->sendReportEmail($email, $report, $data, $orgName, $periodLabel)) {
                    $allSent = false;
                }
            }

            // Update timestamps
            $scheduledReportsTable = $this->fetchTable('ScheduledReports');
            $report->last_sent_at = DateTime::now();
            $report->next_send_at = $this->calculateNextSendAt($report->frequency);
            $scheduledReportsTable->save($report);

            $this->log("Scheduled report #{$report->id} '{$report->name}' sent to " . count($recipients) . " recipients.", 'info');

            return $allSent;
        } catch (\Exception $e) {
            $this->log("Error sending scheduled report #{$report->id}: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Send the report email to a single recipient.
     *
     * @param string $email Recipient email address
     * @param \App\Model\Entity\ScheduledReport $report Report entity
     * @param array $data Report data
     * @param string $orgName Organization name
     * @param string $periodLabel Period label string
     * @return bool True if sent successfully
     */
    private function sendReportEmail(string $email, ScheduledReport $report, array $data, string $orgName, string $periodLabel): bool
    {
        try {
            $mailer = new Mailer();
            $this->configureSMTP($mailer);

            $siteName = $this->settingService->get('site_name', 'ISP Status');
            $fromEmail = $this->settingService->get('email_from', 'noreply@localhost');
            $fromName = $this->settingService->get('email_from_name', $siteName);

            $frequencyLabel = $report->frequency === 'monthly' ? __('Monthly') : __('Weekly');
            $subject = "{$frequencyLabel} " . __('Uptime Report') . " - {$periodLabel} - {$orgName}";

            $mailer
                ->setEmailFormat('both')
                ->setFrom([$fromEmail => $fromName])
                ->setTo($email)
                ->setSubject($subject)
                ->setViewVars([
                    'report' => $report,
                    'data' => $data,
                    'orgName' => $orgName,
                    'periodLabel' => $periodLabel,
                    'siteName' => $siteName,
                    'manageUrl' => '',
                ])
                ->viewBuilder()
                    ->setTemplate('scheduled_report')
                    ->setLayout('default');

            $mailer->deliver();

            return true;
        } catch (\Exception $e) {
            $this->log("Failed to send scheduled report email to {$email}: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Process all due reports.
     *
     * @return int Number of reports processed
     */
    public function processDueReports(): int
    {
        $scheduledReportsTable = $this->fetchTable('ScheduledReports');

        $dueReports = $scheduledReportsTable->find('due')->all();

        $processed = 0;
        foreach ($dueReports as $report) {
            if ($this->sendReport($report)) {
                $processed++;
            }
        }

        return $processed;
    }

    /**
     * Calculate the next send time based on frequency.
     *
     * @param string $frequency Report frequency (weekly or monthly)
     * @param \Cake\I18n\DateTime|null $from Base time (default: now)
     * @return \Cake\I18n\DateTime Next send time
     */
    public function calculateNextSendAt(string $frequency, ?DateTime $from = null): DateTime
    {
        $from = $from ?? DateTime::now();

        if ($frequency === 'monthly') {
            // Next 1st of month at 8:00 AM
            $next = $from->modify('first day of next month')->setTime(8, 0, 0);
        } else {
            // Next Monday at 8:00 AM
            $next = $from->modify('next monday')->setTime(8, 0, 0);
        }

        return $next;
    }

    /**
     * Build a human-readable period label.
     *
     * @param array $period Period data with 'start' and 'end' keys
     * @return string Period label
     */
    private function buildPeriodLabel(array $period): string
    {
        $start = $period['start'];
        $end = $period['end'];

        if ($start instanceof DateTime && $end instanceof DateTime) {
            return $start->format('M j') . ' - ' . $end->format('M j, Y');
        }

        return (string)$start . ' - ' . (string)$end;
    }

    /**
     * Configure SMTP transport on a Mailer instance.
     *
     * @param \Cake\Mailer\Mailer $mailer Mailer instance
     * @return void
     */
    private function configureSMTP(Mailer $mailer): void
    {
        $host = $this->settingService->get('smtp_host', 'localhost');
        $port = (int)$this->settingService->get('smtp_port', 587);
        $username = $this->settingService->get('smtp_username', '');
        $password = $this->settingService->get('smtp_password', '');
        $encryption = strtolower($this->settingService->get('smtp_encryption', ''));

        $config = [
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'password' => $password,
            'className' => 'Smtp',
            'timeout' => 30,
        ];

        if ($encryption === 'tls' || $encryption === 'ssl') {
            $config['tls'] = true;
        }

        $transportName = 'smtp_report_' . uniqid();
        TransportFactory::setConfig($transportName, $config);
        $transport = TransportFactory::get($transportName);
        $mailer->setTransport($transport);
    }
}
