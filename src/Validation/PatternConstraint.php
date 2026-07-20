<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Validation;

/**
 * Правило-паттерн платформы (slug/aclAction/roleCode). Значения — собственность
 * ядра; сюда они попадают через реализацию {@see ValidationConstraints} (хост).
 */
final class PatternConstraint
{
    /**
     * @param string $pattern тело регулярки без разделителей
     */
    public function __construct(
        public readonly string $pattern,
        public readonly int $max,
    ) {
    }

    /** Регулярка с разделителями для preg_*. Слэши экранируются — паритет с ядром. */
    public function phpPattern(): string
    {
        return '/' . str_replace('/', '\\/', $this->pattern) . '/';
    }

    public function matches(string $value): bool
    {
        return mb_strlen($value) <= $this->max && preg_match($this->phpPattern(), $value) === 1;
    }
}
