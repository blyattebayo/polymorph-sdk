<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Routing;

use InvalidArgumentException;
use Polymorph\Sdk\Access\Capability;

/**
 * Один маршрут расширения. Создаётся через методы {@see RouteGroup}.
 *
 * Действие — всегда пара [контроллер, метод], никогда голая строка: строку
 * Laravel трактует как «Controller@method» и приписывает ей namespace
 * родительской группы, а пустой метод после собаки доезжает до роутера
 * и падает уже на запросе.
 *
 * Снимаемые middleware живут ОТДЕЛЬНЫМ списком, а не токеном 'without:x'
 * внутри общего: два разных смысла в одном списке строк — ровно та причина,
 * по которой снятие middleware на уровне группы молча не работало.
 */
final class RouteDefinition
{
    /** @var list<string> */
    private array $middleware = [];

    /** @var list<string> */
    private array $withoutMiddleware = [];

    /** @var array<string, string> */
    private array $where = [];

    private ?string $name = null;

    /**
     * @param  list<string>  $methods
     * @param  array{0: string, 1: string}  $action
     */
    public function __construct(
        private readonly array $methods,
        private readonly string $uri,
        private readonly array $action,
    ) {}

    /**
     * Имя ОТНОСИТЕЛЬНО зоны: полное соберёт хост, добавив свой префикс
     * ('catalog' → 'api.v1.ext.{id}.catalog'). Занять имя маршрута ядра нечем.
     */
    public function name(string $name): self
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Route name must not be empty.');
        }

        $this->name = $trimmed;

        return $this;
    }

    public function middleware(string ...$middleware): self
    {
        foreach ($middleware as $entry) {
            $this->middleware[] = $entry;
        }

        return $this;
    }

    /**
     * Снять middleware, applied выше по цепочке (зоной или ядром).
     */
    public function without(string ...$middleware): self
    {
        foreach ($middleware as $entry) {
            $this->withoutMiddleware[] = $entry;
        }

        return $this;
    }

    /** Исключить CSRF-проверку (для machine-to-machine эндпоинтов). */
    public function withoutCsrf(): self
    {
        return $this->without(Middleware::CSRF);
    }

    /** ЕДИНЫЙ способ потребовать право: добавляет capability-middleware ядра. */
    public function requires(Capability $capability): self
    {
        return $this->middleware(Middleware::requireCapability($capability));
    }

    public function where(string $parameter, string $pattern): self
    {
        $this->where[$parameter] = $pattern;

        return $this;
    }

    /** @return list<string> */
    public function methods(): array
    {
        return $this->methods;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    /** @return array{0: string, 1: string} */
    public function action(): array
    {
        return $this->action;
    }

    public function relativeName(): ?string
    {
        return $this->name;
    }

    /** @return list<string> */
    public function middlewareList(): array
    {
        return $this->middleware;
    }

    /** @return list<string> */
    public function withoutMiddlewareList(): array
    {
        return $this->withoutMiddleware;
    }

    /** @return array<string, string> */
    public function whereMap(): array
    {
        return $this->where;
    }
}
