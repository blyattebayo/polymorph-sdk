<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Routing;

/**
 * Набор маршрутов расширения в одной зоне.
 */
final readonly class Zone
{
    /**
     * @param  list<string>  $middleware  Middleware зоны
     * @param  list<string>  $withoutMiddleware  Middleware, снимаемые с зоны
     * @param  list<RouteDefinition>  $routes
     */
    public function __construct(
        public ZoneKind $kind,
        public array $middleware,
        public array $withoutMiddleware,
        public array $routes,
    ) {}
}
