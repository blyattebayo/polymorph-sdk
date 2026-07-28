<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Routing;

use InvalidArgumentException;

/**
 * Маршруты внутри одной зоны расширения.
 */
final class RouteGroup
{
    /** @var list<RouteDefinition> */
    private array $routes = [];

    /** @param array{0: string, 1: string} $action */
    public function get(string $uri, array $action): RouteDefinition
    {
        return $this->match(['GET'], $uri, $action);
    }

    /** @param array{0: string, 1: string} $action */
    public function post(string $uri, array $action): RouteDefinition
    {
        return $this->match(['POST'], $uri, $action);
    }

    /** @param array{0: string, 1: string} $action */
    public function put(string $uri, array $action): RouteDefinition
    {
        return $this->match(['PUT'], $uri, $action);
    }

    /** @param array{0: string, 1: string} $action */
    public function patch(string $uri, array $action): RouteDefinition
    {
        return $this->match(['PATCH'], $uri, $action);
    }

    /** @param array{0: string, 1: string} $action */
    public function delete(string $uri, array $action): RouteDefinition
    {
        return $this->match(['DELETE'], $uri, $action);
    }

    /**
     * @param  list<string>  $methods
     * @param  array{0: string, 1: string}  $action  [Controller::class, 'method'];
     *                                               для invokable — [Controller::class, '__invoke']
     */
    public function match(array $methods, string $uri, array $action): RouteDefinition
    {
        if ($methods === []) {
            throw new InvalidArgumentException('Route must declare at least one HTTP method.');
        }

        $route = new RouteDefinition(
            methods: array_values(array_map(strtoupper(...), $methods)),
            uri: $uri,
            action: self::assertAction($action),
        );

        $this->routes[] = $route;

        return $route;
    }

    /** @return list<RouteDefinition> */
    public function routes(): array
    {
        return $this->routes;
    }

    /**
     * @param  array{0: string, 1: string}  $action
     * @return array{0: string, 1: string}
     */
    private static function assertAction(array $action): array
    {
        $controller = trim($action[0] ?? '');
        $method = trim($action[1] ?? '');

        if ($controller === '' || $method === '') {
            throw new InvalidArgumentException(
                'Route action must be [Controller::class, \'method\']; use \'__invoke\' for invokable controllers.',
            );
        }

        return [$controller, $method];
    }
}
