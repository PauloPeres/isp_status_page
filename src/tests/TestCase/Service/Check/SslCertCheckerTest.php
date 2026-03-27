<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Check;

use App\Model\Entity\Monitor;
use App\Service\Check\SslCertChecker;
use Cake\TestSuite\TestCase;

/**
 * SslCertChecker Test Case
 */
class SslCertCheckerTest extends TestCase
{
    /**
     * Test getType returns correct identifier
     */
    public function testGetType(): void
    {
        $checker = new SslCertChecker();

        $this->assertEquals('ssl', $checker->getType());
    }

    /**
     * Test getName returns human-readable name
     */
    public function testGetName(): void
    {
        $checker = new SslCertChecker();

        $this->assertEquals('SSL Certificate Checker', $checker->getName());
    }

    /**
     * Test valid cert = success (expires in 90 days, warning at 30)
     */
    public function testValidCertReturnsSuccess(): void
    {
        $now = time();
        $validFrom = $now - (30 * 86400); // 30 days ago
        $validTo = $now + (90 * 86400);   // 90 days from now

        $certInfo = [
            'subject' => ['CN' => 'example.com'],
            'issuer' => ['O' => 'Let\'s Encrypt', 'CN' => 'R3'],
            'validFrom_time_t' => $validFrom,
            'validTo_time_t' => $validTo,
        ];

        $socketFactory = function ($host, $port, $timeout) use ($certInfo) {
            return $certInfo;
        };

        $checker = new SslCertChecker($socketFactory);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'SSL Test',
            'type' => 'ssl',
            'target' => 'example.com',
            'timeout' => 10,
            'configuration' => json_encode([
                'host' => 'example.com',
                'port' => 443,
                'warning_days' => 30,
            ]),
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('up', $result['status']);
        $this->assertNull($result['error_message']);
        $this->assertEquals('example.com', $result['metadata']['subject']);
        $this->assertArrayHasKey('days_remaining', $result['metadata']);
        $this->assertGreaterThan(30, $result['metadata']['days_remaining']);
    }

    /**
     * Test expiring cert = degraded (expires in 15 days, warning at 30)
     */
    public function testExpiringCertReturnsDegraded(): void
    {
        $now = time();
        $validFrom = $now - (350 * 86400); // 350 days ago
        $validTo = $now + (15 * 86400);    // 15 days from now

        $certInfo = [
            'subject' => ['CN' => 'example.com'],
            'issuer' => ['O' => 'Let\'s Encrypt', 'CN' => 'R3'],
            'validFrom_time_t' => $validFrom,
            'validTo_time_t' => $validTo,
        ];

        $socketFactory = function ($host, $port, $timeout) use ($certInfo) {
            return $certInfo;
        };

        $checker = new SslCertChecker($socketFactory);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'SSL Test',
            'type' => 'ssl',
            'target' => 'example.com',
            'timeout' => 10,
            'configuration' => json_encode([
                'host' => 'example.com',
                'port' => 443,
                'warning_days' => 30,
            ]),
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('degraded', $result['status']);
        $this->assertStringContainsString('expires in', $result['error_message']);
        $this->assertArrayHasKey('days_remaining', $result['metadata']);
        $this->assertLessThanOrEqual(30, $result['metadata']['days_remaining']);
    }

    /**
     * Test expired cert = down
     */
    public function testExpiredCertReturnsDown(): void
    {
        $now = time();
        $validFrom = $now - (400 * 86400); // 400 days ago
        $validTo = $now - (5 * 86400);     // expired 5 days ago

        $certInfo = [
            'subject' => ['CN' => 'example.com'],
            'issuer' => ['O' => 'Let\'s Encrypt', 'CN' => 'R3'],
            'validFrom_time_t' => $validFrom,
            'validTo_time_t' => $validTo,
        ];

        $socketFactory = function ($host, $port, $timeout) use ($certInfo) {
            return $certInfo;
        };

        $checker = new SslCertChecker($socketFactory);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'SSL Test',
            'type' => 'ssl',
            'target' => 'example.com',
            'timeout' => 10,
            'configuration' => json_encode([
                'host' => 'example.com',
                'port' => 443,
                'warning_days' => 30,
            ]),
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('down', $result['status']);
        $this->assertStringContainsString('expired', $result['error_message']);
        $this->assertLessThan(0, $result['metadata']['days_remaining']);
    }

    /**
     * Test null cert info (could not retrieve) = down
     */
    public function testNullCertInfoReturnsDown(): void
    {
        $socketFactory = function ($host, $port, $timeout) {
            return null;
        };

        $checker = new SslCertChecker($socketFactory);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'SSL Test',
            'type' => 'ssl',
            'target' => 'example.com',
            'timeout' => 10,
            'configuration' => json_encode([
                'host' => 'example.com',
                'port' => 443,
                'warning_days' => 30,
            ]),
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('down', $result['status']);
        $this->assertStringContainsString('Could not retrieve', $result['error_message']);
    }

    /**
     * Test connection exception = down
     */
    public function testConnectionExceptionReturnsDown(): void
    {
        $socketFactory = function ($host, $port, $timeout) {
            throw new \RuntimeException('Connection timed out');
        };

        $checker = new SslCertChecker($socketFactory);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'SSL Test',
            'type' => 'ssl',
            'target' => 'example.com',
            'timeout' => 10,
            'configuration' => json_encode([
                'host' => 'example.com',
                'port' => 443,
                'warning_days' => 30,
            ]),
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('down', $result['status']);
        $this->assertStringContainsString('Connection timed out', $result['error_message']);
    }

    /**
     * Test validateConfiguration with missing host
     */
    public function testValidateConfigurationMissingHost(): void
    {
        $checker = new SslCertChecker();

        $monitor = new Monitor([
            'id' => 1,
            'type' => 'ssl',
            'timeout' => 10,
            'configuration' => json_encode([
                'port' => 443,
            ]),
        ]);

        $this->assertFalse($checker->validateConfiguration($monitor));
    }

    /**
     * Test validateConfiguration with valid config
     */
    public function testValidateConfigurationValid(): void
    {
        $checker = new SslCertChecker();

        $monitor = new Monitor([
            'id' => 1,
            'type' => 'ssl',
            'target' => 'example.com',
            'timeout' => 10,
            'configuration' => json_encode([
                'host' => 'example.com',
                'port' => 443,
            ]),
        ]);

        $this->assertTrue($checker->validateConfiguration($monitor));
    }

    /**
     * Test validateConfiguration with invalid port
     */
    public function testValidateConfigurationInvalidPort(): void
    {
        $checker = new SslCertChecker();

        $monitor = new Monitor([
            'id' => 1,
            'type' => 'ssl',
            'target' => 'example.com',
            'timeout' => 10,
            'configuration' => json_encode([
                'host' => 'example.com',
                'port' => 99999,
            ]),
        ]);

        $this->assertFalse($checker->validateConfiguration($monitor));
    }
}
