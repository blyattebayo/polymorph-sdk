<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Data;

/**
 * Идемпотентное создание схем и record-definitions платформы данных из
 * типизированной {@see SchemaSpec}. Замена V1 `DefinitionSeeder` (нетипизированные
 * массивы) — теперь спецификация строится через {@see SchemaBuilder}.
 *
 * Инстанс СКОУПНУТ на расширение (id берётся из ExtensionContext): принимает
 * ГОЛОЕ имя сущности ('servers'), внутренний storage key строит хост.
 *
 *     $registry->ensure('servers', SchemaBuilder::make('MCP Servers')
 *         ->string('code', fn ($f) => $f->required()->indexed()->unique())
 *         ->build());
 */
interface DefinitionRegistry
{
    public function ensure(string $entity, SchemaSpec $spec): DefinitionRef;
}
