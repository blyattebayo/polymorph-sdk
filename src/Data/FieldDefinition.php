<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Data;

/**
 * Иммутабельное определение одного поля. Результат {@see FieldBuilder}.
 *
 * Размещение признаков типобезопасно и не оставляет ловушек V1 (где 'unique'
 * случайно уходил в Laravel-валидатор): `indexed`/`unique` — отдельные поля VO,
 * `rules` — только содержательные правила валидации.
 */
final class FieldDefinition
{
    /**
     * @param array<string, mixed> $rules валидационные правила (required/nullable/in/regex/min/max/…)
     */
    public function __construct(
        public readonly string $name,
        public readonly FieldType $type,
        public readonly Cardinality $cardinality,
        public readonly bool $indexed,
        public readonly bool $unique,
        public readonly int $sortOrder,
        public readonly array $rules,
    ) {
    }

    public function isRequired(): bool
    {
        return ($this->rules['required'] ?? false) === true;
    }

    /**
     * Нейтральное представление для хост-адаптера/сериализации.
     *
     * @return array{name: string, type: string, cardinality: string, indexed: bool, unique: bool, sort_order: int, rules: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type->value,
            'cardinality' => $this->cardinality->value,
            'indexed' => $this->indexed,
            'unique' => $this->unique,
            'sort_order' => $this->sortOrder,
            'rules' => $this->rules,
        ];
    }
}
