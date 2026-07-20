<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Data;

/**
 * Иммутабельная спецификация схемы сущности расширения. Результат
 * {@see SchemaBuilder::build()}. Передаётся в `DefinitionRegistry` (хост) как
 * типизированный объект — не нетипизированный вложенный массив V1.
 */
final class SchemaSpec
{
    /**
     * @param  list<FieldDefinition>  $fields
     */
    public function __construct(
        public readonly string $name,
        public readonly array $fields,
    ) {}

    /**
     * @return array{name: string, fields: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'fields' => array_map(static fn (FieldDefinition $f): array => $f->toArray(), $this->fields),
        ];
    }
}
