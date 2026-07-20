<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Access;

/**
 * Действия capability, поддерживаемые ядром. Зеркалит capability-каталог ядра;
 * соответствие охраняется contract-guard тестом ядра (см. ADR 0002).
 */
final class CapabilityAction
{
    public const ACCESS = 'access';

    public const READ = 'read';

    public const WRITE = 'write';

    public const DELETE = 'delete';

    private function __construct() {}
}
