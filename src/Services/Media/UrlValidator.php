<?php

declare(strict_types=1);

namespace Adeliom\SyliusHappyCMSPlugin\Services\Media;

/**
 * SECURITY: URL Validator to prevent SSRF (Server-Side Request Forgery) attacks
 *
 * This class validates URLs before they are used in file_get_contents() or similar operations.
 * It prevents attacks like:
 * - Reading local files (file://, php://, data://, etc.)
 * - Accessing internal network (169.254.169.254, localhost, 127.0.0.1, etc.)
 * - Using dangerous protocols (ftp://, gopher://, etc.)
 */
class UrlValidator
{
    /**
     * Allowed protocols for external URL fetching
     */
    private const ALLOWED_PROTOCOLS = ['http', 'https'];

    /**
     * Blocked IP ranges (CIDR notation) to prevent SSRF to internal networks
     */
    private const BLOCKED_IP_RANGES = [
        '0.0.0.0/8',          // Current network
        '10.0.0.0/8',         // Private network
        '127.0.0.0/8',        // Loopback
        '169.254.0.0/16',     // Link-local (AWS metadata, etc.)
        '172.16.0.0/12',      // Private network
        '192.168.0.0/16',     // Private network
        '224.0.0.0/4',        // Multicast
        '240.0.0.0/4',        // Reserved
        '::1/128',            // IPv6 loopback
        'fc00::/7',           // IPv6 private
        'fe80::/10',          // IPv6 link-local
    ];

    /**
     * Blocked hostnames to prevent DNS rebinding attacks
     */
    private const BLOCKED_HOSTNAMES = [
        'localhost',
        'localhost.localdomain',
        '0.0.0.0',
        '127.0.0.1',
        '::1',
    ];

    /**
     * Validate URL for safe external fetching
     *
     * @param string $url The URL to validate
     *
     * @throws \InvalidArgumentException If URL is invalid or dangerous
     *
     * @return bool True if URL is safe
     */
    public function validate(string $url): bool
    {
        // Step 1: Basic URL validation
        if (empty($url)) {
            throw new \InvalidArgumentException('URL cannot be empty');
        }

        // Step 2: Parse URL
        $parsed = parse_url($url);
        if (false === $parsed || !isset($parsed['scheme']) || !isset($parsed['host'])) {
            throw new \InvalidArgumentException('Invalid URL format');
        }

        // Step 3: Validate protocol
        $scheme = strtolower($parsed['scheme']);
        if (!\in_array($scheme, self::ALLOWED_PROTOCOLS, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Protocol "%s" is not allowed. Only %s are permitted.',
                    $scheme,
                    implode(', ', self::ALLOWED_PROTOCOLS),
                ),
            );
        }

        // Step 4: Validate hostname
        $host = strtolower($parsed['host']);

        // Check blocked hostnames
        if (\in_array($host, self::BLOCKED_HOSTNAMES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Hostname "%s" is not allowed for security reasons', $host),
            );
        }

        // Step 5: Resolve hostname to IP and validate
        $ip = @gethostbyname($host);
        if (!$ip || $ip === $host) {
            // If gethostbyname fails, it returns the hostname itself
            // Try to validate if it's already an IP
            if (filter_var($host, \FILTER_VALIDATE_IP)) {
                $ip = $host;
            } else {
                throw new \InvalidArgumentException(
                    sprintf('Unable to resolve hostname "%s"', $host),
                );
            }
        }

        // Step 6: Check if IP is in blocked ranges
        if ($this->isIpBlocked($ip)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'IP address "%s" (hostname: %s) is in a blocked range for security reasons',
                    $ip,
                    $host,
                ),
            );
        }

        return true;
    }

    /**
     * Check if an IP address is in any of the blocked ranges
     *
     * @param string $ip The IP address to check
     *
     * @return bool True if IP is blocked
     */
    private function isIpBlocked(string $ip): bool
    {
        // Determine if IPv4 or IPv6
        if (filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4)) {
            return $this->isIpInRanges($ip, self::BLOCKED_IP_RANGES, \AF_INET);
        }

        if (filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6)) {
            return $this->isIpInRanges($ip, self::BLOCKED_IP_RANGES, \AF_INET6);
        }

        // If not a valid IP, block it
        return true;
    }

    /**
     * Check if an IP is in any of the given CIDR ranges
     *
     * @param string $ip The IP address to check
     * @param array<string> $ranges Array of CIDR ranges
     * @param int $family AF_INET or AF_INET6
     *
     * @return bool True if IP is in any range
     */
    private function isIpInRanges(string $ip, array $ranges, int $family): bool
    {
        $ipBin = @inet_pton($ip);
        if (false === $ipBin) {
            return true; // Block invalid IPs
        }

        foreach ($ranges as $range) {
            // Skip IPv6 ranges when checking IPv4 and vice versa
            if ($family === \AF_INET && str_contains($range, ':')) {
                continue;
            }
            if ($family === \AF_INET6 && !str_contains($range, ':')) {
                continue;
            }

            if ($this->isIpInCidr($ipBin, $range, $family)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP (binary) is in a CIDR range
     *
     * @param string $ipBin Binary representation of IP
     * @param string $cidr CIDR notation (e.g., "192.168.0.0/16")
     * @param int $family AF_INET or AF_INET6
     *
     * @return bool True if IP is in CIDR range
     */
    private function isIpInCidr(string $ipBin, string $cidr, int $family): bool
    {
        [$subnet, $mask] = explode('/', $cidr);
        $subnetBin = @inet_pton($subnet);

        if (false === $subnetBin) {
            return false;
        }

        $mask = (int) $mask;
        $maxBits = $family === \AF_INET ? 32 : 128;

        if ($mask < 0 || $mask > $maxBits) {
            return false;
        }

        // Create binary mask
        $binaryMask = str_repeat('1', $mask) . str_repeat('0', $maxBits - $mask);
        $maskBin = '';

        for ($i = 0; $i < $maxBits; $i += 8) {
            $maskBin .= \chr((int) bindec(substr($binaryMask, $i, 8)));
        }

        // Compare masked IP with masked subnet
        return ($ipBin & $maskBin) === ($subnetBin & $maskBin);
    }
}
