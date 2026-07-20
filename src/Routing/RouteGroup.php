<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Routing;

/**
 * Группа маршрутов внутри одной зоны (api/adminApi/web).
 */
final class RouteGroup
{
    /** @var list<RouteDefinition> */
    private array $routes = [];

    public function __construct(private readonly string $namePrefix) {}

    /**
     * @param  array{0: string, 1: string}|string  $action  [Controller::class, 'method'],
     *                                                      'Controller@method' или invokable Controller::class
     */
    public function get(string $uri, array|string $action): RouteDefinition
    {
        return $this->match(['GET'], $uri, $action);
    }

    /** @param array{0: string, 1: string}|string $action */
    public function post(string $uri, array|string $action): RouteDefinition
    {
        return $this->match(['POST'], $uri, $action);
    }

    /** @param array{0: string, 1: string}|string $action */
    public function put(string $uri, array|string $action): RouteDefinition
    {
        return $this->match(['PUT'], $uri, $action);
    }

    /** @param array{0: string, 1: string}|string $action */
    public function patch(string $uri, array|string $action): RouteDefinition
    {
        return $this->match(['PATCH'], $uri, $action);
    }

    /** @param array{0: string, 1: string}|string $action */
    public function delete(string $uri, array|string $action): RouteDefinition
    {
        return $this->match(['DELETE'], $uri, $action);
    }

    /**
     * @param  list<string>  $methods
     * @param  array{0: string, 1: string}|string  $action
     */
    public function match(array $methods, string $uri, array|string $action): RouteDefinition
    {
        $route = new RouteDefinition(
            methods: array_map(strtoupper(...), $methods),
            uri: $uri,
            action: $this->normalizeAction($action),
        );

        $this->routes[] = $route;

        return $route;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toNodes(): array
    {
        return array_map(
            fn (RouteDefinition $route): array => $route->toNode($this->namePrefix),
            $this->routes,
        );
    }

    /**
     * @param  array{0: string, 1: string}|string  $action
     */
    private function normalizeAction(array|string $action): string
    {
        return is_array($action) ? $action[0].'@'.$action[1] : $action;
    }
}
