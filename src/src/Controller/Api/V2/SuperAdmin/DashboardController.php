<?php
declare(strict_types=1);

namespace App\Controller\Api\V2\SuperAdmin;

/**
 * Super Admin DashboardController (TASK-NG-014)
 *
 * Platform-wide summary metrics.
 */
class DashboardController extends AppController
{
    /**
     * GET /api/v2/super-admin/dashboard
     *
     * Return summary metrics for the platform.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $orgsCount = $this->fetchTable('Organizations')->find()->count();
        $usersCount = $this->fetchTable('Users')->find()->count();
        $monitorsCount = $this->fetchTable('Monitors')->find()->count();
        $activeIncidents = $this->fetchTable('Incidents')->find()
            ->where(['Incidents.status' => 'open'])
            ->count();

        $this->success([
            'metrics' => [
                'total_organizations' => $orgsCount,
                'total_users' => $usersCount,
                'total_monitors' => $monitorsCount,
                'active_incidents' => $activeIncidents,
            ],
        ]);
    }
}
