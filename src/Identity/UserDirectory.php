<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Identity;

use Polymorph\Sdk\Errors\ExtensionError;

interface UserDirectory
{
    public function findById(int $userId): ?User;

    /** @throws ExtensionError notFound */
    public function requireById(int $userId): User;

    public function exists(int $userId): bool;
}
