<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Data;

/**
 * Fluent-построитель запроса записей сущности. В отличие от V1, привязан к одной
 * сущности через {@see Repository::query()} — не принимает definitionCode в
 * каждом методе. Терминалы исполняет {@see QueryExecutor} (хост/фейк).
 */
final class Query
{
    /** Часовой для отличия where($f,$v) от where($f,$op,$v). */
    private const NO_VALUE = "\0__pq_no_value__\0";

    private const OPERATORS = ['=', '!=', '<', '<=', '>', '>=', 'in'];

    /** @var list<array{field: string, op: string, value: mixed}> */
    private array $conditions = [];

    private ?int $authorId = null;

    /** @var list<array{field: string, dir: string}> */
    private array $orders = [];

    private ?int $limit = null;

    public function __construct(private readonly QueryExecutor $executor)
    {
    }

    public function where(string $field, mixed $opOrValue, mixed $value = self::NO_VALUE): self
    {
        if ($value === self::NO_VALUE) {
            $op = '=';
            $val = $opOrValue;
        } else {
            $op = strtolower(trim((string) $opOrValue));
            $val = $value;
        }

        $this->assertOperator($op);
        $this->conditions[] = ['field' => $field, 'op' => $op, 'value' => $val];

        return $this;
    }

    /**
     * @param list<mixed> $values
     */
    public function whereIn(string $field, array $values): self
    {
        $this->conditions[] = ['field' => $field, 'op' => 'in', 'value' => array_values($values)];

        return $this;
    }

    public function whereNull(string $field): self
    {
        $this->conditions[] = ['field' => $field, 'op' => 'isnull', 'value' => null];

        return $this;
    }

    public function whereNotNull(string $field): self
    {
        $this->conditions[] = ['field' => $field, 'op' => 'notnull', 'value' => null];

        return $this;
    }

    public function whereAuthor(int $userId): self
    {
        $this->authorId = $userId;

        return $this;
    }

    public function orderBy(string $field, string $dir = 'asc'): self
    {
        $this->orders[] = ['field' => $field, 'dir' => strtolower(trim($dir)) === 'desc' ? 'desc' : 'asc'];

        return $this;
    }

    public function orderByDesc(string $field): self
    {
        return $this->orderBy($field, 'desc');
    }

    public function limit(int $count): self
    {
        $this->limit = max(0, $count);

        return $this;
    }

    // ───────── Интроспекция для исполнителя ─────────

    /** @return list<array{field: string, op: string, value: mixed}> */
    public function conditions(): array
    {
        return $this->conditions;
    }

    public function authorId(): ?int
    {
        return $this->authorId;
    }

    /** @return list<array{field: string, dir: string}> */
    public function orders(): array
    {
        return $this->orders;
    }

    public function limitValue(): ?int
    {
        return $this->limit;
    }

    // ───────── Терминалы ─────────

    /** @return list<Entity> */
    public function get(): array
    {
        return $this->executor->runGet($this);
    }

    public function first(): ?Entity
    {
        return $this->executor->runFirst($this);
    }

    public function exists(): bool
    {
        return $this->executor->runExists($this);
    }

    public function count(): int
    {
        return $this->executor->runCount($this);
    }

    public function paginate(int $page, int $perPage): EntityPage
    {
        return $this->executor->runPaginate($this, max(1, $page), max(1, $perPage));
    }

    public function sum(string $field): float
    {
        return (float) ($this->executor->runAggregate($this, 'sum', $field) ?? 0.0);
    }

    public function avg(string $field): ?float
    {
        return $this->executor->runAggregate($this, 'avg', $field);
    }

    private function assertOperator(string $op): void
    {
        if (!in_array($op, self::OPERATORS, true)) {
            throw new \InvalidArgumentException("Unsupported query operator '{$op}'.");
        }
    }
}
