<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Tests\Support;

use Polymorph\Sdk\Data\Entity;
use Polymorph\Sdk\Data\EntityPage;
use Polymorph\Sdk\Data\FieldDefinition;
use Polymorph\Sdk\Data\Query;
use Polymorph\Sdk\Data\QueryExecutor;
use Polymorph\Sdk\Data\Repository;
use Polymorph\Sdk\Data\SchemaSpec;
use Polymorph\Sdk\Http\Pagination;
use RuntimeException;

/**
 * In-memory реализация Repository — зерно будущего contract-kit (`sdk-testing` v2).
 * Тот же набор поведенческих контрактов прогоняется против неё и реальной
 * flexible-реализации (Epic 1, хост). Проверяет required/unique и семантику query.
 *
 * @implements Repository<Entity>
 */
final class InMemoryRepository implements Repository, QueryExecutor
{
    /** @var array<int, array{data: array<string, mixed>, revision: int, author_id: int|null}> */
    private array $rows = [];

    private int $nextId = 1;

    private ?int $actor = null;

    /** @var array<string, FieldDefinition> */
    private array $fields = [];

    /** @var class-string<Entity> */
    private readonly string $entityClass;

    /**
     * @param class-string<Entity>|null $entityClass подкласс для гидрации
     *        (см. EntityGenerator); по умолчанию базовый Entity
     */
    public function __construct(SchemaSpec $spec, ?string $entityClass = null)
    {
        foreach ($spec->fields as $field) {
            $this->fields[$field->name] = $field;
        }

        $this->entityClass = $entityClass ?? Entity::class;
    }

    public function actingAs(?int $userId): self
    {
        $this->actor = $userId;

        return $this;
    }

    public function create(array $data): Entity
    {
        $this->assertRequired($data);
        $this->assertUnique($data, null);

        $id = $this->nextId++;
        $this->rows[$id] = ['data' => $data, 'revision' => 1, 'author_id' => $this->actor];

        return $this->entity($id);
    }

    public function find(int $id): ?Entity
    {
        return isset($this->rows[$id]) ? $this->entity($id) : null;
    }

    public function update(int $id, array $partial): Entity
    {
        $row = $this->requireRow($id);
        $merged = array_merge($row['data'], $partial);

        $this->assertRequired($merged);
        $this->assertUnique($merged, $id);

        $this->rows[$id]['data'] = $merged;
        $this->rows[$id]['revision']++;

        return $this->entity($id);
    }

    public function replace(int $id, array $data): Entity
    {
        $this->requireRow($id);
        $this->assertRequired($data);
        $this->assertUnique($data, $id);

        $this->rows[$id]['data'] = $data;
        $this->rows[$id]['revision']++;

        return $this->entity($id);
    }

    public function delete(int $id): void
    {
        unset($this->rows[$id]);
    }

    public function all(): array
    {
        return array_map(fn (int $id): Entity => $this->entity($id), array_keys($this->rows));
    }

    public function query(): Query
    {
        return new Query($this);
    }

    public function increment(int $id, string $field, int|float $delta): Entity
    {
        $row = $this->requireRow($id);
        $this->assertNumericField($field);
        $current = is_numeric($row['data'][$field] ?? null) ? $row['data'][$field] + 0 : 0;
        $this->rows[$id]['data'][$field] = $current + $delta;
        $this->rows[$id]['revision']++;

        return $this->entity($id);
    }

    public function firstOrCreate(array $match, array $defaults = []): Entity
    {
        $existing = $this->matchQuery($match)->first();
        if ($existing !== null) {
            return $existing;
        }

        try {
            return $this->create([...$match, ...$defaults]);
        } catch (UniqueViolation) {
            $raced = $this->matchQuery($match)->first();
            if ($raced !== null) {
                return $raced;
            }
            throw new RuntimeException('firstOrCreate: unique violation without matching row.');
        }
    }

    public function upsert(array $match, array $values = []): Entity
    {
        $existing = $this->matchQuery($match)->first();
        if ($existing !== null) {
            return $this->update($existing->id, $values);
        }

        try {
            return $this->create([...$match, ...$values]);
        } catch (UniqueViolation) {
            $raced = $this->matchQuery($match)->first();
            if ($raced !== null) {
                return $this->update($raced->id, $values);
            }
            throw new RuntimeException('upsert: unique violation without matching row.');
        }
    }

    // ───────── QueryExecutor ─────────

    public function runGet(Query $query): array
    {
        $ids = $this->filteredIds($query);

        return array_map(fn (int $id): Entity => $this->entity($id), $ids);
    }

    public function runFirst(Query $query): ?Entity
    {
        $ids = $this->filteredIds($query);

        return $ids === [] ? null : $this->entity($ids[0]);
    }

    public function runExists(Query $query): bool
    {
        return $this->filteredIds($query) !== [];
    }

    public function runCount(Query $query): int
    {
        return count($this->filteredIds($query));
    }

    public function runPaginate(Query $query, int $page, int $perPage): EntityPage
    {
        $ids = $this->filteredIds($query);
        $total = count($ids);
        $slice = array_slice($ids, ($page - 1) * $perPage, $perPage);
        $items = array_map(fn (int $id): Entity => $this->entity($id), $slice);

        return new EntityPage($items, new Pagination($page, $perPage, $total));
    }

