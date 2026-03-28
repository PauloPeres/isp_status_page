<?php
declare(strict_types=1);

namespace App\Controller\SuperAdmin;

use App\Service\SuperAdmin\MetricsService;
use Cake\I18n\DateTime;

class HealthController extends AppController
{
    public function index()
    {
        $metricsService = new MetricsService();
        $health = $metricsService->getPlatformHealthMetrics();
        $engagement = $metricsService->getUserEngagementMetrics();

        // Monitor type distribution
        $monitorsTable = $this->fetchTable('Monitors');
        $typeDistribution = $monitorsTable->find()
            ->select(['type', 'count' => $monitorsTable->find()->func()->count('*')])
            ->applyOptions(['skipTenantScope' => true])
            ->groupBy(['type'])
            ->disableAutoFields()
            ->all()
            ->combine('type', 'count')
            ->toArray();

        // Recent failed alerts
        $failedAlerts = $this->fetchTable('AlertLogs')->find()
            ->where(['status' => 'failed', 'created >=' => DateTime::now()->subDays(7)])
            ->applyOptions(['skipTenantScope' => true])
            ->orderBy(['created' => 'DESC'])
            ->limit(10)
            ->all();

        // Platform stats
        $stats = [
            'total_orgs' => $this->fetchTable('Organizations')->find()->count(),
            'total_users' => $this->fetchTable('Users')->find()->count(),
            'total_api_keys' => $this->fetchTable('ApiKeys')->find()->applyOptions(['skipTenantScope' => true])->where(['active' => true])->count(),
            'total_webhook_endpoints' => $this->fetchTable('WebhookEndpoints')->find()->applyOptions(['skipTenantScope' => true])->where(['active' => true])->count(),
        ];

        $this->set(compact('health', 'engagement', 'typeDistribution', 'failedAlerts', 'stats'));
    }
}
