<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Matcher;

final readonly class CidrMatcher
{
    /**
     * @param string[] $cidrs List of CIDR strings, e.g. ['192.168.1.0/24', '2001:db8::/32', '10.0.0.5']
     */
    public function matches(string $ip, array $cidrs): bool
    {
        $ipBin = inet_pton($ip);
        if (false === $ipBin) {
            return false; // not a valid IP at all
        }

        foreach ($cidrs as $cidr) {
            if ($this->matchesSingle($ipBin, $cidr)) {
                return true;
            }
        }

        return false;
    }

    private function matchesSingle(string $ipBin, string $cidr): bool
    {
        if (str_contains($cidr, '/')) {
            [$subnet, $maskLen] = explode('/', $cidr, 2);
            $maskLen = (int) $maskLen;
        } else {
            $subnet = $cidr;
            $maskLen = null;
        }

        $subnetBin = inet_pton($subnet);
        if (false === $subnetBin) {
            return false; // malformed entry in the list
        }

        // IPv4 vs IPv6 length must match (4 vs 16 bytes)
        if (strlen($subnetBin) !== strlen($ipBin)) {
            return false;
        }

        $maskLen ??= strlen($subnetBin) * 8; // no prefix => exact match required

        return $this->withinMask($ipBin, $subnetBin, $maskLen);
    }

    private function withinMask(string $ipBin, string $subnetBin, int $maskLen): bool
    {
        $bytesLen = strlen($ipBin); // 4 or 16
        if ($maskLen < 0 || $maskLen > $bytesLen * 8) {
            return false;
        }

        $fullBytes = intdiv($maskLen, 8);
        $remainderBits = $maskLen % 8;

        // Compare the full bytes first
        if ($fullBytes > 0 && !str_starts_with($ipBin, substr($subnetBin, 0, $fullBytes))) {
            return false;
        }

        // Compare the remaining bits in the next byte, if any
        if ($remainderBits > 0) {
            $mask = 0xFF << (8 - $remainderBits) & 0xFF;
            $ipByte = ord($ipBin[$fullBytes]);
            $subnetByte = ord($subnetBin[$fullBytes]);

            if (($ipByte & $mask) !== ($subnetByte & $mask)) {
                return false;
            }
        }

        return true;
    }
}
