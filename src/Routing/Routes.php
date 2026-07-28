<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Routing;

use Closure;
use Polymorph\Sdk\Access\Capability;

/**
 * Маршруты расширения.
 *
 * Расширение объявляет ЗОНУ и маршруты в ней. Ни своего id, ни префикса URI,
 * ни префикса имени оно не задаёт — их подставляет хост, который и так знает,
 * чей файл он только что исполнил. Поэтому расширение не может ни выйти
 * за свою поверхность, ни занять чужое имя, и отдельный класс ограничений
 * поверхности хосту больше не нужен.
 *
 *     return Routes::define()
 *         ->api(function (RouteGroup $r): void {
 *             $r->get('/items', [ItemController::class, 'index'])->name('items.index');
 *         })
 *         ->adminApi(function (RouteGroup $r): void {
 *             $r->post('/items', [ItemController::class, 'store'])->requires($manage);
 *         });
 */
final class Routes
{
    /** @var list<Zone> */
    private array $zones = [];

    private function __construct() {}

    public static function define(): self
    {
        return new self;
    }

    /**
     * Публичная зона расширения.
     *
     * @param  Closure(RouteGroup): void  $routes
     * @param  list<string>  $middleware
     * @param  list<string>  $without  Снимаемые middleware (см. Middleware::CSRF)
     */
    public function api(Closure $routes, array $middleware = [], array $without = [], ?Capability $requires = null): self
    {
        return $this->zone(ZoneKind::API, $routes, $middleware, $without, $requires);
    }

    /**
     * Админская зона расширения.
     *
     * @param  Closure(RouteGroup): void  $routes
     * @param  list<string>  $middleware
     * @param  list<string>  $without
     */
    public function adminApi(Closure $routes, array $middleware = [], array $without = [], ?Capability $requires = null): self
    {
        return $this->zone(ZoneKind::ADMIN_API, $routes, $middleware, $without, $requires);
    }

    /**
     * Web-зона расширения.
     *
     * @param  Closure(RouteGroup): void  $routes
     * @param  list<string>  $middleware
     * @param  list<string>  $without
     */
    public function web(Closure $routes, array $middleware = [], array $without = [], ?Capability $requires = null): self
    {
        return $this->zone(ZoneKind::WEB, $routes, $middleware, $without, $requires);
    }

    /** @return list<Zone> */
    public function zones(): array
    {
        return $this->zones;
    }

    /**
     * @param  Closure(RouteGroup): void  $routes
     * @param  list<string>  $middleware
     * @param  list<string>  $without
     */
    private function zone(ZoneKind $kind, Closure $routes, array $middleware, array $without, ?Capability $requires): self
    {
        if ($requires !== null) {
            $middleware[] = Middleware::requireCapability($requires);
        }

        $group = new RouteGroup;
        $routes($group);

        $this->zones[] = new Zone(
            kind: $kind,
            middleware: array_values($middleware),
            withoutMiddleware: array_values($without),
            routes: $group->routes(),
        );

        return $this;
    }
}
