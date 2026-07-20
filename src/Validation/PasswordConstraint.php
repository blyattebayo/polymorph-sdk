<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Validation;

/**
 * Ограничения пароля платформы.
 */
final class PasswordConstraint
{
    public function __construct(
        public readonly int $min,
        public readonly int $max,
    ) {}

    public function isValidLength(string $value): bool
    {
        $length = mb_strlen($value);

        return $length >= $this->min && $length <= $this->max;
    }
}
