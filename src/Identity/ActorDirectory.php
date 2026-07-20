<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Identity;

use Polymorph\Sdk\Errors\ExtensionError;

/**
 * Поиск пользователей ядра по идентификатору — замена прямым запросам к модели
 * пользователя из расширения.
 */
interface ActorDirectory
{
    public function findById(int $userId): ?Actor;

    /**
     * @throws ExtensionError notFound, если пользователь не найден
     */
    public function requireById(int $userId): Actor;

    public function exists(int $userId): bool;
}
