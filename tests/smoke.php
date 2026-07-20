<?php

declare(strict_types=1);

/**
 * Автономный smoke-тест каркаса SDK V2 (Epic 0). Не требует composer install:
 * регистрирует минимальный PSR-4 автолоадер и проверяет поведение примитивов.
 * Запуск: php be/sdk-v2/tests/smoke.php   (выход 0 = ок, 1 = провал)
 *
 * Pest/PHPUnit-обвязка добавится позже; здесь — быстрый guard на этапе каркаса.
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'Polymorph\\Sdk\\';
    if (! str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = __DIR__.'/../src/'.str_replace('\\', '/', $relative).'.php';
    if (is_file($path)) {
        require $path;
    }
});

use Polymorph\Sdk\Errors\ErrorCode;
use Polymorph\Sdk\Errors\ExtensionError;
use Polymorph\Sdk\Extension\ExtensionContext;
use Polymorph\Sdk\Extension\ExtensionId;
use Polymorph\Sdk\Http\Pagination;
use Polymorph\Sdk\Http\Reply;
use Polymorph\Sdk\Version\Compatibility;
use Polymorph\Sdk\Version\Sdk;
use Polymorph\Sdk\Version\SdkVersion;

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

// ── ExtensionId ──
check('id accepts slug with underscore', ExtensionId::fromString('purr_quest')->value === 'purr_quest');
check('id accepts slug with dash', ExtensionId::fromString('context-router')->value === 'context-router');
check('id trims', ExtensionId::fromString('  demo ')->value === 'demo');
check('id rejects empty', throws(static fn () => ExtensionId::fromString('')));
check('id rejects leading digit', throws(static fn () => ExtensionId::fromString('1bad')));
check('id rejects dot', throws(static fn () => ExtensionId::fromString('a.b')));
check('id equals', ExtensionId::fromString('a')->equals(ExtensionId::fromString('a')));

// ── ExtensionContext ──
$ctx = ExtensionContext::for('context-router');
check('ctx prefix', $ctx->resourcePrefix() === 'ext.context-router.');
check('ctx resource segments', $ctx->resource('servers', '42') === 'ext.context-router.servers.42');
check('ctx resource no segments', $ctx->resource() === 'ext.context-router');

// ── ErrorCode ──
check('code http 404', ErrorCode::NOT_FOUND->httpStatus() === 404);
check('code http 409', ErrorCode::CONFLICT->httpStatus() === 409);
check('code http 422', ErrorCode::VALIDATION_ERROR->httpStatus() === 422);
check('code http 429', ErrorCode::TOO_MANY_REQUESTS->httpStatus() === 429);

// ── ExtensionError ──
$nf = ExtensionError::notFound('Server not found', ['id' => 7]);
check('error status', $nf->httpStatus() === 404);
check('error problem code', $nf->toProblem()['code'] === 'NOT_FOUND');
check('error problem detail', $nf->toProblem()['detail'] === 'Server not found');
check('error problem meta', $nf->toProblem()['meta'] === ['id' => 7]);
$val = ExtensionError::validation('Invalid', ['name' => ['required']]);
check('validation meta errors', $val->toProblem()['meta'] === ['errors' => ['name' => ['required']]]);
check('error is throwable', throws(static fn () => throw ExtensionError::conflict('x')));

// ── Reply ──
$ok = Reply::ok(['x' => 1]);
check('reply ok status', $ok->status === 200);
check('reply ok envelope', $ok->body === ['data' => ['x' => 1]]);
check('reply created status', Reply::created(['a'])->status === 201);
check('reply noContent', Reply::noContent()->status === 204 && Reply::noContent()->body === null);
$page = Reply::page([1, 2], new Pagination(1, 2, 5));
check('reply page data', $page->body['data'] === [1, 2]);
check('reply page meta', $page->body['meta']['pagination'] === ['page' => 1, 'per_page' => 2, 'total' => 5, 'has_more' => true]);
check('reply raw', Reply::raw('hi', 418)->status === 418 && Reply::raw('hi', 418)->body === 'hi');
$withHeader = Reply::ok([])->withHeader('X-Test', 'v');
check('reply immutable header', $withHeader->headers === ['X-Test' => 'v'] && Reply::ok([])->headers === []);

// ── Pagination ──
check('pagination has more', (new Pagination(1, 10, 25))->hasMorePages() === true);
check('pagination last page', (new Pagination(3, 10, 25))->hasMorePages() === false);

// ── Version / Compatibility ──
$v = SdkVersion::fromString('2.3.1');
check('version parse', $v->major === 2 && $v->minor === 3 && $v->patch === 1);
check('version toString', (string) SdkVersion::fromString('2.0.0') === '2.0.0');
check('version rejects junk', throws(static fn () => SdkVersion::fromString('nope')));
$host = new SdkVersion(2, 3, 0);
check('compat same major older minor required', Compatibility::hostSupports($host, new SdkVersion(2, 1, 0)) === true);
check('compat required newer minor than host', Compatibility::hostSupports($host, new SdkVersion(2, 5, 0)) === false);
check('compat required newer major', Compatibility::hostSupports($host, new SdkVersion(3, 0, 0)) === false);
check('compat previous major within window', Compatibility::hostSupports($host, new SdkVersion(1, 9, 0)) === true);
check('compat too old major', Compatibility::hostSupports($host, new SdkVersion(0, 9, 0)) === false);
check('compat facade strings', Compatibility::check('2.3.0', '2.0.0') === true);
check('range caret major ok', Compatibility::satisfiesRange('2.3.0', '^2') === true);
check('range caret minor too new', Compatibility::satisfiesRange('2.3.0', '^2.5') === false);
check('range previous major in window', Compatibility::satisfiesRange('2.3.0', '^1') === true);
check('range future major', Compatibility::satisfiesRange('2.3.0', '^3') === false);
check('range exact', Compatibility::satisfiesRange('2.3.0', '2.1.0') === true);
check('range junk rejected', throws(static fn () => Compatibility::satisfiesRange('2.3.0', 'nope')));
check('sdk version const', Sdk::VERSION === '2.0.0' && (string) Sdk::version() === '2.0.0');

// ── Result ──
echo "Ran {$count} checks.\n";
if ($failures !== []) {
    echo count($failures)." FAILED.\n";
    exit(1);
}
echo "All passed.\n";
exit(0);
