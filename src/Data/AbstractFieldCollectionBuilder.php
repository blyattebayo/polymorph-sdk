<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Data;

use Closure;

/** Shared typed DSL for schema roots and nested object children. */
abstract class AbstractFieldCollectionBuilder
{
    /** @var list<FieldBuilder> */
    private array $fields = [];

    /** @param Closure(FieldBuilder):void|null $configure */
    public function field(string $name, FieldType $type, ?Closure $configure = null): static
    {
        $builder = new FieldBuilder($name, $type);
        if ($configure !== null) {
            $configure($builder);
        }
        $this->fields[] = $builder;

        return $this;
    }

    /** @param Closure(FieldBuilder):void|null $configure */
    public function string(string $name, ?Closure $configure = null): static
    {
        return $this->field($name, FieldType::STRING, $configure);
    }

    /** @param Closure(FieldBuilder):void|null $configure */
    public function text(string $name, ?Closure $configure = null): static
    {
        return $this->field($name, FieldType::TEXT, $configure);
    }

    /** @param Closure(FieldBuilder):void|null $configure */
    public function int(string $name, ?Closure $configure = null): static
    {
        return $this->field($name, FieldType::INT, $configure);
    }

    /** @param Closure(FieldBuilder):void|null $configure */
    public function float(string $name, ?Closure $configure = null): static
    {
        return $this->field($name, FieldType::FLOAT, $configure);
    }

    /** @param Closure(FieldBuilder):void|null $configure */
    public function bool(string $name, ?Closure $configure = null): static
    {
        return $this->field($name, FieldType::BOOL, $configure);
    }

    /** @param Closure(FieldBuilder):void|null $configure */
    public function datetime(string $name, ?Closure $configure = null): static
    {
        return $this->field($name, FieldType::DATETIME, $configure);
    }

    /** Explicit arbitrary JSON value; it cannot have schema children. @param Closure(FieldBuilder):void|null $configure */
    public function rawJson(string $name, ?Closure $configure = null): static
    {
        return $this->field($name, FieldType::RAW_JSON, $configure);
    }

    /** @param Closure(FieldBuilder):void|null $configure */
    public function ref(string $name, ?Closure $configure = null): static
    {
        return $this->field($name, FieldType::REF, $configure);
    }

    /** @param Closure(FieldBuilder):void|null $configure */
    public function media(string $name, ?Closure $configure = null): static
    {
        return $this->field($name, FieldType::MEDIA, $configure);
    }

    /** @param Closure(ObjectBuilder):void $children @param Closure(FieldBuilder):void|null $configure */
    public function object(string $name, Closure $children, ?Closure $configure = null): static
    {
        return $this->objectField($name, Cardinality::ONE, $children, $configure);
    }

    /** @param Closure(ObjectBuilder):void $children @param Closure(FieldBuilder):void|null $configure */
    public function objects(string $name, Closure $children, ?Closure $configure = null): static
    {
        return $this->objectField($name, Cardinality::MANY, $children, $configure);
    }

    /** @param list<string> $values @param Closure(FieldBuilder):void|null $configure */
    public function enum(string $name, array $values, ?Closure $configure = null): static
    {
        return $this->field($name, FieldType::STRING, function (FieldBuilder $field) use ($values, $configure): void {
            $field->rule('in', array_values($values));
            if ($configure !== null) {
                $configure($field);
            }
        });
    }

    /** @return list<FieldDefinition> */
    final protected function definitions(): array
    {
        return array_map(
            static fn (FieldBuilder $field, int $index): FieldDefinition => $field->toDefinition($index),
            $this->fields,
            array_keys($this->fields),
        );
    }

    final protected function hasFields(): bool
    {
        return $this->fields !== [];
    }

    /** @param Closure(ObjectBuilder):void $children @param Closure(FieldBuilder):void|null $configure */
    private function objectField(string $name, Cardinality $cardinality, Closure $children, ?Closure $configure): static
    {
        $object = new ObjectBuilder;
        $children($object);
        if (! $object->hasFields()) {
            throw new \InvalidArgumentException("JSON container '{$name}' must declare at least one child.");
        }
        $builder = new FieldBuilder($name, FieldType::JSON, $cardinality, $object->definitions());
        if ($configure !== null) {
            $configure($builder);
        }
        $this->fields[] = $builder;

        return $this;
    }
}
