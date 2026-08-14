<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Data;

/** Immutable field node in the public nested schema contract. */
final readonly class FieldDefinition
{
    /**
     * @param  array<string,mixed>  $rules
     * @param  list<FieldDefinition>  $children
     */
    public function __construct(
        public string $name,
        public FieldType $type,
        public Cardinality $cardinality,
        public bool $indexed,
        public bool $unique,
        public int $sortOrder,
        public array $rules,
        public array $children = [],
    ) {
        FieldName::from($name);
        if ($type !== FieldType::JSON && $children !== []) {
            throw new \InvalidArgumentException('Only JSON fields may declare children.');
        }
    }

    public function isRequired(): bool
    {
        return ($this->rules['required'] ?? false) === true;
    }

    /** @return array<string,mixed> */
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
            'children' => array_map(static fn (self $child): array => $child->toArray(), $this->children),
        ];
    }
}
