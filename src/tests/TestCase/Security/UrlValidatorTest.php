<?php
declare(strict_types=1);

namespace App\Test\TestCase\Security;

use App\Service\UrlValidator;
use Cake\TestSuite\TestCase;

/**
 * Security regression tests for UrlValidator (SSRF protection).
 *
 * These are functional tests since UrlValidator is a pure utility class.
 * They verify that private/reserved IP ranges are blocked to prevent
 * Server-Side Request Forgery attacks.
 */
class UrlValidatorTest extends TestCase
{
    /**
     * Verify localhost (127.0.0.1) is blocked.
     */
    public function testBlocksLocalhost(): void
    {
        $this->assertTrue(
            UrlValidator::isPrivateIp('127.0.0.1'),
            'UrlValidator must block localhost 127.0.0.1'
        );
    }

    /**
     * Verify 10.x.x.x private range is blocked.
     */
    public function testBlocksPrivate10(): void
    {
        $this->assertTrue(
            UrlValidator::isPrivateIp('10.0.0.1'),
            'UrlValidator must block 10.0.0.1 (RFC 1918 private range)'
        );
    }

    /**
     * Verify 172.16.x.x private range is blocked.
     */
    public function testBlocksPrivate172(): void
    {
        $this->assertTrue(
            UrlValidator::isPrivateIp('172.16.0.1'),
            'UrlValidator must block 172.16.0.1 (RFC 1918 private range)'
        );
    }

    /**
     * Verify 192.168.x.x private range is blocked.
     */
    public function testBlocksPrivate192(): void
    {
        $this->assertTrue(
            UrlValidator::isPrivateIp('192.168.1.1'),
            'UrlValidator must block 192.168.1.1 (RFC 1918 private range)'
        );
    }

    /**
     * Verify the cloud metadata endpoint IP (169.254.169.254) is blocked.
     * This is critical for preventing SSRF attacks against cloud provider metadata services.
     */
    public function testBlocksMetadataEndpoint(): void
    {
        $this->assertTrue(
            UrlValidator::isPrivateIp('169.254.169.254'),
            'UrlValidator must block 169.254.169.254 (cloud metadata endpoint / link-local)'
        );
    }

    /**
     * Verify public IP 8.8.8.8 (Google DNS) is allowed.
     */
    public function testAllowsPublicIp(): void
    {
        $this->assertFalse(
            UrlValidator::isPrivateIp('8.8.8.8'),
            'UrlValidator must allow public IP 8.8.8.8'
        );
    }

    /**
     * Verify public IP 1.1.1.1 (Cloudflare DNS) is allowed.
     */
    public function testAllowsPublicIp2(): void
    {
        $this->assertFalse(
            UrlValidator::isPrivateIp('1.1.1.1'),
            'UrlValidator must allow public IP 1.1.1.1'
        );
    }

    /**
     * Verify isUrlSafe rejects URLs without a valid host.
     */
    public function testIsUrlSafeRejectsNoHost(): void
    {
        $this->assertFalse(
            UrlValidator::isUrlSafe('not-a-url'),
            'UrlValidator::isUrlSafe must return false for strings without a valid host'
        );
    }
}
