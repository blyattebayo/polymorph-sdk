<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Errors;

/**
 * Каталог ошибок, доступных расширению. Зеркалит подмножество ErrorCode ядра;
 * соответствие охраняется contract-guard тестом ядра (см. ADR 0002).
 *
 * В отличие от V1 (`PluginErrors` с тремя методами и инъекцией хоста для броска),
 * V2 даёт полный паритет с HTTP-семантикой ядра и бросается без хоста —
 * см. {@see ExtensionError}.
 */
enum ErrorCode: string
{
    case BAD_REQUEST = 'BAD_REQUEST';
    case VALIDATION_ERROR = 'VALIDATION_ERROR';
    case UNAUTHORIZED = 'UNAUTHORIZED';
    case FORBIDDEN = 'FORBIDDEN';
    case NOT_FOUND = 'NOT_FOUND';
    case CONFLICT = 'CONFLICT';
    case TOO_MANY_REQUESTS = 'TOO_MANY_REQUESTS';
    case SERVICE_UNAVAILABLE = 'SERVICE_UNAVAILABLE';
    case INTERNAL_ERROR = 'INTERNAL_ERROR';

    public function httpStatus(): int
    {
        return match ($this) {
            self::BAD_REQUEST => 400,
            self::VALIDATION_ERROR => 422,
            self::UNAUTHORIZED => 401,
            self::FORBIDDEN => 403,
            self::NOT_FOUND => 404,
            self::CONFLICT => 409,
            self::TOO_MANY_REQUESTS => 429,
            self::SERVICE_UNAVAILABLE => 503,
            self::INTERNAL_ERROR => 500,
        };
    }
}
