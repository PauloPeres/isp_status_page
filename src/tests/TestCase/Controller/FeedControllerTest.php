<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\FeedController Test Case
 *
 * @uses \App\Controller\FeedController
 */
class FeedControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Organizations',
        'app.OrganizationUsers',
        'app.Users',
        'app.Monitors',
        'app.Incidents',
        'app.Settings',
    ];

    public function testIncidentsFeedIsPublic(): void
    {
        // No session - this should work without auth
        $this->get('/feed/incidents.rss');
        $this->assertResponseOk();
    }

    public function testIncidentsFeedSetsVariables(): void
    {
        $this->get('/feed/incidents.rss');
        $this->assertResponseOk();

        $incidents = $this->viewVariable('incidents');
        $this->assertNotNull($incidents);

        $siteName = $this->viewVariable('siteName');
        $this->assertNotNull($siteName);
    }

    public function testIncidentsFeedReturnsRssContentType(): void
    {
        $this->get('/feed/incidents.rss');
        $this->assertResponseOk();
        $this->assertContentType('application/rss+xml');
    }
}
