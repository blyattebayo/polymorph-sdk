<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Tests\Support;

use RuntimeException;

/**
 * Фейковый аналог нарушения UNIQUE-ограничения БД — для проверки гонко-безопасных
 * путей firstOrCreate/upsert в контракте Repository.
 */
final class UniqueViolation extends RuntimeException
{
}
