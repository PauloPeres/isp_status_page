<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\BadgesController Test Case
 *
 * @uses \App\Controller\BadgesController
 */
class BadgesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.Monitors',
        'app.MonitorChecks',
    ];

    public function testUptimeBadgeWithInvalidTokenReturnsNotFoundSvg(): void
    {
        $token = '00000000000000000000000000000000';
        $this->get('/badges/' . $token . '/uptime.svg');
        $this->assertResponseOk();
        $this->assertContentType('image/svg+xml');

        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('<svg', $body);
        $this->assertStringContainsString('not found', $body);
    }

    public function testStatusBadgeWithInvalidTokenReturnsNotFoundSvg(): void
    {
        $token = '00000000000000000000000000000000';
        $this->get('/badges/' . $token . '/status.svg');
        $this->assertResponseOk();
        $this->assertContentType('image/svg+xml');

        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('<svg', $body);
    }

    public function testBadgesArePublicNoAuthRequired(): void
    {
        // No session set
        $token = '00000000000000000000000000000000';
        $this->get('/badges/' . $token . '/uptime.svg');
        $this->assertResponseOk();
    }

    public function testBadgeResponseHasNoCacheHeaders(): void
    {
        $token = '00000000000000000000000000000000';
        $this->get('/badges/' . $token . '/uptime.svg');
        $this->assertResponseOk();
        $this->assertHeaderContains('Cache-Control', 'no-cache');
    }
}
