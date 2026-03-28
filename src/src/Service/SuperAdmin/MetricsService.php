<?php
declare(strict_types=1);

namespace App\Service\SuperAdmin;

use Cake\Cache\Cache;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;

class MetricsService
{
    use LocatorAwareTrait;

    // === REVENUE KPIs (SA-006) ===

    /**
     * Get revenue metrics: MRR, ARR, ARPU, revenue by plan.
     *
     * @return array<string, mixed>
     */
    public function getRevenueMetrics(): array
    {
        return Cache::remember('sa_revenue', function () {
            $orgsTable = $this->fetchTable('Organizations');
            $plansTable = $this->fetchTable('Plans');

            // MRR: sum of monthly prices for all active paid orgs
            $paidOrgs = $orgsTable->find()
                ->where(['Organizations.active' => true, 'Organizations.plan !=' => 'free'])
                ->toArray();

            $plans = $plansTable->find()->all()->indexBy('slug')->toArray();

            $mrr = 0;
            $revenueByPlan = ['free' => 0, 'pro' => 0, 'business' => 0];
            foreach ($paidOrgs as $org) {
                $plan = $plans[$org->plan] ?? null;
                if ($plan) {
                    $monthlyPrice = $plan->price_monthly / 100; // cents to dollars
                    $mrr += $monthlyPrice;
                    $revenueByPlan[$org->plan] = ($revenueByPlan[$org->plan] ?? 0) + $monthlyPrice;
                }
            }

            $totalOrgs = $orgsTable->find()->where(['active' => true])->count();
            $paidCount = count($paidOrgs);

            return [
                'mrr' => $mrr,
                'arr' => $mrr * 12,
                'arpu' => $totalOrgs > 0 ? $mrr / $totalOrgs : 0,
                'revenue_by_plan' => $revenueByPlan,
                'paid_orgs' => $paidCount,
                'total_orgs' => $totalOrgs,
            ];
        }, 'super_admin');
    }

    /**
     * Get growth metrics: signups by day, totals.
     *
     * @param int $days Number of days to look back.
     * @return array<string, mixed>
     */
    public function getGrowthMetrics(int $days = 30): array
    {
        return Cache::remember('sa_growth_' . $days, function () use ($days) {
            $orgsTable = $this->fetchTable('Organizations');
            $since = DateTime::now()->subDays($days);

            // New signups by day
            $newOrgs = $orgsTable->find()
                ->where(['Organizations.created >=' => $since])
                ->orderBy(['Organizations.created' => 'ASC'])
                ->all();

            $signupsByDay = [];
            foreach ($newOrgs as $org) {
                $day = $org->created->format('Y-m-d');
                $signupsByDay[$day] = ($signupsByDay[$day] ?? 0) + 1;
            }

            // Fill in missing days with 0
            $current = clone $since;
            $now = DateTime::now();
            while ($current <= $now) {
                $day = $current->format('Y-m-d');
                if (!isset($signupsByDay[$day])) {
                    $signupsByDay[$day] = 0;
                }
                $current = $current->addDays(1);
            }
            ksort($signupsByDay);

            return [
                'signups_by_day' => $signupsByDay,
                'total_new' => count($newOrgs),
                'new_this_week' => $orgsTable->find()->where(['created >=' => DateTime::now()->subDays(7)])->count(),
            ];
        }, 'super_admin');
    }

    /**
     * Get trial metrics: active trials, conversions, conversion rate.
     *
     * @return array<string, mixed>
     */
    public function getTrialMetrics(): array
    {
        return Cache::remember('sa_trials', function () {
            $orgsTable = $this->fetchTable('Organizations');
            $now = DateTime::now();

            $activeTrials = $orgsTable->find()
                ->where(['trial_ends_at IS NOT' => null, 'trial_ends_at >' => $now])
                ->count();

            // Conversion: orgs that had trial_ends_at but are now on paid plan
            $converted = $orgsTable->find()
                ->where(['trial_ends_at IS NOT' => null, 'plan !=' => 'free'])
                ->count();

            $totalTrialed = $orgsTable->find()
                ->where(['trial_ends_at IS NOT' => null])
                ->count();

            return [
                'active_trials' => $activeTrials,
                'converted' => $converted,
                'total_trialed' => $totalTrialed,
                'conversion_rate' => $totalTrialed > 0 ? round(($converted / $totalTrialed) * 100, 1) : 0,
            ];
        }, 'super_admin');
    }

    // === CUSTOMER & PLATFORM KPIs (SA-007) ===

