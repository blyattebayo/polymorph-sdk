<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Routing;

use Polymorph\Sdk\Access\Capability;

/**
 * Имена middleware ядра, доступные расширениям. Единственное место строковых
 * контрактов маршрутизации; соответствие охраняется contract-guard тестом ядра.
 */
final class Middleware
{
    public const API = 'api';
    public const WEB = 'web';
    public const JWT_AUTH = 'auth:api';

    /** Алиас middleware проверки capability. */
    public const CAPABILITY_ALIAS = 'capability.require';

    /** Нейтральный символ CSRF-middleware API (хост резолвит его в свой FQCN при загрузке маршрутов). */
    public const CSRF = 'csrf';

    private function __construct()
    {
    }

    public static function requireCapability(Capability $capability): string
    {
        return self::CAPABILITY_ALIAS . ':' . $capability->resource . ',' . $capability->action;
    }

    public static function without(string $middleware): string
    {
        return 'without:' . $middleware;
    }

    public static function withoutCsrf(): string
    {
        return self::without(self::CSRF);
    }
}
