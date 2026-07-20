<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Http;

/**
 * Нейтральный иммутабельный результат HTTP-ответа расширения.
 *
 * Заменяет V1 `PluginResponse`/`ApiResponder`, которые возвращали
 * `Symfony\Response` и ходили через `app()` (service locator). Здесь — чистое
 * значение (status/body/headers) + стандартный конверт ядра ('data'/'meta'),
 * который РЕНДЕРИТ ХОСТ. Расширение не лепит конверт руками и не зависит от
 * Symfony/Laravel.
 */
final class Reply
{
    /**
     * @param array<string, string> $headers
     */
    private function __construct(
        public readonly int $status,
        public readonly mixed $body,
        public readonly array $headers = [],
    ) {
    }

    public static function ok(mixed $data): self
    {
        return new self(200, ['data' => $data]);
    }

    public static function created(mixed $data): self
    {
        return new self(201, ['data' => $data]);
    }

    public static function noContent(): self
    {
        return new self(204, null);
    }

    /**
     * Постраничный ответ: данные + meta.pagination в едином формате ядра.
     *
     * @param list<mixed> $items
     */
    public static function page(array $items, Pagination $pagination): self
    {
        return new self(200, [
            'data' => $items,
            'meta' => ['pagination' => $pagination->toArray()],
        ]);
    }

    /**
     * Escape-hatch: произвольное тело и статус без конверта 'data'
     * (для нестандартных контрактов — например, протокольных эндпоинтов).
     */
    public static function raw(mixed $body, int $status = 200): self
    {
        return new self($status, $body);
    }

    public function withStatus(int $status): self
    {
        return new self($status, $this->body, $this->headers);
    }

    public function withHeader(string $name, string $value): self
    {
        return new self($this->status, $this->body, [...$this->headers, $name => $value]);
    }
}
