<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Identity;

use Polymorph\Sdk\Access\CapabilityAction;
use Polymorph\Sdk\Errors\ExtensionError;

/**
 * Текущий аутентифицированный пользователь и проверка его прав.
 *
 * Отличие от V1: requireActor()/requireId() бросают {@see ExtensionError}
 * (свой тип SDK), а не `Symfony\…\HttpException` — граница больше не течёт.
 */
interface CurrentActor
{
    public function actor(): ?Actor;

    /**
     * @throws ExtensionError unauthorized, если пользователь не аутентифицирован
     */
    public function requireActor(): Actor;

    public function id(): ?int;

    /**
     * @throws ExtensionError unauthorized
     */
    public function requireId(): int;

    /**
     * Проверить capability текущего пользователя через рантайм авторизации ядра.
     */
    public function can(string $resource, string $action = CapabilityAction::ACCESS): bool;
}
