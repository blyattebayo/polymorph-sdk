<?php

declare(strict_types=1);

/**
 * Автономный smoke-тест слоя данных SDK V2 (Epic 1, часть A): SchemaBuilder DSL +
 * контракт Repository против in-memory фейка (зерно contract-kit).
 * Запуск: php be/sdk-v2/tests/smoke_data.php   (выход 0 = ок, 1 = провал)
 */
spl_autoload_register(static function (string $class): void {
    foreach (['Polymorph\\Sdk\\Tests\\' => __DIR__.'/', 'Polymorph\\Sdk\\' => __DIR__.'/../src/'] as $prefix => $base) {
        if (str_starts_with($class, $prefix)) {
            $path = $base.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
            if (is_file($path)) {
                require $path;
            }

            return;
        }
    }
});

use Polymorph\Sdk\Data\Cardinality;
use Polymorph\Sdk\Data\Entity;
use Polymorph\Sdk\Data\FieldType;
use Polymorph\Sdk\Data\SchemaBuilder;
use Polymorph\Sdk\Tests\Support\InMemoryRepository;
use Polymorph\Sdk\Tests\Support\UniqueViolation;

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

function throws(callable $fn, ?string $class = null): bool
{
    try {
        $fn();

        return false;
    } catch (Throwable $e) {
        return $class === null || $e instanceof $class;
    }
}

// ── SchemaBuilder DSL ──
$spec = SchemaBuilder::make('MCP Servers')
    ->string('code', fn ($f) => $f->required()->indexed()->unique())
    ->enum('transport', ['http', 'stdio'], fn ($f) => $f->required())
    ->int('attempts')
    ->bool('is_enabled', fn ($f) => $f->nullable())
    ->json('args', fn ($f) => $f->nullable())
    ->datetime('created_at')
    ->build();

check('spec name', $spec->name === 'MCP Servers');
check('spec field count', count($spec->fields) === 6);
$byName = [];
foreach ($spec->fields as $f) {
    $byName[$f->name] = $f;
}
check('code type string', $byName['code']->type === FieldType::STRING);
check('code required+indexed+unique', $byName['code']->isRequired() && $byName['code']->indexed && $byName['code']->unique);
check('transport enum rule', ($byName['transport']->rules['in'] ?? null) === ['http', 'stdio']);
check('attempts type int', $byName['attempts']->type === FieldType::INT);
check('attempts default cardinality one', $byName['attempts']->cardinality === Cardinality::ONE);
check('sort order preserved', $byName['code']->sortOrder === 0 && $byName['created_at']->sortOrder === 5);
check('empty schema rejected', throws(static fn () => SchemaBuilder::make('X')->build()));

// ── Repository contract (in-memory fake) ──
function freshRepo($spec): InMemoryRepository
{
    $repo = new InMemoryRepository($spec);
    $repo->actingAs(10)->create(['code' => 'alpha', 'transport' => 'http', 'attempts' => 5, 'is_enabled' => true, 'created_at' => '2026-06-27T10:00:00Z']);
    $repo->actingAs(20)->create(['code' => 'beta', 'transport' => 'stdio', 'attempts' => 2, 'is_enabled' => false]);
    $repo->actingAs(10)->create(['code' => 'gamma', 'transport' => 'http', 'attempts' => 9, 'is_enabled' => true]);

    return $repo;
}

$repo = freshRepo($spec);

// create / required / unique
check('create assigns id+revision', $repo->find(1)?->id === 1 && $repo->find(1)?->revision === 1);
check('create missing required throws', throws(static fn () => (new InMemoryRepository($spec))->create(['transport' => 'http']), InvalidArgumentException::class));
check('duplicate unique throws', throws(static fn () => $repo->create(['code' => 'alpha', 'transport' => 'http']), UniqueViolation::class));

// update merges + bumps revision
$updated = $repo->update(2, ['attempts' => 7]);
check('update merges', $updated->int('attempts') === 7 && $updated->string('code') === 'beta');
check('update bumps revision', $updated->revision === 2);

// query
check('where eq count', $repo->query()->where('transport', 'http')->count() === 2);
check('whereIn count', $repo->query()->whereIn('transport', ['stdio'])->count() === 1);
// beta был обновлён до attempts=7 выше → alpha(5), beta(7), gamma(9)
check('where gte count', $repo->query()->where('attempts', '>=', 5)->count() === 3);
check('orderByDesc first', $repo->query()->orderByDesc('attempts')->first()?->string('code') === 'gamma');
check('whereAuthor filter', $repo->query()->whereAuthor(10)->count() === 2);
check('limit', count($repo->query()->limit(1)->get()) === 1);
check('exists true', $repo->query()->where('code', 'beta')->exists() === true);
check('exists false', $repo->query()->where('code', 'zzz')->exists() === false);
check('first by code', $repo->query()->where('code', 'alpha')->first()?->id === 1);

// aggregates (note: beta updated to 7 -> 5+7+9 = 21)
check('sum attempts', $repo->query()->sum('attempts') === 21.0);
check('avg attempts', abs(($repo->query()->avg('attempts') ?? 0) - 7.0) < 1e-9);
check('aggregate non-numeric throws', throws(static fn () => $repo->query()->sum('code'), InvalidArgumentException::class));

// pagination
$p1 = $repo->query()->paginate(1, 2);
check('paginate page1 items', count($p1->items) === 2);
check('paginate total+hasMore', $p1->pagination->total === 3 && $p1->pagination->hasMorePages() === true);
check('paginate page2 last', $repo->query()->paginate(2, 2)->pagination->hasMorePages() === false);

// increment
$inc = $repo->increment(1, 'attempts', 3);
check('increment numeric', $inc->int('attempts') === 8 && $inc->revision === 2);
check('increment non-numeric throws', throws(static fn () => $repo->increment(1, 'is_enabled', 1), InvalidArgumentException::class));

// firstOrCreate / upsert
$repo2 = freshRepo($spec);
check('firstOrCreate returns existing', $repo2->firstOrCreate(['code' => 'alpha'], ['attempts' => 999])->id === 1);
check('firstOrCreate existing not mutated', $repo2->find(1)?->int('attempts') === 5);
$created = $repo2->firstOrCreate(['code' => 'delta'], ['transport' => 'http']);
check('firstOrCreate creates new', $created->string('code') === 'delta' && $created->id === 4);
$ups = $repo2->upsert(['code' => 'beta'], ['attempts' => 100]);
check('upsert updates existing', $ups->id === 2 && $ups->int('attempts') === 100);
check('upsert creates new', $repo2->upsert(['code' => 'epsilon'], ['transport' => 'stdio'])->string('code') === 'epsilon');

// delete
$repo2->delete(2);
check('delete removes', $repo2->find(2) === null);

// Entity typed accessors
$e = new Entity(1, ['n' => '42', 'flag' => 1, 'list' => ['a'], 'when' => '2026-01-01T00:00:00Z'], 1, 7);
check('entity int', $e->int('n') === 42);
check('entity bool', $e->bool('flag') === true);
check('entity array', $e->array('list') === ['a']);
check('entity datetime', $e->datetime('when') === '2026-01-01T00:00:00Z');
check('entity datetime missing null', $e->datetime('missing') === null);
check('entity authorId', $e->authorId === 7);

echo "Ran {$count} checks.\n";
if ($failures !== []) {
    echo count($failures)." FAILED.\n";
    exit(1);
}
echo "All passed.\n";
exit(0);
