<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Data;

/**
 * Fluent-настройка одного поля внутри {@see SchemaBuilder}.
 *
 *     $b->string('code', fn (FieldBuilder $f) => $f->required()->indexed()->unique());
 */
final class FieldBuilder
{
    private bool $indexed = false;

    private bool $unique = false;

    private ?int $sortOrder = null;

    /** @var array<string, mixed> */
    private array $rules = [];

    private readonly string $name;

    public function __construct(
        string $name,
        private readonly FieldType $type,
        private Cardinality $cardinality = Cardinality::ONE,
        /** @var list<FieldDefinition> */
        private readonly array $children = [],
    ) {
        $this->name = FieldName::from($name)->value;
    }

    public function required(): self
    {
        $this->rules['required'] = true;
        unset($this->rules['nullable']);

        return $this;
    }

    public function nullable(): self
    {
        $this->rules['nullable'] = true;
        unset($this->rules['required']);

        return $this;
    }

    public function indexed(): self
    {
        $this->indexed = true;

        return $this;
    }

    public function unique(): self
    {
        $this->unique = true;

        return $this;
    }

    public function many(): self
    {
        $this->cardinality = Cardinality::MANY;

        return $this;
    }

    public function sort(int $order): self
    {
        $this->sortOrder = $order;

        return $this;
    }

    /**
     * Произвольное правило валидации (escape-hatch): in/regex/min/max/…
     */
    public function rule(string $name, mixed $value = true): self
    {
        $this->rules[$name] = $value;

        return $this;
    }

    public function toDefinition(int $defaultSortOrder): FieldDefinition
    {
        return new FieldDefinition(
            name: $this->name,
            type: $this->type,
            cardinality: $this->cardinality,
            indexed: $this->indexed,
            unique: $this->unique,
            sortOrder: $this->sortOrder ?? $defaultSortOrder,
            rules: $this->rules,
            children: $this->children,
        );
    }
}
