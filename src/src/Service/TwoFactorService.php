<?php
declare(strict_types=1);

namespace App\Service;

/**
 * TwoFactorService
 *
 * Handles TOTP-based two-factor authentication including secret generation,
 * code verification, QR code URL generation, and recovery codes.
 *
 * Uses HMAC-based OTP algorithm (RFC 6238 / RFC 4226).
 */
class TwoFactorService
{
    /**
     * Base32 alphabet used for encoding/decoding TOTP secrets.
     *
     * @var string
     */
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a random 20-byte secret, base32-encoded.
     *
     * @return string Base32-encoded secret
     */
    public function generateSecret(): string
    {
        return $this->base32Encode(random_bytes(20));
    }

    /**
     * Return an otpauth:// URI suitable for QR code generation.
     *
     * @param string $email The user's email address.
     * @param string $secret The base32-encoded TOTP secret.
     * @return string The otpauth:// URI.
     */
    public function getQrCodeUrl(string $email, string $secret): string
    {
        $issuer = urlencode('ISP Status Page');
        $encodedEmail = urlencode($email);

        return "otpauth://totp/{$issuer}:{$encodedEmail}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";
    }

    /**
     * Verify a TOTP code against the secret with a +/-1 time window.
     *
     * @param string $secret The base32-encoded TOTP secret.
     * @param string $code The 6-digit code to verify.
     * @return bool True if the code is valid.
     */
    public function verifyCode(string $secret, string $code): bool
    {
        if (strlen($code) !== 6 || !ctype_digit($code)) {
            return false;
        }

        $timeSlice = (int)floor(time() / 30);

        for ($i = -1; $i <= 1; $i++) {
            if ($this->generateCode($secret, $timeSlice + $i) === $code) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a TOTP code for a given time slice.
     *
     * @param string $secret The base32-encoded TOTP secret.
     * @param int $timeSlice The time slice (counter value).
     * @return string The 6-digit TOTP code.
     */
    private function generateCode(string $secret, int $timeSlice): string
    {
        $secretKey = $this->base32Decode($secret);
        $time = pack('N*', 0, $timeSlice);
        $hash = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord($hash[19]) & 0x0F;
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % 1000000;

        return str_pad((string)$code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a set of single-use recovery codes.
     *
     * @param int $count Number of codes to generate.
     * @return array<string> Array of plaintext recovery codes.
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(4)));
        }

        return $codes;
    }

    /**
     * Hash recovery codes for safe storage.
     *
     * @param array<string> $codes Plaintext recovery codes.
     * @return array<string> Hashed recovery codes.
     */
    public function hashRecoveryCodes(array $codes): array
    {
        return array_map(function (string $code) {
            return password_hash($code, PASSWORD_BCRYPT);
        }, $codes);
    }

    /**
     * Verify a recovery code against a list of hashed codes.
     * Returns the index of the matching code, or -1 if not found.
     *
     * @param string $code The plaintext recovery code to verify.
     * @param array<string> $hashedCodes The stored hashed codes.
     * @return int Index of the matching code, or -1.
     */
    public function verifyRecoveryCode(string $code, array $hashedCodes): int
    {
        $code = strtoupper(trim($code));
        foreach ($hashedCodes as $index => $hashedCode) {
            if (password_verify($code, $hashedCode)) {
                return $index;
            }
        }

        return -1;
    }

    /**
     * Base32 encode binary data.
     *
     * @param string $data Binary data to encode.
     * @return string Base32-encoded string.
     */
    private function base32Encode(string $data): string
    {
        $binary = '';
        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $result = '';
        $chunks = str_split($binary, 5);
        foreach ($chunks as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $result .= self::BASE32_ALPHABET[bindec($chunk)];
        }

        return $result;
    }

    /**
     * Base32 decode a string to binary data.
     *
     * @param string $data Base32-encoded string.
     * @return string Decoded binary data.
     */
    private function base32Decode(string $data): string
    {
        $data = strtoupper($data);
        $binary = '';
        foreach (str_split($data) as $char) {
            $pos = strpos(self::BASE32_ALPHABET, $char);
            if ($pos === false) {
                continue;
            }
            $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $result = '';
        $chunks = str_split($binary, 8);
        foreach ($chunks as $chunk) {
            if (strlen($chunk) === 8) {
                $result .= chr(bindec($chunk));
            }
        }

        return $result;
    }
}
