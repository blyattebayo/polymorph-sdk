<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Identity;

/** Immutable SDK projection of a platform user. */
final readonly class User
{
    public function __construct(
        public int $id,
        public string $email,
        public ?string $name = null,
    ) {}
}
