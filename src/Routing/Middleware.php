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

    public const SESSION_AUTH = 'auth:session';

    public const OAUTH_AUTH = 'oauth.resource';

    /** Алиас middleware проверки capability. */
    public const CAPABILITY_ALIAS = 'capability.require';

    /** Нейтральный символ CSRF-middleware API (хост резолвит его в свой FQCN при загрузке маршрутов). */
    public const CSRF = 'csrf';

    private function __construct() {}

    public static function requireCapability(Capability $capability): string
    {
        return self::CAPABILITY_ALIAS.':'.$capability->resource.','.$capability->action;
    }
}
