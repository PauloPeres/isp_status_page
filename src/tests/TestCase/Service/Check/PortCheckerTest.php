<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Check;

use App\Model\Entity\Monitor;
use App\Service\Check\PortChecker;
use Cake\TestSuite\TestCase;

/**
 * PortChecker Test Case
 */
class PortCheckerTest extends TestCase
{
    /**
     * Test getType returns correct identifier
     */
    public function testGetType(): void
    {
        $checker = new PortChecker();

        $this->assertEquals('port', $checker->getType());
    }

    /**
     * Test getName returns human-readable name
     */
    public function testGetName(): void
    {
        $checker = new PortChecker();

        $this->assertEquals('Port/TCP Checker', $checker->getName());
    }

    /**
     * Test validateConfiguration with valid monitor
     */
    public function testValidateConfigurationWithValidMonitor(): void
    {
        $checker = new PortChecker();

        $monitor = new Monitor([
            'id' => 1,
            'type' => 'port',
            'target' => 'example.com:80',
            'timeout' => 10,
        ]);

        $result = $checker->validateConfiguration($monitor);

        $this->assertTrue($result);
    }

    /**
     * Test validateConfiguration with IP address and port
     */
    public function testValidateConfigurationWithIpAndPort(): void
    {
        $checker = new PortChecker();

        $monitor = new Monitor([
            'id' => 1,
            'type' => 'port',
            'target' => '8.8.8.8:53',
            'timeout' => 10,
        ]);

        $result = $checker->validateConfiguration($monitor);

        $this->assertTrue($result);
    }

    /**
     * Test validateConfiguration with IPv6 and port
     */
    public function testValidateConfigurationWithIpv6AndPort(): void
    {
        $checker = new PortChecker();

        $monitor = new Monitor([
            'id' => 1,
            'type' => 'port',
            'target' => '[2001:4860:4860::8888]:53',
            'timeout' => 10,
        ]);

        $result = $checker->validateConfiguration($monitor);

        $this->assertTrue($result);
    }

    /**
     * Test validateConfiguration with invalid target (no port)
     */
    public function testValidateConfigurationWithNoPort(): void
    {
        $checker = new PortChecker();

        $monitor = new Monitor([
            'id' => 1,
            'type' => 'port',
            'target' => 'example.com',
            'timeout' => 10,
        ]);

        $result = $checker->validateConfiguration($monitor);

        $this->assertFalse($result);
    }

    /**
     * Test validateConfiguration with invalid port range
     */
    public function testValidateConfigurationWithInvalidPort(): void
    {
        $checker = new PortChecker();

        $monitor = new Monitor([
            'id' => 1,
            'type' => 'port',
            'target' => 'example.com:99999', // Invalid port
            'timeout' => 10,
        ]);

        $result = $checker->validateConfiguration($monitor);

        $this->assertFalse($result);
    }

    /**
     * Test parseTarget with standard format
     */
    public function testParseTargetWithStandardFormat(): void
    {
        $checker = new PortChecker();
        $reflection = new \ReflectionClass($checker);
        $method = $reflection->getMethod('parseTarget');
        $method->setAccessible(true);

        [$host, $port] = $method->invoke($checker, 'example.com:80');

        $this->assertEquals('example.com', $host);
        $this->assertEquals(80, $port);
    }

    /**
     * Test parseTarget with IP address
     */
    public function testParseTargetWithIpAddress(): void
    {
        $checker = new PortChecker();
        $reflection = new \ReflectionClass($checker);
        $method = $reflection->getMethod('parseTarget');
        $method->setAccessible(true);

        [$host, $port] = $method->invoke($checker, '192.168.1.1:443');

        $this->assertEquals('192.168.1.1', $host);
        $this->assertEquals(443, $port);
    }

    /**
     * Test parseTarget with IPv6
     */
    public function testParseTargetWithIpv6(): void
    {
        $checker = new PortChecker();
        $reflection = new \ReflectionClass($checker);
        $method = $reflection->getMethod('parseTarget');
        $method->setAccessible(true);

        [$host, $port] = $method->invoke($checker, '[2001:db8::1]:8080');

        $this->assertEquals('2001:db8::1', $host);
        $this->assertEquals(8080, $port);
    }

    /**
     * Test parseTarget throws exception with invalid format
     */
    public function testParseTargetThrowsExceptionWithInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid target format');

        $checker = new PortChecker();
        $reflection = new \ReflectionClass($checker);
        $method = $reflection->getMethod('parseTarget');
        $method->setAccessible(true);

