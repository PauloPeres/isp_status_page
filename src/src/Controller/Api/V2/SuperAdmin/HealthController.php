<?php
declare(strict_types=1);

namespace App\Controller\Api\V2\SuperAdmin;

use Cake\Cache\Cache;
use Cake\Datasource\ConnectionManager;

/**
 * Super Admin HealthController (TASK-NG-014)
 *
 * Platform health checks.
 */
class HealthController extends AppController
{
    /**
     * GET /api/v2/super-admin/health
     *
     * Return platform health status.
     *
     * @return void
     */
    public function index(): void
    {
        $this->request->allowMethod(['get']);

        $health = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'disk' => $this->checkDisk(),
        ];

        $allHealthy = !in_array(false, array_column($health, 'healthy'), true);

        $this->success([
            'healthy' => $allHealthy,
            'checks' => $health,
        ]);
    }

    /**
     * Check database connectivity.
     *
     * @return array{healthy: bool, message: string}
     */
    private function checkDatabase(): array
    {
        try {
            $conn = ConnectionManager::get('default');
            $conn->execute('SELECT 1');

            return ['healthy' => true, 'message' => 'Connected'];
        } catch (\Exception $e) {
            return ['healthy' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Check cache connectivity.
     *
     * @return array{healthy: bool, message: string}
     */
    private function checkCache(): array
    {
        try {
            Cache::write('health_check', time(), 'default');
            $val = Cache::read('health_check', 'default');

            return ['healthy' => $val !== null, 'message' => $val !== null ? 'Working' : 'Read failed'];
        } catch (\Exception $e) {
            return ['healthy' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Check disk space.
     *
     * @return array{healthy: bool, message: string, free_gb: float}
     */
    private function checkDisk(): array
    {
        $free = disk_free_space('/');
        $freeGb = round(($free ?: 0) / 1073741824, 2);

        return [
            'healthy' => $freeGb > 1.0,
            'message' => $freeGb > 1.0 ? 'OK' : 'Low disk space',
            'free_gb' => $freeGb,
        ];
    }
}
