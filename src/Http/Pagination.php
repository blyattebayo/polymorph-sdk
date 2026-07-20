<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Http;

/**
 * Метаданные пагинации для {@see Reply::page()}. Нейтральный VO — никакой
 * зависимости от пагинатора ядра/Illuminate.
 */
final class Pagination
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $total,
    ) {
    }

    public function hasMorePages(): bool
    {
        return $this->page * $this->perPage < $this->total;
    }

    /**
     * @return array{page: int, per_page: int, total: int, has_more: bool}
     */
    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'per_page' => $this->perPage,
            'total' => $this->total,
            'has_more' => $this->hasMorePages(),
        ];
    }
}
