<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Extension;

use Stringable;

/**
 * Идентификатор расширения — типизированная замена «голой» строке pluginId V1.
 *
 * Делает лёгкую структурную проверку формы (slug). АВТОРИТЕТНАЯ валидация id —
 * на стороне хоста при discovery (против slug-правила ядра): SDK не дублирует
 * правила ядра статически, иначе они разойдутся (см. ADR 0002).
 */
final class ExtensionId implements Stringable
{
    /** Структурный slug-guard (зеркало документированного slug-правила платформы). */
    private const PATTERN = '/^[a-z][a-z0-9_-]*$/';

    private function __construct(public readonly string $value)
    {
    }

    public static function fromString(string $value): self
    {
        $trimmed = trim($value);

        if ($trimmed === '' || preg_match(self::PATTERN, $trimmed) !== 1) {
            throw new \InvalidArgumentException(
                "Invalid extension id '{$value}': must match " . self::PATTERN . '.',
            );
        }

        return new self($trimmed);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
