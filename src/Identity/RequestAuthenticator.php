<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Identity;

/**
 * Аутентификация запроса от имени пользователя ядра — для расширений с
 * собственными схемами аутентификации (OAuth/PAT). После проверки токена
 * расширение передаёт сюда id, ядро выставляет актора текущим, чтобы downstream
 * middleware ядра (проверка capability и т.п.) работали как обычно.
 */
interface RequestAuthenticator
{
    /**
     * @return Actor|null null, если пользователь не существует
     */
    public function authenticateAs(int $userId): ?Actor;
}
