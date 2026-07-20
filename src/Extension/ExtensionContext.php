<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Extension;

/**
 * Явный скоуп расширения. Передаётся в DI и в lifecycle-хуки — это замена
 * ambient-скоупу V1 (контекстные биндинги + scopedConsumers() + guard), который
 * был неявным и допускал расхождения. Здесь скоуп всегда передаётся явно.
 *
 * Единственный источник правды для ресурсного namespace расширения: 'ext.{id}.'.
 * И ACL-гранты, и data-store строят ключи отсюда — без ручной склейки строк.
 */
final class ExtensionContext
{
    public function __construct(public readonly ExtensionId $id)
    {
    }

    public static function for(string $id): self
    {
        return new self(ExtensionId::fromString($id));
    }

    /** Префикс ACL/ресурсов расширения: 'ext.{id}.' (с завершающей точкой). */
    public function resourcePrefix(): string
    {
        return 'ext.' . $this->id->value . '.';
    }

    /**
     * Полный ресурс расширения из сегментов: resource('servers','42') ->
     * 'ext.{id}.servers.42'. Без сегментов вернёт префикс без хвостовой точки.
     */
    public function resource(string ...$segments): string
    {
        $base = 'ext.' . $this->id->value;

        if ($segments === []) {
            return $base;
        }

        return $base . '.' . implode('.', array_map(static fn (string $s): string => trim($s), $segments));
    }
}
