<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Data\FieldTypes;

/**
 * Controlled, storage-neutral plugin field-type contract.
 *
 * Implementations return plain arrays/scalars. They cannot access platform models or
 * tables; the host validates and persists their declared projection changes.
 * Field arguments include structural context (`parent_id`, `multi_valued`, and
 * `position`) in addition to identity, type, cardinality, constraints, and metadata.
 */
interface FieldTypeExtension
{
    public function type(): string;

    /** @param array<string,mixed> $field */
    public function validateSchema(array $field): void;

    /** @param array<string,mixed> $field */
    public function normalize(mixed $value, array $field, string $occurrence): mixed;

    /** @param array<string,mixed> $field */
    public function validateValue(mixed $value, array $field, string $occurrence): void;

    /** @param array<string,mixed> $field @return array{record_ids?:list<int>,media_ids?:list<string>} */
    public function collectBatchDependencies(mixed $value, array $field, string $occurrence): array;

    /** @param array<string,mixed> $field @param array<string,mixed> $resolved */
    public function validateResolvedDependencies(mixed $value, array $field, string $occurrence, array $resolved): void;

    /**
     * @param  array<string,mixed>  $field
     * @return array{ref_edges?:list<array<string,mixed>>,media_edges?:list<array<string,mixed>>,unique_values?:list<array<string,mixed>>,search_values?:list<string>,display_value?:string|null}
     */
    public function buildProjectionChanges(mixed $value, array $field, string $occurrence): array;

    /** @return list<string> */
    public function supportedQueryOperators(): array;

    /** @param array<string,mixed> $predicate @return array{strategy:string,operator:string,cast?:string|null,bindings?:list<mixed>} */
    public function compileQuery(array $predicate): array;
}
