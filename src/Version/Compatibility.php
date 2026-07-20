<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Version;

/**
 * Переговоры совместимости версии SDK расширения с версией хоста.
 *
 * Модель (см. ADR 0002, Stripe-подход): хост реализует ТОЛЬКО последний контракт,
 * но поддерживает окно из {@see SUPPORTED_MAJOR_WINDOW} предыдущих мажоров через
 * compatibility-слой. Расширение объявляет требуемую версию (из манифеста, напр.
 * "^2" → major=2); хост проверяет совместимость на enable (BE) и mount (FE).
 *
 * Правила:
 *  - требуемый major не может быть НОВЕЕ хоста (хост не знает будущий контракт);
 *  - требуемый major не должен быть старше, чем (host.major - окно);
 *  - в пределах текущего мажора хоста: аддитивность — хост может быть новее по
 *    minor, но не может быть СТАРШЕ требуемого minor.
 */
final class Compatibility
{
    /** Сколько ПРЕДЫДУЩИХ мажоров хост поддерживает помимо текущего. */
    public const SUPPORTED_MAJOR_WINDOW = 1;

    private function __construct() {}

    public static function hostSupports(
        SdkVersion $host,
        SdkVersion $required,
        int $window = self::SUPPORTED_MAJOR_WINDOW,
    ): bool {
        if ($required->major > $host->major) {
            return false;
        }

        if ($required->major < $host->major - $window) {
            return false;
        }

        if ($required->major === $host->major && $required->minor > $host->minor) {
            return false;
        }

        return true;
    }

    /**
     * Удобный фасад: разобрать строки версий и проверить совместимость.
     */
    public static function check(string $hostVersion, string $requiredVersion): bool
    {
        return self::hostSupports(
            SdkVersion::fromString($hostVersion),
            SdkVersion::fromString($requiredVersion),
        );
    }

    /**
     * Проверка против диапазона из манифеста расширения: '^2', '^2.1', '2.3.0'.
     * Caret/tilde трактуются как «не ниже указанного в пределах того же мажора»
     * (что и кодирует {@see hostSupports}). Точная версия — как минимальная.
     */
    public static function satisfiesRange(string $hostVersion, string $range, int $window = self::SUPPORTED_MAJOR_WINDOW): bool
    {
        return self::hostSupports(SdkVersion::fromString($hostVersion), self::parseRange($range), $window);
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
