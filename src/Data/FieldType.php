<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Data;

/**
 * Типы полей платформы данных. Зеркалят типы ядра; соответствие охраняется
 * contract-guard тестом ядра (см. ADR 0002). Замена «магическим строкам» V1
 * (`'type' => 'string'`).
 */
enum FieldType: string
{
    case STRING = 'string';
    case TEXT = 'text';
    case INT = 'int';
    case FLOAT = 'float';
    case BOOL = 'bool';
    case DATETIME = 'datetime';
    case JSON = 'json';
    case REF = 'ref';
    case MEDIA = 'media';

    public function isNumeric(): bool
    {
        return $this === self::INT || $this === self::FLOAT;
    }
}