    public function runAggregate(Query $query, string $func, string $field): ?float
    {
        $this->assertNumericField($field);
        $values = [];
        foreach ($this->filteredIds($query) as $id) {
            $v = $this->rows[$id]['data'][$field] ?? null;
            if (is_numeric($v)) {
                $values[] = (float) $v;
            }
        }

        if ($values === []) {
            return $func === 'sum' ? 0.0 : null;
        }

        return match ($func) {
            'sum' => array_sum($values),
            'avg' => array_sum($values) / count($values),
            'min' => min($values),
            'max' => max($values),
            default => throw new \InvalidArgumentException("Unsupported aggregate '{$func}'."),
        };
    }

    // ───────── Внутреннее ─────────

    /**
     * @return list<int>
     */
    private function filteredIds(Query $query): array
    {
        $ids = array_values(array_filter(array_keys($this->rows), function (int $id) use ($query): bool {
            if ($query->authorId() !== null && $this->rows[$id]['author_id'] !== $query->authorId()) {
                return false;
            }
            foreach ($query->conditions() as $cond) {
                if (!$this->matches($this->fieldValue($id, $cond['field']), $cond['op'], $cond['value'])) {
                    return false;
                }
            }

            return true;
        }));

        $ids = $this->applyOrders($ids, $query->orders());

        $limit = $query->limitValue();

        return $limit === null ? $ids : array_slice($ids, 0, $limit);
    }

    /**
     * Значение поля для фильтра/сортировки. Системные 'id'/'author_id' берутся из
     * метаданных строки, прочие — из data.
     */
    private function fieldValue(int $id, string $field): mixed
    {
        $row = $this->rows[$id];

        return match ($field) {
            'id' => $id,
            'author_id' => $row['author_id'],
            default => $row['data'][$field] ?? null,
        };
    }

    /**
     * Зеркалит детерминированный tie-break ядра (EloquentRecordRepository): ничьи
     * разрешаются по системному id, направление = направлению ПОСЛЕДНЕГО order
     * (asc → id asc, desc/без order → id desc). Та же логика в sdk-testing —
     * фейк и реальный flexible-адаптер обязаны вести себя одинаково.
     *
     * @param list<int> $ids
     * @param list<array{field: string, dir: string}> $orders
     * @return list<int>
     */
    private function applyOrders(array $ids, array $orders): array
    {
        $tieDir = 'desc';
        foreach ($orders as $order) {
            $tieDir = $order['dir'] === 'asc' ? 'asc' : 'desc';
        }

        usort($ids, function (int $a, int $b) use ($orders, $tieDir): int {
            foreach ($orders as $order) {
                $cmp = $this->fieldValue($a, $order['field']) <=> $this->fieldValue($b, $order['field']);
                if ($cmp !== 0) {
                    return $order['dir'] === 'desc' ? -$cmp : $cmp;
                }
            }

            return $tieDir === 'desc' ? ($b <=> $a) : ($a <=> $b);
        });

        return $ids;
    }

    private function matches(mixed $value, string $op, mixed $expected): bool
    {
        return match ($op) {
            '=' => $value == $expected,
            '!=' => $value != $expected,
            '<' => $value < $expected,
            '<=' => $value <= $expected,
            '>' => $value > $expected,
            '>=' => $value >= $expected,
            'in' => is_array($expected) && in_array($value, $expected, false),
            'isnull' => $value === null,
            'notnull' => $value !== null,
            default => throw new \InvalidArgumentException("Unsupported operator '{$op}'."),
        };
    }

    /**
     * @param array<string, mixed> $match
     */
    private function matchQuery(array $match): Query
    {
        $query = $this->query();
        foreach ($match as $field => $value) {
            $query->where((string) $field, $value);
        }

        return $query;
    }

    /**
     * @return array{data: array<string, mixed>, revision: int, author_id: int|null}
     */
    private function requireRow(int $id): array
    {
        if (!isset($this->rows[$id])) {
            throw new RuntimeException("Record {$id} not found.");
        }

        return $this->rows[$id];
    }

    private function entity(int $id): Entity
    {
        $row = $this->rows[$id];
        $class = $this->entityClass;

        return new $class($id, $row['data'], $row['revision'], $row['author_id']);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertRequired(array $data): void
    {
        foreach ($this->fields as $field) {
            if ($field->isRequired() && (($data[$field->name] ?? null) === null)) {
                throw new \InvalidArgumentException("Field '{$field->name}' is required.");
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertUnique(array $data, ?int $exceptId): void
    {
        foreach ($this->fields as $field) {
            if (!$field->unique) {
                continue;
            }
            $value = $data[$field->name] ?? null;
            if ($value === null) {
                continue;
            }
            foreach ($this->rows as $id => $row) {
                if ($id === $exceptId) {
                    continue;
                }
                if (($row['data'][$field->name] ?? null) == $value) {
                    throw new UniqueViolation("Unique violation on '{$field->name}'.");
                }
            }
        }
    }

    private function assertNumericField(string $field): void
    {
        $def = $this->fields[$field] ?? null;
        if ($def === null || !$def->type->isNumeric()) {
            throw new \InvalidArgumentException("Field '{$field}' is not numeric.");
        }
    }
}
