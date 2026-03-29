<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\LoginThrottleService;
use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;

/**
 * App\Service\LoginThrottleService Test Case
 */
class LoginThrottleServiceTest extends TestCase
{
    protected LoginThrottleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LoginThrottleService();
        // Clear cache before each test
        Cache::clear('default');
    }

    protected function tearDown(): void
    {
        Cache::clear('default');
        unset($this->service);
        parent::tearDown();
    }

    public function testIsLockedReturnsFalseInitially(): void
    {
        $this->assertFalse($this->service->isLocked('test@example.com'));
    }

    public function testRecordFailureIncrementsAttempts(): void
    {
        $attempts = $this->service->recordFailure('test@example.com');
        $this->assertEquals(1, $attempts);

        $attempts = $this->service->recordFailure('test@example.com');
        $this->assertEquals(2, $attempts);
    }

    public function testIsLockedReturnsTrueAfterMaxAttempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->service->recordFailure('lock-test@example.com');
        }

        $this->assertTrue($this->service->isLocked('lock-test@example.com'));
    }

    public function testIsLockedReturnsFalseBeforeMaxAttempts(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->service->recordFailure('almost-locked@example.com');
        }

        $this->assertFalse($this->service->isLocked('almost-locked@example.com'));
    }

    public function testClearAttemptsResetsCounter(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->service->recordFailure('clear-test@example.com');
        }
        $this->assertTrue($this->service->isLocked('clear-test@example.com'));

        $this->service->clearAttempts('clear-test@example.com');
        $this->assertFalse($this->service->isLocked('clear-test@example.com'));
    }

    public function testGetRemainingAttemptsInitially(): void
    {
        $remaining = $this->service->getRemainingAttempts('fresh@example.com');
        $this->assertEquals(5, $remaining);
    }

    public function testGetRemainingAttemptsAfterFailures(): void
    {
        $this->service->recordFailure('remaining@example.com');
        $this->service->recordFailure('remaining@example.com');

        $remaining = $this->service->getRemainingAttempts('remaining@example.com');
        $this->assertEquals(3, $remaining);
    }

    public function testGetRemainingAttemptsNeverNegative(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->service->recordFailure('overdone@example.com');
        }

        $remaining = $this->service->getRemainingAttempts('overdone@example.com');
        $this->assertEquals(0, $remaining);
    }

    public function testDifferentIdentifiersAreIsolated(): void
    {
        $this->service->recordFailure('user-a@example.com');
        $this->service->recordFailure('user-a@example.com');

        $this->assertEquals(3, $this->service->getRemainingAttempts('user-a@example.com'));
        $this->assertEquals(5, $this->service->getRemainingAttempts('user-b@example.com'));
    }
}
