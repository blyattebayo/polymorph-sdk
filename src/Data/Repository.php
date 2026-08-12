<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Data;

/**
 * Единый контракт доступа к записям ОДНОЙ сущности расширения.
 *
 * Ключевое отличие от V1 `RecordStore`:
 *  - репозиторий привязан к одной сущности (не принимает definitionCode в каждом
 *    вызове) — DX чище;
 *  - это **единственный шов модели данных**: сейчас реализуется flexible-бэкендом
 *    (jsonb поверх ядра), а owned-бэкенд (реальные таблицы) можно добавить позже
 *    БЕЗ переписывания бизнес-логики расширения.
 *
 * @template T of Entity
 */
interface Repository
{
    /**
     * @param  array<string, mixed>  $data
     * @return T
     */
    public function create(array $data): Entity;

    /** @return T|null */
    public function find(int $id): ?Entity;

    /**
     * Безопасный partial update (мерж поверх текущих данных, атомарно).
     *
     * @param  array<string, mixed>  $partial
     * @return T
     */
    public function update(int $id, array $partial): Entity;

    /**
     * Полная замена данных (явная destructive-операция).
     *
     * @param  array<string, mixed>  $data
     * @return T
     */
    public function replace(int $id, array $data): Entity;

    public function delete(int $id): void;

    /** @return list<T> */
    public function all(): array;

    public function query(): Query;

    /**
     * Атомарный инкремент числового поля (без read-modify-write).
     *
     * @return T
     */
    public function increment(int $id, string $field, int|float $delta): Entity;

    /**
     * Найти по равенству match, иначе создать (match + defaults). Гонко-безопасно
     * при наличии unique-поля.
     *
     * @param  array<string, mixed>  $match
     * @param  array<string, mixed>  $defaults
     * @return T
     */
    public function firstOrCreate(array $match, array $defaults = []): Entity;

    /**
     * Создать по match или обновить существующую (мерж values).
     *
     * @param  array<string, mixed>  $match
     * @param  array<string, mixed>  $values
     * @return T
     */
    public function upsert(array $match, array $values = []): Entity;
}
