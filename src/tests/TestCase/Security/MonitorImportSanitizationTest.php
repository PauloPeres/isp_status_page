<?php
declare(strict_types=1);

namespace App\Test\TestCase\Security;

use App\Service\Import\MonitorImportService;
use Cake\TestSuite\TestCase;

/**
 * Security regression tests for MonitorImportService (import sanitization).
 *
 * Functional tests that verify the import service correctly detects formats,
 * handles edge cases gracefully, and does not crash on malformed input.
 */
class MonitorImportSanitizationTest extends TestCase
{
    private MonitorImportService $importService;

    public function setUp(): void
    {
        parent::setUp();
        $this->importService = new MonitorImportService();
    }

    /**
     * Verify detectFormat returns 'uptimerobot' for UptimeRobot JSON structure.
     */
    public function testDetectsUptimeRobotJson(): void
    {
        $content = json_encode([
            'stat' => 'ok',
            'monitors' => [
                ['friendly_name' => 'Test', 'url' => 'https://example.com', 'type' => 1],
            ],
        ]);

        $result = $this->importService->detectFormat($content);

        $this->assertSame(
            'uptimerobot',
            $result,
            'detectFormat must return "uptimerobot" for JSON with stat+monitors keys'
        );
    }

    /**
     * Verify detectFormat returns 'pingdom' for Pingdom CSV with hostname and resolution headers.
     */
    public function testDetectsPingdomCsv(): void
    {
        $content = "Name,Hostname,Type,Resolution\nMy Site,example.com,http,5\n";

        $result = $this->importService->detectFormat($content);

        $this->assertSame(
            'pingdom',
            $result,
            'detectFormat must return "pingdom" for CSV with hostname and resolution headers'
        );
    }

    /**
     * Verify detectFormat returns 'csv' for unknown/generic CSV format.
     */
    public function testDetectsGenericCsv(): void
    {
        $content = "name,url,type\nMy Site,https://example.com,http\n";

        $result = $this->importService->detectFormat($content);

        $this->assertSame(
            'csv',
            $result,
            'detectFormat must return "csv" for generic CSV format'
        );
    }

    /**
     * Verify parse handles empty content gracefully and returns an empty monitors array.
     */
    public function testParseHandlesEmptyContent(): void
    {
        $result = $this->importService->parse('');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('monitors', $result);
        $this->assertEmpty(
            $result['monitors'],
            'parse must return an empty monitors array for empty content'
        );
    }

    /**
     * Verify parse does not crash on malformed JSON input.
     */
    public function testParseHandlesMalformedJson(): void
    {
        // This malformed JSON should not cause an exception
        $result = $this->importService->parse('{invalid json content!!!', 'uptimerobot');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('monitors', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertEmpty(
            $result['monitors'],
            'parse must return empty monitors for malformed JSON'
        );
        $this->assertNotEmpty(
            $result['errors'],
            'parse must report errors for malformed JSON'
        );
    }
}
