<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Identity;

/**
 * Снимок пользователя ядра, доступный расширению (DTO, не Eloquent-модель).
 * Переименование V1 `PluginUser` → нейтральное `Actor` (см. ADR 0002).
 */
final class Actor
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly ?string $name = null,
    ) {}
}
