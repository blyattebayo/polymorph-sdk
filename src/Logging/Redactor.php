<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Logging;

/**
 * Маскирование секретов в данных расширения (логи, audit-трейлы, исходящие
 * payload'ы). Реализуется ХОСТОМ поверх единой политики ядра — расширение НЕ
 * катает собственный редактор (иначе политика маскирования разъезжается).
 *
 *     // в провайдере: new AuditService($this->records('audit'), $this->redactor());
 *     $clean = $this->redactor->redact($responsePayload);
 */
interface Redactor
{
    public const REDACTED = '[redacted]';

    /**
     * Рекурсивно маскирует чувствительные значения в массиве.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function redact(array $payload): array;

    /** Маскирует секреты (токены/ключи) внутри строки. */
    public function redactString(string $value): string;
}
