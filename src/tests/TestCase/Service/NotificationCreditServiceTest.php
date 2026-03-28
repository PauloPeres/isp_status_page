<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\NotificationCreditService;
use Cake\TestSuite\TestCase;

/**
 * App\Service\NotificationCreditService Test Case
 */
class NotificationCreditServiceTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.Monitors',
        'app.Incidents',
        'app.NotificationCredits',
        'app.NotificationCreditTransactions',
    ];

    /**
     * @var \App\Service\NotificationCreditService
     */
    protected NotificationCreditService $service;

    /**
     * setUp method
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NotificationCreditService();
    }

    /**
     * Test getCredits creates a new record for an org without credits
     */
    public function testGetCreditsCreatesNewRecord(): void
    {
        $credits = $this->service->getCredits(2);

        $this->assertNotNull($credits);
        $this->assertSame(2, $credits->organization_id);
        $this->assertSame(0, $credits->balance);
    }

    /**
     * Test hasCredits for a free channel always returns true
     */
    public function testHasCreditsForFreeChannel(): void
    {
        $this->assertTrue($this->service->hasCredits(1, 'email'));
        $this->assertTrue($this->service->hasCredits(1, 'slack'));
        $this->assertTrue($this->service->hasCredits(1, 'discord'));
        $this->assertTrue($this->service->hasCredits(1, 'telegram'));
        $this->assertTrue($this->service->hasCredits(1, 'webhook'));
    }

    /**
     * Test hasCredits for SMS returns true when org has balance
     */
    public function testHasCreditsForSmsWithBalance(): void
    {
        // Org 1 has 50 credits in fixture
        $this->assertTrue($this->service->hasCredits(1, 'sms'));
    }

    /**
     * Test hasCredits for SMS returns false when org has no balance
     */
    public function testHasCreditsForSmsNoBalance(): void
    {
        // Org 2 has no credit record (will be created with 0 balance)
        $this->assertFalse($this->service->hasCredits(2, 'sms'));
    }

    /**
     * Test deduct reduces balance for paid channel
     */
    public function testDeductReducesBalance(): void
    {
        // Org 1 starts with 50 credits
        $result = $this->service->deduct(1, 'sms');
        $this->assertTrue($result);

        $credits = $this->service->getCredits(1);
        $this->assertSame(49, $credits->balance);
    }

    /**
     * Test deduct fails when org has no balance
     */
    public function testDeductFailsNoBalance(): void
    {
        // Org 2 has no credits
        $result = $this->service->deduct(2, 'sms');
        $this->assertFalse($result);
    }

    /**
     * Test getCostForChannel returns 0 for free channels
     */
    public function testGetCostForChannelFree(): void
    {
        $this->assertSame(0, $this->service->getCostForChannel('email'));
        $this->assertSame(0, $this->service->getCostForChannel('slack'));
        $this->assertSame(0, $this->service->getCostForChannel('discord'));
        $this->assertSame(0, $this->service->getCostForChannel('telegram'));
        $this->assertSame(0, $this->service->getCostForChannel('webhook'));
    }

    /**
     * Test getCostForChannel returns 1 for paid channels
     */
    public function testGetCostForChannelPaid(): void
    {
        $this->assertSame(1, $this->service->getCostForChannel('sms'));
        $this->assertSame(1, $this->service->getCostForChannel('whatsapp'));
    }
}