    /**
     * Get customer metrics: active/inactive counts, by plan, top by monitors, recent signups.
     *
     * @return array<string, mixed>
     */
    public function getCustomerMetrics(): array
    {
        return Cache::remember('sa_customers', function () {
            $orgsTable = $this->fetchTable('Organizations');
            $monitorsTable = $this->fetchTable('Monitors');

            $totalActive = $orgsTable->find()->where(['active' => true])->count();
            $totalInactive = $orgsTable->find()->where(['active' => false])->count();

            // By plan
            $byPlan = [];
            foreach (['free', 'pro', 'business'] as $plan) {
                $byPlan[$plan] = $orgsTable->find()->where(['plan' => $plan, 'active' => true])->count();
            }

            // Top 10 by monitor count
            $topByMonitors = $orgsTable->find()
                ->select([
                    'Organizations.id',
                    'Organizations.name',
                    'Organizations.plan',
                    'Organizations.slug',
                    'monitor_count' => $monitorsTable->find()->func()->count('Monitors.id'),
                ])
                ->leftJoinWith('Monitors')
                ->groupBy(['Organizations.id', 'Organizations.name', 'Organizations.plan', 'Organizations.slug'])
                ->orderBy(['monitor_count' => 'DESC'])
                ->limit(10)
                ->disableAutoFields()
                ->toArray();

            // Recent signups (7 days)
            $recentSignups = $orgsTable->find()
                ->where(['Organizations.created >=' => DateTime::now()->subDays(7)])
                ->orderBy(['Organizations.created' => 'DESC'])
                ->limit(10)
                ->toArray();

            return [
                'total_active' => $totalActive,
                'total_inactive' => $totalInactive,
                'by_plan' => $byPlan,
                'top_by_monitors' => $topByMonitors,
                'recent_signups' => $recentSignups,
            ];
        }, 'super_admin');
    }

    /**
     * Get platform health metrics: monitor counts, checks, incidents, alerts.
     *
     * @return array<string, mixed>
     */
    public function getPlatformHealthMetrics(): array
    {
        return Cache::remember('sa_platform_health', function () {
            $monitorsTable = $this->fetchTable('Monitors');
            $checksTable = $this->fetchTable('MonitorChecks');
            $alertLogsTable = $this->fetchTable('AlertLogs');
            $incidentsTable = $this->fetchTable('Incidents');

            $today = DateTime::now()->startOfDay();
            $weekAgo = DateTime::now()->subDays(7);
            $monthAgo = DateTime::now()->subDays(30);

            return [
                'total_monitors' => $monitorsTable->find()->applyOptions(['skipTenantScope' => true])->count(),
                'active_monitors' => $monitorsTable->find()->applyOptions(['skipTenantScope' => true])->where(['active' => true])->count(),
                'checks_today' => $checksTable->find()->applyOptions(['skipTenantScope' => true])->where(['checked_at >=' => $today])->count(),
                'checks_this_week' => $checksTable->find()->applyOptions(['skipTenantScope' => true])->where(['checked_at >=' => $weekAgo])->count(),
                'checks_this_month' => $checksTable->find()->applyOptions(['skipTenantScope' => true])->where(['checked_at >=' => $monthAgo])->count(),
                'active_incidents' => $incidentsTable->find()->applyOptions(['skipTenantScope' => true])->where(['status !=' => 'resolved'])->count(),
                'alerts_today' => $alertLogsTable->find()->applyOptions(['skipTenantScope' => true])->where(['created >=' => $today])->count(),
            ];
        }, 'super_admin');
    }

    /**
     * Get user engagement metrics: DAU/WAU/MAU, API adoption rate.
     *
     * @return array<string, mixed>
     */
    public function getUserEngagementMetrics(): array
    {
        return Cache::remember('sa_engagement', function () {
            $usersTable = $this->fetchTable('Users');
            $apiKeysTable = $this->fetchTable('ApiKeys');
            $orgsTable = $this->fetchTable('Organizations');

            $now = DateTime::now();

            // DAU/WAU/MAU based on last_login
            $dau = $usersTable->find()->where(['last_login >=' => $now->subDays(1)])->count();
            $wau = $usersTable->find()->where(['last_login >=' => $now->subDays(7)])->count();
            $mau = $usersTable->find()->where(['last_login >=' => $now->subDays(30)])->count();

            $totalUsers = $usersTable->find()->count();

            // API adoption: % of orgs with at least one active API key
            $totalActiveOrgs = $orgsTable->find()->where(['active' => true])->count();
            $orgsWithApiKeys = $apiKeysTable->find()
                ->applyOptions(['skipTenantScope' => true])
                ->select(['organization_id'])
                ->where(['active' => true])
                ->groupBy(['organization_id'])
                ->count();

            return [
                'dau' => $dau,
                'wau' => $wau,
                'mau' => $mau,
                'total_users' => $totalUsers,
                'api_adoption_rate' => $totalActiveOrgs > 0 ? round(($orgsWithApiKeys / $totalActiveOrgs) * 100, 1) : 0,
            ];
        }, 'super_admin');
    }

    /**
     * Clear all super admin cache entries.
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::clear('super_admin');
    }
}
