<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\WebhooksController Test Case
 *
 * @uses \App\Controller\WebhooksController
 */
class WebhooksControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.Plans',
    ];

    /**
     * Test webhook endpoint accepts POST requests
     */
    public function testStripeWebhookAcceptsPost(): void
    {
        $this->configRequest([
            'headers' => [
                'Content-Type' => 'application/json',
                'Stripe-Signature' => 'invalid_signature',
            ],
        ]);

        $this->post('/webhooks/stripe', '{"type":"test"}');

        // Should return 400 because signature is invalid (no webhook secret configured in test)
        $this->assertResponseCode(400);
    }

    /**
     * Test webhook endpoint rejects GET requests
     */
    public function testStripeWebhookRejectsGet(): void
    {
        $this->get('/webhooks/stripe');

        $this->assertResponseCode(405);
    }

    /**
     * Test invalid signature returns 400
     */
    public function testInvalidSignatureReturns400(): void
    {
        $this->configRequest([
            'headers' => [
                'Content-Type' => 'application/json',
                'Stripe-Signature' => 't=1234567890,v1=abc123',
            ],
        ]);

        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => ['object' => []],
        ]);

        $this->post('/webhooks/stripe', $payload);

        $this->assertResponseCode(400);
        $this->assertResponseContains('Invalid webhook');
    }

    /**
     * Test webhook endpoint does not require authentication
     */
    public function testWebhookDoesNotRequireAuth(): void
    {
        // No session set - should still be accessible (not redirect to login)
        $this->configRequest([
            'headers' => [
                'Content-Type' => 'application/json',
                'Stripe-Signature' => 'test',
            ],
        ]);

        $this->post('/webhooks/stripe', '{}');

        // Should return 400 (bad signature), not 302 (redirect to login)
        $this->assertResponseCode(400);
    }
}
