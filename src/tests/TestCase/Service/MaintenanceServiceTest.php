<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\MaintenanceService;
use Cake\TestSuite\TestCase;

/**
 * App\Service\MaintenanceService Test Case
 */
class MaintenanceServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Organizations',
        'app.Monitors',
        'app.MaintenanceWindows',
    ];

    protected MaintenanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MaintenanceService();
    }

    protected function tearDown(): void
    {
        unset($this->service);
        parent::tearDown();
    }

    public function testIsInMaintenanceReturnsFalseWhenNoActiveWindow(): void
    {
        // The maintenance window in fixture is in the future (2026-12-01)
        // so monitor 1 should not be in maintenance right now
        $result = $this->service->isInMaintenance(1);
        $this->assertFalse($result);
    }

    public function testIsInMaintenanceReturnsFalseForNonExistentMonitor(): void
    {
        $result = $this->service->isInMaintenance(999);
        $this->assertFalse($result);
    }

    public function testShouldSuppressAlertReturnsFalseWhenNoActiveWindow(): void
    {
        $result = $this->service->shouldSuppressAlert(1);
        $this->assertFalse($result);
    }

    public function testShouldSuppressAlertReturnsFalseForNonExistentMonitor(): void
    {
        $result = $this->service->shouldSuppressAlert(999);
        $this->assertFalse($result);
    }
}
