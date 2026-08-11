<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Identity;

use Polymorph\Sdk\Access\CapabilityAction;
use Polymorph\Sdk\Errors\ExtensionError;

interface CurrentUser
{
    public function user(): ?User;

    /** @throws ExtensionError unauthorized */
    public function requireUser(): User;

    public function id(): ?int;

    /** @throws ExtensionError unauthorized */
    public function requireId(): int;

    public function can(string $resource, string $action = CapabilityAction::ACCESS): bool;
}
