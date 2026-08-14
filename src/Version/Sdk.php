<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Version;

/**
 * Current backend extension SDK contract. Manifests must match it exactly.
 */
final class Sdk
{
    public const VERSION = '6.1.0';

    private function __construct() {}

    public static function version(): SdkVersion
    {
        return SdkVersion::fromString(self::VERSION);
    }
}
