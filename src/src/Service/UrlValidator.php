<?php
declare(strict_types=1);

namespace App\Service;

class UrlValidator
{
    private static array $blockedCidrs = [
        '127.0.0.0/8', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16',
        '169.254.0.0/16', '0.0.0.0/8', '100.64.0.0/10', '198.18.0.0/15',
        'fc00::/7', '::1/128', 'fe80::/10',
    ];

    public static function isUrlSafe(string $url): bool
    {
        $parsed = parse_url($url);
        if (!$parsed || empty($parsed['host'])) {
            return false;
        }

        $host = $parsed['host'];

        // Resolve DNS to check actual IP
        $ips = gethostbynamel($host);
        if ($ips === false) {
            return true; // Can't resolve — let the checker handle the error
        }

        foreach ($ips as $ip) {
            if (self::isPrivateIp($ip)) {
                return false;
            }
        }

        return true;
    }

    public static function isPrivateIp(string $ip): bool
    {
        // Check against private/reserved ranges
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
