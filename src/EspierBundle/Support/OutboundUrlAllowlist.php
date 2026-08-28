<?php

declare(strict_types=1);

namespace EspierBundle\Support;

class OutboundUrlAllowlist
{
    public static function isAllowed(string $url): bool
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if ($scheme !== 'http' && $scheme !== 'https') {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '') {
            return false;
        }

        if (self::isBlockedHost($host)) {
            return false;
        }

        return true;
    }

    private static function isBlockedHost(string $host): bool
    {
        if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return self::isPrivateIpv4($host);
        }

        return false;
    }

    private static function isPrivateIpv4(string $ip): bool
    {
        $long = ip2long($ip);
        if ($long === false) {
            return true;
        }

        $privateRanges = [
            [0x7F000000, 0x7FFFFFFF], // 127.0.0.0/8 loopback
            [0x0A000000, 0x0AFFFFFF], // 10.0.0.0/8
            [0xAC100000, 0xAC1FFFFF], // 172.16.0.0/12
            [0xC0A80000, 0xC0A8FFFF], // 192.168.0.0/16
            [0xA9FE0000, 0xA9FEFFFF], // 169.254.0.0/16 link-local
        ];

        foreach ($privateRanges as [$start, $end]) {
            if ($long >= $start && $long <= $end) {
                return true;
            }
        }

        return false;
    }

    public static function assertAllowed(string $url): void
    {
        if (!self::isAllowed($url)) {
            throw new \InvalidArgumentException('Outbound URL is not allowed: ' . $url);
        }
    }
}
