<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Check;

use App\Model\Entity\Monitor;
use App\Service\Check\PingChecker;
use Cake\TestSuite\TestCase;

/**
 * PingChecker Test Case
 */
class PingCheckerTest extends TestCase
{
    /**
     * Test getType returns correct identifier
     */
    public function testGetType(): void
    {
        $checker = new PingChecker();

        $this->assertEquals('ping', $checker->getType());
    }

    /**
     * Test getName returns human-readable name
     */
    public function testGetName(): void
    {
        $checker = new PingChecker();

        $this->assertEquals('Ping/ICMP Checker', $checker->getName());
    }

    /**
     * Test validateConfiguration with valid monitor
     */
    public function testValidateConfigurationWithValidMonitor(): void
    {
        $checker = new PingChecker();

        $monitor = new Monitor([
            'id' => 1,
            'type' => 'ping',
            'target' => 'example.com',
            'timeout' => 10,
        ]);

        $result = $checker->validateConfiguration($monitor);

        $this->assertTrue($result);
    }

    /**
     * Test validateConfiguration with IP address
     */
    public function testValidateConfigurationWithIpAddress(): void
    {
        $checker = new PingChecker();

        $monitor = new Monitor([
            'id' => 1,
            'type' => 'ping',
            'target' => '8.8.8.8',
            'timeout' => 10,
        ]);

        $result = $checker->validateConfiguration($monitor);

        $this->assertTrue($result);
    }

    /**
     * Test validateConfiguration with invalid host
     */
    public function testValidateConfigurationWithInvalidHost(): void
    {
        $checker = new PingChecker();

        $monitor = new Monitor([
            'id' => 1,
            'type' => 'ping',
            'target' => 'not a valid host!!!',
            'timeout' => 10,
        ]);

        $result = $checker->validateConfiguration($monitor);

        $this->assertFalse($result);
    }

    /**
     * Test prepareHost removes scheme
     */
    public function testPrepareHostRemovesScheme(): void
    {
        $checker = new PingChecker();
        $reflection = new \ReflectionClass($checker);
        $method = $reflection->getMethod('prepareHost');
        $method->setAccessible(true);

        $result = $method->invoke($checker, 'https://example.com');

        $this->assertEquals('example.com', $result);
    }

    /**
     * Test prepareHost removes path
     */
    public function testPrepareHostRemovesPath(): void
    {
        $checker = new PingChecker();
        $reflection = new \ReflectionClass($checker);
        $method = $reflection->getMethod('prepareHost');
        $method->setAccessible(true);

        $result = $method->invoke($checker, 'example.com/path/to/page');

        $this->assertEquals('example.com', $result);
    }

    /**
     * Test prepareHost removes port
     */
    public function testPrepareHostRemovesPort(): void
    {
        $checker = new PingChecker();
        $reflection = new \ReflectionClass($checker);
        $method = $reflection->getMethod('prepareHost');
        $method->setAccessible(true);

        $result = $method->invoke($checker, 'example.com:8080');

        $this->assertEquals('example.com', $result);
    }

    /**
     * Test buildPingCommand for Linux
     */
    public function testBuildPingCommandForLinux(): void
    {
        if (PHP_OS_FAMILY === 'Windows' || PHP_OS_FAMILY === 'Darwin') {
            $this->markTestSkipped('This test only runs on Linux');
        }

        $checker = new PingChecker();
        $reflection = new \ReflectionClass($checker);
        $method = $reflection->getMethod('buildPingCommand');
        $method->setAccessible(true);

        $result = $method->invoke($checker, 'example.com', 10);

        $this->assertStringContainsString('ping', $result);
        $this->assertStringContainsString('-c 4', $result);
        $this->assertStringContainsString('-W 10', $result);
        $this->assertStringContainsString('example.com', $result);
    }

    /**
     * Test buildPingCommand for macOS
     */
    public function testBuildPingCommandForMacOS(): void
    {
        if (PHP_OS_FAMILY !== 'Darwin') {
            $this->markTestSkipped('This test only runs on macOS');
        }

        $checker = new PingChecker();
        $reflection = new \ReflectionClass($checker);
        $method = $reflection->getMethod('buildPingCommand');
        $method->setAccessible(true);

        $result = $method->invoke($checker, 'example.com', 10);

        $this->assertStringContainsString('ping', $result);
        $this->assertStringContainsString('-c 4', $result);
        $this->assertStringContainsString('-t 10', $result);
        $this->assertStringContainsString('example.com', $result);
    }

