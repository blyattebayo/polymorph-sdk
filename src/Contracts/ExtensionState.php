<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Contracts;

/**
 * Состояние расширения в реестре ядра. Используется базовым ExtensionProvider,
 * чтобы активировать слушатели событий и расписание только у включённых расширений.
 */
interface ExtensionState
{
    public function isEnabled(string $extensionId): bool;
}
