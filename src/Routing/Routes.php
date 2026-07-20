<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Routing;

use Closure;
use Polymorph\Sdk\Access\Capability;
use Polymorph\Sdk\Extension\ExtensionContext;

/**
 * Типизированный builder маршрутов расширения. Компилируется в формат route_nodes
 * ядра (DB-модель маршрутов сохраняется). Зоны выводятся из {@see ExtensionContext}
 * (не зашиты в 'plugins/{id}', как в V1) — generic 'ext/{id}'.
 *
 *     return Routes::for($context)
 *         ->api(function (RouteGroup $r): void {
 *             $r->get('/items', [ItemController::class, 'index'])
 *                 ->requires(Capability::of($context->resource('items'), CapabilityAction::READ));
 *         })
 *         ->adminApi(function (RouteGroup $r): void {
 *             $r->post('/items', [ItemController::class, 'store']);
 *         }, requires: Capability::of($context->resource('items'), CapabilityAction::WRITE))
 *         ->toArray();
 */
final class Routes
{
    /** @var list<array<string, mixed>> */
    private array $groups = [];

    private function __construct(private readonly ExtensionContext $context) {}

    public static function for(ExtensionContext $context): self
    {
        return new self($context);
    }

    /**
     * Публичная API-зона: api/v1/ext/{id}. Базовый middleware: api.
     *
     * @param  Closure(RouteGroup): void  $routes
     * @param  list<string>  $middleware
     */
    public function api(Closure $routes, array $middleware = [], ?Capability $requires = null): self
    {
        $id = $this->context->id->value;

        return $this->group("api/v1/ext/{$id}", "api.v1.ext.{$id}", [Middleware::API, ...$middleware], $routes, $requires);
    }

    /**
     * Админская API-зона: api/v1/admin/ext/{id}. Базовые middleware: api, auth:api.
     *
     * @param  Closure(RouteGroup): void  $routes
     * @param  list<string>  $middleware
     */
    public function adminApi(Closure $routes, array $middleware = [], ?Capability $requires = null): self
    {
        $id = $this->context->id->value;

        return $this->group(
            "api/v1/admin/ext/{$id}",
            "admin.v1.ext.{$id}",
            [Middleware::API, Middleware::JWT_AUTH, ...$middleware],
            $routes,
            $requires,
        );
    }

    /**
     * Web-зона: ext/{id}. Базовый middleware: web.
     *
     * @param  Closure(RouteGroup): void  $routes
     * @param  list<string>  $middleware
     */
    public function web(Closure $routes, array $middleware = [], ?Capability $requires = null): self
    {
        $id = $this->context->id->value;

        return $this->group("ext/{$id}", "ext.{$id}", [Middleware::WEB, ...$middleware], $routes, $requires);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toArray(): array
    {
        return $this->groups;
    }

    /**
     * @param  list<string>  $middleware
     * @param  Closure(RouteGroup): void  $routes
     */
    private function group(string $prefix, string $namePrefix, array $middleware, Closure $routes, ?Capability $requires): self
    {
        if ($requires !== null) {
            $middleware[] = Middleware::requireCapability($requires);
        }

        $group = new RouteGroup($namePrefix);
        $routes($group);

        $this->groups[] = [
            'kind' => 'group',
            'prefix' => $prefix,
            'middleware' => $middleware,
            'children' => $group->toNodes(),
        ];

        return $this;
    }
}