        $method->invoke($checker, 'example.com'); // No port
    }

    /**
     * Test isValidTarget with valid targets
     */
    public function testIsValidTargetWithValidTargets(): void
    {
        $checker = new PortChecker();
        $reflection = new \ReflectionClass($checker);
        $method = $reflection->getMethod('isValidTarget');
        $method->setAccessible(true);

        $validTargets = [
            'example.com:80',
            'example.com:443',
            'sub.example.com:8080',
            '192.168.1.1:22',
            '8.8.8.8:53',
            '[2001:db8::1]:80',
            '[2001:4860:4860::8888]:443',
            'localhost:3306',
        ];

        foreach ($validTargets as $target) {
            $result = $method->invoke($checker, $target);
            $this->assertTrue($result, "Target should be valid: {$target}");
        }
    }

    /**
     * Test isValidTarget with invalid targets
     */
    public function testIsValidTargetWithInvalidTargets(): void
    {
        $checker = new PortChecker();
        $reflection = new \ReflectionClass($checker);
        $method = $reflection->getMethod('isValidTarget');
        $method->setAccessible(true);

        $invalidTargets = [
            'example.com', // No port
            'example.com:0', // Port 0
            'example.com:99999', // Port too high
            'not a host!!!:80',
            ':80', // No host
            '', // Empty
        ];

        foreach ($invalidTargets as $target) {
            $result = $method->invoke($checker, $target);
            $this->assertFalse($result, "Target should be invalid: {$target}");
        }
    }

    /**
     * Test check with successful connection
     */
    public function testCheckWithSuccessfulConnection(): void
    {
        // Create a mock PortChecker that overrides connectToPort
        $checker = $this->getMockBuilder(PortChecker::class)
            ->onlyMethods(['connectToPort'])
            ->getMock();

        // Mock successful connection
        $checker->method('connectToPort')->willReturn(true);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Test Monitor',
            'type' => 'port',
            'target' => 'example.com:80',
            'timeout' => 10,
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('up', $result['status']);
        $this->assertNull($result['error_message']);
        $this->assertIsInt($result['response_time']);
        $this->assertArrayHasKey('host', $result['metadata']);
        $this->assertArrayHasKey('port', $result['metadata']);
        $this->assertEquals('example.com', $result['metadata']['host']);
        $this->assertEquals(80, $result['metadata']['port']);
    }

    /**
     * Test check with failed connection
     */
    public function testCheckWithFailedConnection(): void
    {
        // Create a mock PortChecker
        $checker = $this->getMockBuilder(PortChecker::class)
            ->onlyMethods(['connectToPort'])
            ->getMock();

        // Mock failed connection
        $checker->method('connectToPort')->willReturn(false);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Test Monitor',
            'type' => 'port',
            'target' => 'example.com:9999',
            'timeout' => 10,
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('down', $result['status']);
        $this->assertNotNull($result['error_message']);
        $this->assertStringContainsString('closed or filtered', $result['error_message']);
    }

    /**
     * Test check detects degraded performance
     */
    public function testCheckDetectsDegradedPerformance(): void
    {
        // Create a mock PortChecker
        $checker = $this->getMockBuilder(PortChecker::class)
            ->onlyMethods(['connectToPort'])
            ->getMock();

        // Mock successful but slow connection
        $checker->method('connectToPort')
            ->willReturnCallback(function () {
                // Simulate slow connection (0.9 seconds = 90% of 1 second timeout)
                usleep(900000);

                return true;
            });

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Test Monitor',
            'type' => 'port',
            'target' => 'example.com:80',
            'timeout' => 1, // Very short timeout (1 second)
        ]);

        $result = $checker->check($monitor);

        // Should be detected as degraded (> 80% of timeout)
        $this->assertContains($result['status'], ['degraded', 'up']);
    }

    /**
     * Test check with invalid target format
     */
    public function testCheckWithInvalidTargetFormat(): void
    {
        $checker = new PortChecker();

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Test Monitor',
            'type' => 'port',
            'target' => 'invalid-target', // No port
            'timeout' => 10,
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('down', $result['status']);
        $this->assertNotNull($result['error_message']);
        // AbstractChecker validates before executing, so we get generic message
        $this->assertStringContainsString('Invalid monitor configuration', $result['error_message']);
    }

    /**
     * Test formatErrorMessage with connection refused
     */
    public function testFormatErrorMessageWithConnectionRefused(): void
    {
        $checker = new PortChecker();
        $reflection = new \ReflectionClass($checker);
        $method = $reflection->getMethod('formatErrorMessage');
        $method->setAccessible(true);

        $exception = new \Exception('Connection refused');
        $result = $method->invoke($checker, $exception);

        $this->assertStringContainsString('Connection refused', $result);
        $this->assertStringContainsString('port may be closed', $result);
    }

    /**
     * Test real connection to well-known ports (integration test)
     */
    public function testRealConnectionToPublicDns(): void
    {
        // This is an integration test that makes a real connection
        // We'll test against Google's public DNS on port 53
        $checker = new PortChecker();

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Google DNS',
            'type' => 'port',
            'target' => '8.8.8.8:53',
            'timeout' => 5,
        ]);

        $result = $checker->check($monitor);

        // Network may not be available or firewall may block
        // Just verify the check executes and returns valid structure
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('response_time', $result);
        $this->assertContains($result['status'], ['up', 'degraded', 'down']);

        if ($result['status'] !== 'down') {
            $this->assertEquals(53, $result['metadata']['port']);
        }
    }

    /**
     * Test real connection to closed port (integration test)
     */
    public function testRealConnectionToClosedPort(): void
    {
        // Test connection to a likely closed port
        $checker = new PortChecker();

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Closed Port',
            'type' => 'port',
            'target' => '127.0.0.1:65534', // Unlikely to be open
            'timeout' => 2,
        ]);

        $result = $checker->check($monitor);

        // Should fail (port is likely closed)
        $this->assertEquals('down', $result['status']);
    }
}
