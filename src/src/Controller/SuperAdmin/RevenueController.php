<?php
declare(strict_types=1);

namespace App\Controller\SuperAdmin;

use App\Service\SuperAdmin\MetricsService;

class RevenueController extends AppController
{
    public function index()
    {
        $metricsService = new MetricsService();
        $revenue = $metricsService->getRevenueMetrics();
        $trials = $metricsService->getTrialMetrics();
        $growth = $metricsService->getGrowthMetrics(90);
        // Get all orgs with their plans for the revenue table
        $orgsTable = $this->fetchTable('Organizations');
        $organizations = $orgsTable->find()
            ->where(['active' => true, 'plan !=' => 'free'])
            ->orderBy(['plan' => 'DESC', 'created' => 'ASC'])
            ->toArray();
        $this->set(compact('revenue', 'trials', 'growth', 'organizations'));
    }
}
