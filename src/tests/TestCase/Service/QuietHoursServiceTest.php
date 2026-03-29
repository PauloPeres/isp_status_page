<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\QuietHoursService;
use Cake\TestSuite\TestCase;

/**
 * App\Service\QuietHoursService Test Case
 */
class QuietHoursServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Organizations',
    ];

    protected QuietHoursService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QuietHoursService();
    }

    protected function tearDown(): void
    {
        unset($this->service);
        parent::tearDown();
    }

    public function testIsInQuietHoursReturnsFalseWhenDisabled(): void
    {
        // Org 1 from fixture has no quiet hours enabled
        $result = $this->service->isInQuietHours(1);
        $this->assertFalse($result);
    }

    public function testIsInQuietHoursReturnsFalseForNonExistentOrg(): void
    {
        $result = $this->service->isInQuietHours(999);
        $this->assertFalse($result);
    }

    public function testShouldSuppressAlertReturnsFalseWhenNotInQuietHours(): void
    {
        $result = $this->service->shouldSuppressAlert(1, 'critical');
        $this->assertFalse($result);
    }

    public function testShouldSuppressAlertReturnsFalseForNonExistentOrg(): void
    {
        $result = $this->service->shouldSuppressAlert(999, 'warning');
        $this->assertFalse($result);
    }

    public function testIsInQuietHoursWithEnabledOrg(): void
    {
        // Try to set quiet_hours columns; skip if migration hasn't added them
        $orgsTable = $this->getTableLocator()->get('Organizations');
        $schema = $orgsTable->getSchema();

        if (!$schema->getColumn('quiet_hours_enabled')) {
            $this->markTestSkipped('quiet_hours columns not present in test schema');
        }

        $org = $orgsTable->get(1);

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $startHour = ((int)$now->format('H') - 1 + 24) % 24;
        $endHour = ((int)$now->format('H') + 1) % 24;

        $conn = $orgsTable->getConnection();
        $conn->execute(
            'UPDATE organizations SET quiet_hours_enabled = ?, quiet_hours_start = ?, quiet_hours_end = ?, quiet_hours_timezone = ?, quiet_hours_suppress_level = ? WHERE id = ?',
            [true, str_pad((string)$startHour, 2, '0', STR_PAD_LEFT) . ':00', str_pad((string)$endHour, 2, '0', STR_PAD_LEFT) . ':00', 'UTC', 'all', 1]
        );

        $result = $this->service->isInQuietHours(1);
        $this->assertTrue($result);
    }

    public function testShouldSuppressAlertNonCriticalLevel(): void
    {
        $orgsTable = $this->getTableLocator()->get('Organizations');
        $schema = $orgsTable->getSchema();

        if (!$schema->getColumn('quiet_hours_enabled')) {
            $this->markTestSkipped('quiet_hours columns not present in test schema');
        }

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $startHour = ((int)$now->format('H') - 1 + 24) % 24;
        $endHour = ((int)$now->format('H') + 1) % 24;

        $conn = $orgsTable->getConnection();
        $conn->execute(
            'UPDATE organizations SET quiet_hours_enabled = ?, quiet_hours_start = ?, quiet_hours_end = ?, quiet_hours_timezone = ?, quiet_hours_suppress_level = ? WHERE id = ?',
            [true, str_pad((string)$startHour, 2, '0', STR_PAD_LEFT) . ':00', str_pad((string)$endHour, 2, '0', STR_PAD_LEFT) . ':00', 'UTC', 'non_critical', 1]
        );

        // Critical alerts should NOT be suppressed
        $this->assertFalse($this->service->shouldSuppressAlert(1, 'critical'));

        // Warning alerts should be suppressed
        $this->assertTrue($this->service->shouldSuppressAlert(1, 'warning'));
    }
}
