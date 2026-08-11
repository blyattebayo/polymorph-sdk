<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Version;

/**
 * Validates an extension SDK requirement against the one contract implemented by the host.
 */
final class Compatibility
{
    private function __construct() {}

    public static function hostSupports(SdkVersion $host, SdkVersion $required): bool
    {
        return $required->major === $host->major
            && $required->minor <= $host->minor;
    }

    public static function check(string $hostVersion, string $requiredVersion): bool
    {
        return self::hostSupports(
            SdkVersion::fromString($hostVersion),
            SdkVersion::fromString($requiredVersion),
        );
    }

    /** Accepts '^5', '^5.1', '~5.1', or an exact 5.1.0-style minimum. */
    public static function satisfiesRange(string $hostVersion, string $range): bool
    {
        return self::hostSupports(SdkVersion::fromString($hostVersion), self::parseRange($range));
    }

    private static function parseRange(string $range): SdkVersion
    {
        $normalized = ltrim(trim($range), '^~');

        if (preg_match('/^\d+(\.\d+){0,2}$/', $normalized) !== 1) {
            throw new \InvalidArgumentException("Invalid SDK range '{$range}'.");
        }

        $parts = explode('.', $normalized);

        return new SdkVersion(
            (int) ($parts[0] ?? 0),
            (int) ($parts[1] ?? 0),
            (int) ($parts[2] ?? 0),
        );
    }
}
