<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Data;

use Polymorph\Sdk\Http\Pagination;

/**
 * Страница сущностей. Переиспользует {@see Pagination} как метаданные.
 */
final class EntityPage
{
    /**
     * @param list<Entity> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly Pagination $pagination,
    ) {
    }
}
