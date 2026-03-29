<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\TwoFactorService;
use Cake\TestSuite\TestCase;

/**
 * App\Service\TwoFactorService Test Case
 */
class TwoFactorServiceTest extends TestCase
{
    protected TwoFactorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TwoFactorService();
    }

    protected function tearDown(): void
    {
        unset($this->service);
        parent::tearDown();
    }

    public function testGenerateSecretReturnsBase32String(): void
    {
        $secret = $this->service->generateSecret();

        $this->assertNotEmpty($secret);
        // Base32 contains only A-Z and 2-7
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
        // 20 bytes = 32 base32 chars
        $this->assertEquals(32, strlen($secret));
    }

    public function testGenerateSecretIsUnique(): void
    {
        $secret1 = $this->service->generateSecret();
        $secret2 = $this->service->generateSecret();

        $this->assertNotEquals($secret1, $secret2);
    }

    public function testGetQrCodeUrlReturnsOtpauthUri(): void
    {
        $secret = $this->service->generateSecret();
        $url = $this->service->getQrCodeUrl('user@example.com', $secret);

        $this->assertStringStartsWith('otpauth://totp/', $url);
        $this->assertStringContainsString('user%40example.com', $url);
        $this->assertStringContainsString('secret=' . $secret, $url);
        $this->assertStringContainsString('issuer=ISP+Status+Page', $url);
        $this->assertStringContainsString('algorithm=SHA1', $url);
        $this->assertStringContainsString('digits=6', $url);
        $this->assertStringContainsString('period=30', $url);
    }

    public function testVerifyCodeRejectsTooShort(): void
    {
        $secret = $this->service->generateSecret();
        $this->assertFalse($this->service->verifyCode($secret, '12345'));
    }

    public function testVerifyCodeRejectsTooLong(): void
    {
        $secret = $this->service->generateSecret();
        $this->assertFalse($this->service->verifyCode($secret, '1234567'));
    }

    public function testVerifyCodeRejectsNonDigits(): void
    {
        $secret = $this->service->generateSecret();
        $this->assertFalse($this->service->verifyCode($secret, 'abcdef'));
    }

    public function testVerifyCodeRejectsRandomCode(): void
    {
        $secret = $this->service->generateSecret();
        // A random code has a 3/1000000 chance of being valid, effectively zero
        $this->assertFalse($this->service->verifyCode($secret, '000000'));
    }

    public function testGenerateRecoveryCodesReturnsCorrectCount(): void
    {
        $codes = $this->service->generateRecoveryCodes();
        $this->assertCount(8, $codes);

        $codes = $this->service->generateRecoveryCodes(5);
        $this->assertCount(5, $codes);
    }

    public function testGenerateRecoveryCodesFormat(): void
    {
        $codes = $this->service->generateRecoveryCodes(1);
        $code = $codes[0];

        // Format: XXXXXXXX-XXXXXXXX (hex digits with dash)
        $this->assertMatchesRegularExpression('/^[A-F0-9]{8}-[A-F0-9]{8}$/', $code);
    }

    public function testGenerateRecoveryCodesAreUnique(): void
    {
        $codes = $this->service->generateRecoveryCodes(10);
        $uniqueCodes = array_unique($codes);
        $this->assertCount(count($codes), $uniqueCodes, 'All recovery codes should be unique');
    }

    public function testHashRecoveryCodesReturnsBcryptHashes(): void
    {
        $codes = $this->service->generateRecoveryCodes(3);
        $hashed = $this->service->hashRecoveryCodes($codes);

        $this->assertCount(3, $hashed);
        foreach ($hashed as $hash) {
            $this->assertStringStartsWith('$2y$', $hash);
        }
    }

    public function testVerifyRecoveryCodeFindsMatch(): void
    {
        $codes = $this->service->generateRecoveryCodes(3);
        $hashed = $this->service->hashRecoveryCodes($codes);

        // The second code should match at index 1
        $index = $this->service->verifyRecoveryCode($codes[1], $hashed);
        $this->assertEquals(1, $index);
    }

    public function testVerifyRecoveryCodeReturnsMinus1ForNoMatch(): void
    {
        $codes = $this->service->generateRecoveryCodes(3);
        $hashed = $this->service->hashRecoveryCodes($codes);

        $index = $this->service->verifyRecoveryCode('INVALID-CODE1234', $hashed);
        $this->assertEquals(-1, $index);
    }

    public function testVerifyRecoveryCodeIsCaseInsensitive(): void
    {
        $codes = $this->service->generateRecoveryCodes(1);
        $hashed = $this->service->hashRecoveryCodes($codes);

        // Try lowercase version
        $lower = strtolower($codes[0]);
        $index = $this->service->verifyRecoveryCode($lower, $hashed);
        $this->assertEquals(0, $index);
    }

    public function testVerifyRecoveryCodeTrimsWhitespace(): void
    {
        $codes = $this->service->generateRecoveryCodes(1);
        $hashed = $this->service->hashRecoveryCodes($codes);

        $index = $this->service->verifyRecoveryCode('  ' . $codes[0] . '  ', $hashed);
        $this->assertEquals(0, $index);
    }
}
