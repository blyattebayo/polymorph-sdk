<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Data;

/**
 * Исполнитель запросов (реализуется хостом поверх индексов БД, либо фейком в
 * тестах). Терминальные методы {@see Query} делегируют сюда. Расширение с этим
 * интерфейсом напрямую не работает.
 */
interface QueryExecutor
{
    /** @return list<Entity> */
    public function runGet(Query $query): array;

    public function runFirst(Query $query): ?Entity;

    public function runExists(Query $query): bool;

    public function runCount(Query $query): int;

    public function runPaginate(Query $query, int $page, int $perPage): EntityPage;

    /** Скалярная агрегация (sum/avg/min/max) числового поля. */
    public function runAggregate(Query $query, string $func, string $field): ?float;
}
