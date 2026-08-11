<?php

declare(strict_types=1);

/**
 * Автономный smoke-тест поверхности Epic 2: routing builder, Capability,
 * validation VO, identity DTO. Запуск: php be/sdk-v2/tests/smoke_surface.php
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'Polymorph\\Sdk\\';
    if (! str_starts_with($class, $prefix)) {
        return;
    }
    $path = __DIR__.'/../src/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
    if (is_file($path)) {
        require $path;
    }
});

use Polymorph\Sdk\Access\Capability;
use Polymorph\Sdk\Access\CapabilityAction;
use Polymorph\Sdk\Extension\ExtensionContext;
use Polymorph\Sdk\Identity\Actor;
use Polymorph\Sdk\Routing\Middleware;
use Polymorph\Sdk\Routing\RouteGroup;
use Polymorph\Sdk\Routing\Routes;
use Polymorph\Sdk\Routing\ZoneKind;
use Polymorph\Sdk\Validation\PasswordConstraint;
use Polymorph\Sdk\Validation\PatternConstraint;

$failures = [];
$count = 0;

function check(string $name, bool $ok): void
{
    global $failures, $count;
    $count++;
    if (! $ok) {
        $failures[] = $name;
        fwrite(STDERR, "FAIL: {$name}\n");
    }
}

function throws(callable $fn): bool
{
    try {
        $fn();

        return false;
    } catch (Throwable) {
        return true;
    }
}

// ── Capability ──
$cap = Capability::of('ext.demo.items', CapabilityAction::READ);
check('capability resource', $cap->resource === 'ext.demo.items');
check('capability action', $cap->action === 'read');
check('capability default action', Capability::of('ext.demo.x')->action === 'access');
check('capability empty rejected', throws(static fn () => Capability::of('', 'read')));
check('middleware string', Middleware::requireCapability($cap) === 'capability.require:ext.demo.items,read');
check('session middleware string', Middleware::SESSION_AUTH === 'auth:session');
check('OAuth resource middleware string', Middleware::OAUTH_AUTH === 'oauth.resource');
check('csrf symbol', Middleware::CSRF === 'csrf');

// ── Routes builder ──
// Расширение объявляет ТОЛЬКО зону и путь внутри неё: ни id, ни префикса пути,
// ни префикса имени здесь нет — их подставляет хост при монтировании.
$ctx = ExtensionContext::for('demo');
$zones = Routes::define()
    ->api(function (RouteGroup $r) use ($ctx): void {
        $r->get('/items', ['Demo\\ItemController', 'index'])
            ->name('items.index')
            ->requires(Capability::of($ctx->resource('items'), CapabilityAction::READ));
        $r->post('/items', ['Demo\\ItemController', 'store']);
    })
    ->adminApi(function (RouteGroup $r): void {
        $r->delete('/items/{item}', ['Demo\\ItemController', 'destroy'])
            ->where('item', '[0-9]+')
            ->withoutCsrf();
    }, requires: Capability::of('ext.demo.manage'))
    ->zones();

check('two zones', count($zones) === 2);

$api = $zones[0];
check('api zone kind', $api->kind === ZoneKind::API);
check('api zone declares no middleware of its own', $api->middleware === []);
check('api two routes', count($api->routes) === 2);

$itemsIndex = $api->routes[0];
check('route uri', $itemsIndex->uri() === '/items');
check('route methods', $itemsIndex->methods() === ['GET']);
check('route action is a tuple', $itemsIndex->action() === ['Demo\\ItemController', 'index']);
check('route name is relative to the zone', $itemsIndex->relativeName() === 'items.index');
check('route capability middleware', $itemsIndex->middlewareList() === ['capability.require:ext.demo.items,read']);

$itemsStore = $api->routes[1];
check('unnamed route stays unnamed', $itemsStore->relativeName() === null);
check('unnamed route has no middleware', $itemsStore->middlewareList() === []);

$admin = $zones[1];
check('admin zone kind', $admin->kind === ZoneKind::ADMIN_API);
check('admin zone capability', $admin->middleware === ['capability.require:ext.demo.manage,access']);

$destroy = $admin->routes[0];
check('admin route methods', $destroy->methods() === ['DELETE']);
check('admin route where', $destroy->whereMap() === ['item' => '[0-9]+']);
check('admin route excludes csrf separately', $destroy->withoutMiddlewareList() === ['csrf']);
check('admin route keeps its middleware list clean', $destroy->middlewareList() === []);

// Действие — только пара [контроллер, метод]: голая строка и пустой метод
// доезжали до роутера и падали уже на запросе.
check('empty method rejected', throws(static fn () => (new RouteGroup)->get('/x', ['Demo\\ItemController', ''])));
check('empty controller rejected', throws(static fn () => (new RouteGroup)->get('/x', ['', 'index'])));

// ── Validation VO ──
$slug = new PatternConstraint('^[a-z][a-z0-9_-]*$', 255);
check('slug matches', $slug->matches('purr_quest') === true);
check('slug rejects leading digit', $slug->matches('1bad') === false);
check('slug phpPattern', $slug->phpPattern() === '/^[a-z][a-z0-9_-]*$/');
check('slug max length', $slug->matches(str_repeat('a', 256)) === false);
$pw = new PasswordConstraint(8, 64);
check('password length ok', $pw->isValidLength('12345678') === true);
check('password too short', $pw->isValidLength('1234') === false);

// ── Identity DTO ──
$actor = new Actor(7, 'a@b.c', 'Alice');
check('actor fields', $actor->id === 7 && $actor->email === 'a@b.c' && $actor->name === 'Alice');

echo "Ran {$count} checks.\n";
if ($failures !== []) {
    echo count($failures)." FAILED.\n";
    exit(1);
}
echo "All passed.\n";
exit(0);
