<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Validation;

/**
 * Ограничения email платформы.
 */
final class EmailConstraint
{
    public function __construct(
        public readonly int $max,
    ) {
    }
}
