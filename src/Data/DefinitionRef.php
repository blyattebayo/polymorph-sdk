<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Data;

/**
 * Ссылка на созданное/существующее определение сущности расширения (результат
 * {@see DefinitionRegistry::ensure()}). Нейтральный VO — без зависимости от
 * моделей ядра.
 */
final class DefinitionRef
{
    public function __construct(
        public readonly int $id,
        public readonly int $schemaId,
        public readonly string $entity,
    ) {}
}
