<?php
declare(strict_types=1);

namespace App\Controller\Api\V2\SuperAdmin;

use App\Controller\Api\V2\AppController;
use Cake\I18n\DateTime;

/**
 * Super Admin DashboardController
 *
 * Platform-wide SaaS KPI metrics for the admin overview.
 */
class DashboardController extends AppController
{
    /**
     * GET /api/v2/super-admin/dashboard
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        if (!$this->isSuperAdmin) {
            $this->error('Super admin access required', 403);
            return;
        }

        $orgsTable = $this->fetchTable('Organizations');
        $usersTable = $this->fetchTable('Users');
        $monitorsTable = $this->fetchTable('Monitors');
        $incidentsTable = $this->fetchTable('Incidents');
        $checksTable = $this->fetchTable('MonitorChecks');

        // Core counts
        $totalOrgs = $orgsTable->find()->count();
        $totalUsers = $usersTable->find()->count();
        $totalMonitors = $monitorsTable->find()->count();
        $activeMonitors = $monitorsTable->find()->where(['Monitors.active' => true])->count();

        $activeIncidents = 0;
        try {
            $activeIncidents = $incidentsTable->find()
                ->where(['Incidents.status !=' => 'resolved'])
                ->count();
        } catch (\Exception $e) {
            // Table may not have expected columns
        }

        // Plan distribution (converted from raw SQL to ORM)
        $planDistribution = [];
        try {
            $query = $orgsTable->find();
            $planField = $query->func()->coalesce([
                'Organizations.plan' => 'identifier',
                "'free'" => 'literal',
            ]);
            $results = $query
                ->select([
                    'plan_name' => $planField,
                    'org_count' => $query->func()->count('*'),
                ])
                ->groupBy([$planField])
                ->orderByDesc('org_count')
                ->disableAutoFields()
                ->all();
            foreach ($results as $row) {
                $planDistribution[] = [
                    'plan' => $row->plan_name,
                    'count' => (int)$row->org_count,
                ];
            }
        } catch (\Exception $e) {
            // Fallback
        }

        // Monitor status breakdown (converted from raw SQL to ORM)
        $monitorsByStatus = ['up' => 0, 'down' => 0, 'degraded' => 0, 'unknown' => 0];
        try {
            $query = $monitorsTable->find();
            $statusField = $query->func()->coalesce([
                'Monitors.status' => 'identifier',
                "'unknown'" => 'literal',
            ]);
            $results = $query
                ->select([
                    'status_name' => $statusField,
                    'cnt' => $query->func()->count('*'),
                ])
                ->where(['Monitors.active' => true])
                ->groupBy([$statusField])
                ->disableAutoFields()
                ->all();
            foreach ($results as $row) {
                $monitorsByStatus[$row->status_name] = (int)$row->cnt;
            }
        } catch (\Exception $e) {
            // Fallback
        }

        // Recent signups (last 30 days)
        $recentSignups = 0;
        try {
            $recentSignups = $orgsTable->find()
                ->where(['Organizations.created >=' => DateTime::now()->subDays(30)->format('Y-m-d')])
                ->count();
        } catch (\Exception $e) {
            // Fallback
        }

        // Checks last 24h
        $checksLast24h = 0;
        try {
            $checksLast24h = $checksTable->find()
                ->where(['MonitorChecks.checked_at >=' => DateTime::now()->subHours(24)->format('Y-m-d H:i:s')])
                ->count();
        } catch (\Exception $e) {
            // Fallback
        }

        // MRR estimate from plan distribution
        $mrr = 0;
        $paidOrgs = 0;
        try {
            $plansTable = $this->fetchTable('Plans');
            $plans = $plansTable->find()->all()->combine('slug', 'price_monthly')->toArray();
            foreach ($planDistribution as $pd) {
                $price = $plans[$pd['plan']] ?? 0;
                $mrr += ($price / 100) * $pd['count'];
                if ($price > 0) {
                    $paidOrgs += $pd['count'];
                }
            }
        } catch (\Exception $e) {
            // Fallback
        }

        // ARPU (Average Revenue Per User/Org)
        $arpu = $totalOrgs > 0 ? round($mrr / $totalOrgs, 2) : 0;
        $arppu = $paidOrgs > 0 ? round($mrr / $paidOrgs, 2) : 0; // paying only

        // MAU / DAU — based on security audit logs (login events)
        $mau = 0;
        $dau = 0;
        $wau = 0;
        try {
            $logsTable = $this->fetchTable('SecurityAuditLogs');

            $mau = (int)$logsTable->find()
                ->select(['unique_users' => $logsTable->find()->func()->count('DISTINCT user_id')])
                ->where([
                    'event_type' => 'login',
                    'created >=' => DateTime::now()->subDays(30)->format('Y-m-d'),
                ])
                ->disableAutoFields()
                ->first()
                ->unique_users;

            $dau = (int)$logsTable->find()
                ->select(['unique_users' => $logsTable->find()->func()->count('DISTINCT user_id')])
                ->where([
                    'event_type' => 'login',
                    'created >=' => DateTime::now()->format('Y-m-d'),
                ])
                ->disableAutoFields()
                ->first()
                ->unique_users;

            $wau = (int)$logsTable->find()
                ->select(['unique_users' => $logsTable->find()->func()->count('DISTINCT user_id')])
                ->where([
                    'event_type' => 'login',
                    'created >=' => DateTime::now()->subDays(7)->format('Y-m-d'),
                ])
                ->disableAutoFields()
                ->first()
                ->unique_users;
        } catch (\Exception $e) {
            // SecurityAuditLogs may not exist or have login events
        }

        // Churn indicator: orgs created > 30 days ago with 0 active monitors
        // Raw SQL retained: correlated NOT EXISTS subquery is not cleanly
        // expressible via CakePHP's ORM query builder without embedding raw
        // SQL fragments, so there is no readability or safety gain from a
        // partial conversion.
        $churnRisk = 0;
        try {
            $conn = $orgsTable->getConnection();
            $stmt = $conn->execute(
                "SELECT COUNT(*) as cnt FROM organizations o
                 WHERE o.created < ? AND NOT EXISTS (
                    SELECT 1 FROM monitors m WHERE m.organization_id = o.id AND m.active = true
                 )",
                [DateTime::now()->subDays(30)->format('Y-m-d')]
            );
            $churnRisk = (int)($stmt->fetch('assoc')['cnt'] ?? 0);
        } catch (\Exception $e) {
            // Fallback
        }

        // Conversion rate: paid orgs / total orgs
        $conversionRate = $totalOrgs > 0 ? round(($paidOrgs / $totalOrgs) * 100, 1) : 0;

        $this->success([
            'metrics' => [
                'total_organizations' => $totalOrgs,
                'total_users' => $totalUsers,
                'total_monitors' => $totalMonitors,
                'active_monitors' => $activeMonitors,
                'active_incidents' => $activeIncidents,
                'recent_signups_30d' => $recentSignups,
                'checks_last_24h' => $checksLast24h,
                'mrr' => round($mrr, 2),
                'arr' => round($mrr * 12, 2),
                'arpu' => $arpu,
                'arppu' => $arppu,
                'paid_organizations' => $paidOrgs,
                'conversion_rate' => $conversionRate,
                'dau' => $dau,
                'wau' => $wau,
                'mau' => $mau,
                'churn_risk' => $churnRisk,
            ],
            'plan_distribution' => $planDistribution,
            'monitors_by_status' => $monitorsByStatus,
        ]);
    }
}
