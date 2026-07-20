<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Data;

/**
 * Типизированный снимок записи — замена нетипизированному `PluginRecord` V1.
 *
 * Может использоваться напрямую (типизированные аксессоры лучше «голого»
 * `array<string,mixed>`), а также служит базой для codegen: генератор создаёт
 * `final class Server extends Entity` с типизированными свойствами из SchemaSpec.
 */
class Entity
{
    /**
     * @param array<string, mixed> $data чистые данные (без системных ключей)
     */
    public function __construct(
        public readonly int $id,
        public readonly array $data,
        public readonly int $revision,
        public readonly ?int $authorId = null,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->data[$key] ?? null;

        return is_scalar($value) ? (string) $value : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->data[$key] ?? null;

        return is_numeric($value) ? (int) $value : $default;
    }

    public function float(string $key, float $default = 0.0): float
    {
        $value = $this->data[$key] ?? null;

        return is_numeric($value) ? (float) $value : $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->data[$key] ?? null;

        return is_scalar($value) ? (bool) $value : $default;
    }

    /**
     * @return array<mixed>
     */
    public function array(string $key): array
    {
        $value = $this->data[$key] ?? null;

        return is_array($value) ? $value : [];
    }

    /** ISO-строка datetime-поля (парсинг в DateTimeImmutable — на стороне потребителя). */
    public function datetime(string $key): ?string
    {
        $value = $this->data[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
