<?php
declare(strict_types=1);

namespace App\Controller\SuperAdmin;

use App\Service\SuperAdmin\MetricsService;

class DashboardController extends AppController
{
    public function index()
    {
        $metricsService = new MetricsService();
        $revenue = $metricsService->getRevenueMetrics();
        $growth = $metricsService->getGrowthMetrics(30);
        $customers = $metricsService->getCustomerMetrics();
        $health = $metricsService->getPlatformHealthMetrics();
        $trials = $metricsService->getTrialMetrics();
        $this->set(compact('revenue', 'growth', 'customers', 'health', 'trials'));
    }
}
