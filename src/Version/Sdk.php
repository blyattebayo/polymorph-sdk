<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Version;

/**
 * Текущая версия Extension SDK (BE). Единый домен версии с FE
 * (`@polymorph/sdk` SDK_VERSION). Хост сверяет требуемую версию расширения
 * (манифест `sdk`) против неё через {@see Compatibility::satisfiesRange()}.
 */
final class Sdk
{
    public const VERSION = '2.1.0';

    private function __construct()
    {
    }

    public static function version(): SdkVersion
    {
        return SdkVersion::fromString(self::VERSION);
    }
}