    /**
     * Test buildPingCommand for Windows
     */
    public function testBuildPingCommandForWindows(): void
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            $this->markTestSkipped('This test only runs on Windows');
        }

        $checker = new PingChecker();
        $reflection = new \ReflectionClass($checker);
        $method = $reflection->getMethod('buildPingCommand');
        $method->setAccessible(true);

        $result = $method->invoke($checker, 'example.com', 10);

        $this->assertStringContainsString('ping', $result);
        $this->assertStringContainsString('-n 4', $result);
        $this->assertStringContainsString('-w 10000', $result);
        $this->assertStringContainsString('example.com', $result);
    }

    /**
     * Test parseUnixPingOutput with successful ping
     */
    public function testParseUnixPingOutputSuccess(): void
    {
        $checker = new PingChecker();
        $reflection = new \ReflectionClass($checker);
        $method = $reflection->getMethod('parseUnixPingOutput');
        $method->setAccessible(true);

        $output = <<<OUTPUT
PING example.com (93.184.216.34): 56 data bytes
64 bytes from 93.184.216.34: icmp_seq=0 ttl=56 time=10.123 ms
64 bytes from 93.184.216.34: icmp_seq=1 ttl=56 time=15.456 ms
64 bytes from 93.184.216.34: icmp_seq=2 ttl=56 time=12.789 ms
64 bytes from 93.184.216.34: icmp_seq=3 ttl=56 time=11.234 ms

--- example.com ping statistics ---
4 packets transmitted, 4 received, 0% packet loss, time 3004ms
rtt min/avg/max/mdev = 10.123/12.400/15.456/2.123 ms
OUTPUT;

        $result = $method->invoke($checker, $output);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['packet_loss']);
        $this->assertEquals(10.123, $result['min_latency']);
        $this->assertEquals(12.400, $result['avg_latency']);
        $this->assertEquals(15.456, $result['max_latency']);
    }

    /**
     * Test parseUnixPingOutput with packet loss
     */
    public function testParseUnixPingOutputWithPacketLoss(): void
    {
        $checker = new PingChecker();
        $reflection = new \ReflectionClass($checker);
        $method = $reflection->getMethod('parseUnixPingOutput');
        $method->setAccessible(true);

        $output = <<<OUTPUT
PING example.com (93.184.216.34): 56 data bytes
64 bytes from 93.184.216.34: icmp_seq=0 ttl=56 time=10.123 ms
Request timeout for icmp_seq 1
64 bytes from 93.184.216.34: icmp_seq=2 ttl=56 time=12.789 ms
64 bytes from 93.184.216.34: icmp_seq=3 ttl=56 time=11.234 ms

--- example.com ping statistics ---
4 packets transmitted, 3 received, 25% packet loss, time 3004ms
rtt min/avg/max/mdev = 10.123/11.382/12.789/1.123 ms
OUTPUT;

        $result = $method->invoke($checker, $output);

        $this->assertTrue($result['success']);
        $this->assertEquals(25, $result['packet_loss']);
        $this->assertEquals(10.123, $result['min_latency']);
        $this->assertEquals(11.382, $result['avg_latency']);
        $this->assertEquals(12.789, $result['max_latency']);
    }

    /**
     * Test parseUnixPingOutput with 100% packet loss
     */
    public function testParseUnixPingOutputWithTotalLoss(): void
    {
        $checker = new PingChecker();
        $reflection = new \ReflectionClass($checker);
        $method = $reflection->getMethod('parseUnixPingOutput');
        $method->setAccessible(true);

        $output = <<<OUTPUT
PING example.com (93.184.216.34): 56 data bytes
Request timeout for icmp_seq 0
Request timeout for icmp_seq 1
Request timeout for icmp_seq 2
Request timeout for icmp_seq 3

--- example.com ping statistics ---
4 packets transmitted, 0 received, 100% packet loss, time 4005ms
OUTPUT;

        $result = $method->invoke($checker, $output);

        $this->assertFalse($result['success']);
        $this->assertEquals(100, $result['packet_loss']);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Test parseWindowsPingOutput with successful ping
     */
    public function testParseWindowsPingOutputSuccess(): void
    {
        $checker = new PingChecker();
        $reflection = new \ReflectionClass($checker);
        $method = $reflection->getMethod('parseWindowsPingOutput');
        $method->setAccessible(true);

        $output = <<<OUTPUT
Pinging example.com [93.184.216.34] with 32 bytes of data:
Reply from 93.184.216.34: bytes=32 time=10ms TTL=56
Reply from 93.184.216.34: bytes=32 time=15ms TTL=56
Reply from 93.184.216.34: bytes=32 time=12ms TTL=56
Reply from 93.184.216.34: bytes=32 time=11ms TTL=56

Ping statistics for 93.184.216.34:
    Packets: Sent = 4, Received = 4, Lost = 0 (0% loss),
Approximate round trip times in milli-seconds:
    Minimum = 10ms, Maximum = 15ms, Average = 12ms
OUTPUT;

        $result = $method->invoke($checker, $output);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['packet_loss']);
        $this->assertEquals(10.0, $result['min_latency']);
        $this->assertEquals(12.0, $result['avg_latency']);
        $this->assertEquals(15.0, $result['max_latency']);
    }

    /**
     * Test parseWindowsPingOutput with packet loss
     */
    public function testParseWindowsPingOutputWithPacketLoss(): void
    {
        $checker = new PingChecker();
        $reflection = new \ReflectionClass($checker);
        $method = $reflection->getMethod('parseWindowsPingOutput');
        $method->setAccessible(true);

        $output = <<<OUTPUT
Pinging example.com [93.184.216.34] with 32 bytes of data:
Reply from 93.184.216.34: bytes=32 time=10ms TTL=56
Request timed out.
Reply from 93.184.216.34: bytes=32 time=12ms TTL=56
Reply from 93.184.216.34: bytes=32 time=11ms TTL=56

Ping statistics for 93.184.216.34:
    Packets: Sent = 4, Received = 3, Lost = 1 (25% loss),
Approximate round trip times in milli-seconds:
    Minimum = 10ms, Maximum = 12ms, Average = 11ms
OUTPUT;

        $result = $method->invoke($checker, $output);

        $this->assertTrue($result['success']);
        $this->assertEquals(25, $result['packet_loss']);
        $this->assertEquals(10.0, $result['min_latency']);
        $this->assertEquals(11.0, $result['avg_latency']);
        $this->assertEquals(12.0, $result['max_latency']);
    }

    /**
     * Test isValidHost with valid hostnames
     */
    public function testIsValidHostWithValidHostnames(): void
    {
        $checker = new PingChecker();
        $reflection = new \ReflectionClass($checker);
        $method = $reflection->getMethod('isValidHost');
        $method->setAccessible(true);

        $validHosts = [
            'example.com',
            'sub.example.com',
            'sub-domain.example.com',
            'example.co.uk',
            '8.8.8.8',
            '2001:4860:4860::8888',
        ];

        foreach ($validHosts as $host) {
            $result = $method->invoke($checker, $host);
            $this->assertTrue($result, "Host should be valid: {$host}");
        }
    }

    /**
     * Test isValidHost with invalid hostnames
     */
    public function testIsValidHostWithInvalidHostnames(): void
    {
        $checker = new PingChecker();
        $reflection = new \ReflectionClass($checker);
        $method = $reflection->getMethod('isValidHost');
        $method->setAccessible(true);

        $invalidHosts = [
            '',
            'not a host!!!',
            'javascript:alert(1)',
            '999.999.999.999', // Invalid IP
        ];

        foreach ($invalidHosts as $host) {
            $result = $method->invoke($checker, $host);
            $this->assertFalse($result, "Host should be invalid: {$host}");
        }
    }

    /**
     * Test check with mock successful ping
     */
    public function testCheckWithSuccessfulPing(): void
    {
        // Create a mock PingChecker that overrides executePing
        $checker = $this->getMockBuilder(PingChecker::class)
            ->onlyMethods(['executePing'])
            ->getMock();

        // Mock output based on current OS
        if (PHP_OS_FAMILY === 'Windows') {
            $mockOutput = <<<OUTPUT
Pinging example.com [93.184.216.34] with 32 bytes of data:
Reply from 93.184.216.34: bytes=32 time=10ms TTL=56
Reply from 93.184.216.34: bytes=32 time=15ms TTL=56
Reply from 93.184.216.34: bytes=32 time=12ms TTL=56
Reply from 93.184.216.34: bytes=32 time=11ms TTL=56

Ping statistics for 93.184.216.34:
    Packets: Sent = 4, Received = 4, Lost = 0 (0% loss),
Approximate round trip times in milli-seconds:
    Minimum = 10ms, Maximum = 15ms, Average = 12ms
OUTPUT;
        } else {
            $mockOutput = <<<OUTPUT
PING example.com (93.184.216.34): 56 data bytes
64 bytes from 93.184.216.34: icmp_seq=0 ttl=56 time=10.123 ms
64 bytes from 93.184.216.34: icmp_seq=1 ttl=56 time=15.456 ms
64 bytes from 93.184.216.34: icmp_seq=2 ttl=56 time=12.789 ms
64 bytes from 93.184.216.34: icmp_seq=3 ttl=56 time=11.234 ms

--- example.com ping statistics ---
4 packets transmitted, 4 received, 0% packet loss, time 3004ms
rtt min/avg/max/mdev = 10.123/12.400/15.456/2.123 ms
OUTPUT;
        }

        $checker->method('executePing')->willReturn($mockOutput);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Test Monitor',
            'type' => 'ping',
            'target' => 'example.com',
            'timeout' => 10,
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('up', $result['status']);
        $this->assertNull($result['error_message']);
        $this->assertIsInt($result['response_time']);
        $this->assertArrayHasKey('host', $result['metadata']);
        $this->assertEquals(0, $result['metadata']['packet_loss']);
    }

    /**
     * Test check detects degraded performance with packet loss
     */
    public function testCheckDetectsDegradedPerformanceWithPacketLoss(): void
    {
        // Create a mock PingChecker
        $checker = $this->getMockBuilder(PingChecker::class)
            ->onlyMethods(['executePing'])
            ->getMock();

        // Mock output with packet loss
        if (PHP_OS_FAMILY === 'Windows') {
            $mockOutput = <<<OUTPUT
Pinging example.com [93.184.216.34] with 32 bytes of data:
Reply from 93.184.216.34: bytes=32 time=10ms TTL=56
Request timed out.
Reply from 93.184.216.34: bytes=32 time=12ms TTL=56
Reply from 93.184.216.34: bytes=32 time=11ms TTL=56

Ping statistics for 93.184.216.34:
    Packets: Sent = 4, Received = 3, Lost = 1 (25% loss),
Approximate round trip times in milli-seconds:
    Minimum = 10ms, Maximum = 12ms, Average = 11ms
OUTPUT;
        } else {
            $mockOutput = <<<OUTPUT
PING example.com (93.184.216.34): 56 data bytes
64 bytes from 93.184.216.34: icmp_seq=0 ttl=56 time=10.123 ms
Request timeout for icmp_seq 1
64 bytes from 93.184.216.34: icmp_seq=2 ttl=56 time=12.789 ms
64 bytes from 93.184.216.34: icmp_seq=3 ttl=56 time=11.234 ms

--- example.com ping statistics ---
4 packets transmitted, 3 received, 25% packet loss, time 3004ms
rtt min/avg/max/mdev = 10.123/11.382/12.789/1.123 ms
OUTPUT;
        }

        $checker->method('executePing')->willReturn($mockOutput);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Test Monitor',
            'type' => 'ping',
            'target' => 'example.com',
            'timeout' => 10,
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('degraded', $result['status']);
        $this->assertStringContainsString('Packet loss', $result['error_message']);
        $this->assertEquals(25, $result['metadata']['packet_loss']);
    }

    /**
     * Test check with 100% packet loss
     */
    public function testCheckWith100PercentPacketLoss(): void
    {
        // Create a mock PingChecker
        $checker = $this->getMockBuilder(PingChecker::class)
            ->onlyMethods(['executePing'])
            ->getMock();

        // Mock output with 100% packet loss
        if (PHP_OS_FAMILY === 'Windows') {
            $mockOutput = <<<OUTPUT
Pinging example.com [93.184.216.34] with 32 bytes of data:
Request timed out.
Request timed out.
Request timed out.
Request timed out.

Ping statistics for 93.184.216.34:
    Packets: Sent = 4, Received = 0, Lost = 4 (100% loss),
OUTPUT;
        } else {
            $mockOutput = <<<OUTPUT
PING example.com (93.184.216.34): 56 data bytes
Request timeout for icmp_seq 0
Request timeout for icmp_seq 1
Request timeout for icmp_seq 2
Request timeout for icmp_seq 3

--- example.com ping statistics ---
4 packets transmitted, 0 received, 100% packet loss, time 4005ms
OUTPUT;
        }

        $checker->method('executePing')->willReturn($mockOutput);

        $monitor = new Monitor([
            'id' => 1,
            'name' => 'Test Monitor',
            'type' => 'ping',
            'target' => 'example.com',
            'timeout' => 10,
        ]);

        $result = $checker->check($monitor);

        $this->assertEquals('down', $result['status']);
        $this->assertNotNull($result['error_message']);
        $this->assertEquals(100, $result['metadata']['packet_loss']);
    }
}
