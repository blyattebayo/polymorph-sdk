<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Data;

/** Builds the canonical nested schema contract consumed by DefinitionRegistry. */
final class SchemaBuilder extends AbstractFieldCollectionBuilder
{
    private function __construct(private readonly string $name) {}

    public static function make(string $name): self
    {
        return new self($name);
    }

    public function build(): SchemaSpec
    {
        if (! $this->hasFields()) {
            throw new \InvalidArgumentException("Schema '{$this->name}' must declare at least one field.");
        }

        return new SchemaSpec($this->name, $this->definitions());
    }
}
