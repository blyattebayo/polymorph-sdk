<?php

declare(strict_types=1);

/**
 * Автономный smoke-тест поверхности Epic 2: routing builder, Capability,
 * validation VO, identity DTO. Запуск: php be/sdk-v2/tests/smoke_surface.php
 */

spl_autoload_register(static function (string $class): void {
    $prefix = 'Polymorph\\Sdk\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $path = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
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
use Polymorph\Sdk\Validation\PasswordConstraint;
use Polymorph\Sdk\Validation\PatternConstraint;

$failures = [];
$count = 0;

function check(string $name, bool $ok): void
{
    global $failures, $count;
    $count++;
    if (!$ok) {
        $failures[] = $name;
        fwrite(STDERR, "FAIL: {$name}\n");
    }
}

function throws(callable $fn): bool
{
    try {
        $fn();
        return false;
    } catch (\Throwable) {
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
check('without csrf', Middleware::withoutCsrf() === 'without:csrf');

// ── Routes builder ──
$ctx = ExtensionContext::for('demo');
$compiled = Routes::for($ctx)
    ->api(function (RouteGroup $r) use ($ctx): void {
        $r->get('/items', ['Demo\\ItemController', 'index'])
            ->name('items.index')
            ->requires(Capability::of($ctx->resource('items'), CapabilityAction::READ));
        $r->post('/items', ['Demo\\ItemController', 'store']);
    })
    ->adminApi(function (RouteGroup $r): void {
        $r->delete('/items/{item}', ['Demo\\ItemController', 'destroy'])->withoutCsrf();
    }, requires: Capability::of('ext.demo.manage'))
    ->toArray();

check('two groups', count($compiled) === 2);

$api = $compiled[0];
check('api group kind', $api['kind'] === 'group');
check('api group prefix', $api['prefix'] === 'api/v1/ext/demo');
check('api base middleware', $api['middleware'] === ['api']);
check('api two routes', count($api['children']) === 2);

$itemsIndex = $api['children'][0];
check('route kind', $itemsIndex['kind'] === 'route');
check('route uri', $itemsIndex['uri'] === '/items');
check('route methods', $itemsIndex['methods'] === ['GET']);
check('route action', $itemsIndex['action_meta']['action'] === 'Demo\\ItemController@index');
check('route name with prefix', $itemsIndex['name'] === 'api.v1.ext.demo.items.index');
check('route capability middleware', $itemsIndex['middleware'] === ['capability.require:ext.demo.items,read']);

$itemsStore = $api['children'][1];
check('default route name', $itemsStore['name'] === 'api.v1.ext.demo.items.post');
check('no middleware key when empty', !array_key_exists('middleware', $itemsStore));

$admin = $compiled[1];
check('admin prefix', $admin['prefix'] === 'api/v1/admin/ext/demo');
check('admin base middleware + capability', $admin['middleware'] === ['api', 'auth:api', 'capability.require:ext.demo.manage,access']);
$destroy = $admin['children'][0];
check('admin route name param stripped', $destroy['name'] === 'admin.v1.ext.demo.items.item.delete');
check('admin route withoutCsrf', $destroy['middleware'] === ['without:csrf']);

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
    echo count($failures) . " FAILED.\n";
    exit(1);
}
echo "All passed.\n";
exit(0);
