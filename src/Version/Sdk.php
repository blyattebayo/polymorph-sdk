<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Version;

/**
 * Current backend extension SDK contract. The host checks each manifest's
 * `sdk` range against this version through {@see Compatibility::satisfiesRange()}.
 */
final class Sdk
{
    public const VERSION = '4.0.0';

    private function __construct() {}

    public static function version(): SdkVersion
    {
        return SdkVersion::fromString(self::VERSION);
    }
}
