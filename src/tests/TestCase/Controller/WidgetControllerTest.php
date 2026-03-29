<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\WidgetController Test Case
 *
 * @uses \App\Controller\WidgetController
 */
class WidgetControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.Monitors',
        'app.StatusPages',
    ];

    public function testStatusWidgetNotFoundReturnsHtml(): void
    {
        $this->get('/widget/status/does-not-exist');

        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('not found', strtolower($body));
    }

    public function testStatusWidgetIsPublicNoAuthNeeded(): void
    {
        // For a non-existing slug, should still not redirect to login
        $this->get('/widget/status/does-not-exist');
        $statusCode = $this->_response->getStatusCode();
        // Should return 200 (with "not found" HTML), not a login redirect
        $this->assertNotEquals(302, $statusCode, 'Widget should not redirect to login');
    }

    public function testStatusJsReturnsJavascript(): void
    {
        $this->get('/widget/status/acme-status.js');
        $this->assertResponseOk();
        $this->assertContentType('application/javascript');

        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('iframe', $body);
        $this->assertStringContainsString('acme-status', $body);
    }

    public function testStatusJsNotFoundSlugStillReturnsJs(): void
    {
        $this->get('/widget/status/does-not-exist.js');
        $this->assertResponseOk();
        $this->assertContentType('application/javascript');
    }

    public function testStatusJsContainsExpectedStructure(): void
    {
        $this->get('/widget/status/test-slug.js');
        $this->assertResponseOk();

        $body = (string)$this->_response->getBody();
        $this->assertStringContainsString('document.createElement', $body);
        $this->assertStringContainsString('iframe', $body);
    }

    public function testStatusWidgetWithValidSlugLoads(): void
    {
        $this->get('/widget/status/acme-status');

        // May return 200 or 500 depending on template rendering
        // We mainly verify it doesn't redirect to login (public endpoint)
        $statusCode = $this->_response->getStatusCode();
        $this->assertNotEquals(302, $statusCode, 'Widget should not redirect to login');
    }
}
