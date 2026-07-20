<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Routing;

use Polymorph\Sdk\Access\Capability;

/**
 * Один маршрут расширения. Создаётся через методы {@see RouteGroup}.
 */
final class RouteDefinition
{
    private ?string $name = null;

    /** @var list<string> */
    private array $middleware = [];

    /**
     * @param list<string> $methods
     */
    public function __construct(
        private readonly array $methods,
        private readonly string $uri,
        private readonly string $action,
    ) {
    }

    /**
     * Имя относительно зоны (дополнится префиксом: 'catalog' → 'api.v1.ext.{id}.catalog').
     */
    public function name(string $name): self
    {
        $this->name = trim($name);

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
     * ЕДИНЫЙ способ потребовать право: добавляет capability-middleware ядра.
     */
    public function requires(Capability $capability): self
    {
        return $this->middleware(Middleware::requireCapability($capability));
    }

    /** Исключить CSRF-проверку (для machine-to-machine эндпоинтов). */
    public function withoutCsrf(): self
    {
        return $this->middleware(Middleware::withoutCsrf());
    }

    /**
     * Скомпилировать в узел маршрута в формате ядра (route_nodes).
     *
     * @return array<string, mixed>
     */
    public function toNode(string $namePrefix): array
    {
        $node = [
            'kind' => 'route',
            'uri' => $this->uri,
            'methods' => $this->methods,
            'action_type' => 'controller',
            'action_meta' => ['action' => $this->action],
            'name' => $namePrefix . '.' . ($this->name ?? $this->defaultName()),
        ];

        if ($this->middleware !== []) {
            $node['middleware'] = $this->middleware;
        }

        return $node;
    }

    private function defaultName(): string
    {
        $normalized = strtolower(trim($this->uri, '/'));
        $normalized = (string) preg_replace('/\{([^}]+)\}/', '$1', $normalized);
        $normalized = str_replace('/', '.', $normalized);

        return ($normalized === '' ? 'index' : $normalized) . '.' . strtolower(implode('-', $this->methods));
    }
}
