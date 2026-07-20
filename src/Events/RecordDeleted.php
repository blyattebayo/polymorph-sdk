<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Events;

/**
 * Declared record-lifecycle contract of the Extension SDK: dispatched by the platform when an
 * extension-owned record is deleted. Extensions subscribe to THIS event (via ExtensionProvider
 * `listeners()`) instead of reaching into the platform's internal event/model classes — this is
 * the sanctioned plugin↔core boundary (ADR 0005 Фаза 4).
 *
 * `extensionId` and `entity` are the parsed components of the record's schema storage key
 * (`ext__{extensionId}__{entity}`), so a listener matches purely on its own constants without
 * knowing the platform's key format or touching platform models.
 */
final class RecordDeleted
{
    /**
     * @param  string  $extensionId  Owning extension id, e.g. 'context-router'.
     * @param  string  $entity  Logical entity name, e.g. 'servers'.
     * @param  string  $schemaCode  Full storage-key schema code, e.g. 'ext__context-router__servers'.
     * @param  int  $recordId  Id of the deleted record.
     * @param  array<string, mixed>  $data  Snapshot of the deleted record's data.
     */
    public function __construct(
        public readonly string $extensionId,
        public readonly string $entity,
        public readonly string $schemaCode,
        public readonly int $recordId,
        public readonly array $data,
    ) {}
}
