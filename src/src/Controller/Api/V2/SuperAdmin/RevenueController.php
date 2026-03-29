<?php
declare(strict_types=1);

namespace App\Controller\Api\V2\SuperAdmin;

/**
 * Super Admin RevenueController (TASK-NG-014)
 *
 * Platform revenue metrics.
 */
class RevenueController extends AppController
{
    /**
     * GET /api/v2/super-admin/revenue
     *
     * Return revenue metrics for the platform.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        try {
            $service = new \App\Service\BillingService();
            $metrics = $service->getRevenueMetrics();

            $this->success(['revenue' => $metrics]);
        } catch (\Exception $e) {
            $this->error('Failed to fetch revenue metrics: ' . $e->getMessage(), 500);
        }
    }
}
