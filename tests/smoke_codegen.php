<?php

declare(strict_types=1);

/**
 * Автономный smoke-тест codegen (Epic 1): SchemaSpec → типизированный класс-сущность,
 * затем гидрация через Repository. Запуск: php be/sdk-v2/tests/smoke_codegen.php
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

use Polymorph\Sdk\Codegen\EntityGenerator;
use Polymorph\Sdk\Data\SchemaBuilder;
use Polymorph\Sdk\Tests\Support\InMemoryRepository;

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

$spec = SchemaBuilder::make('MCP Servers')
    ->string('code', fn ($f) => $f->required()->indexed()->unique())
    ->enum('transport', ['http', 'stdio'], fn ($f) => $f->required())
    ->int('attempts')
    ->bool('is_enabled', fn ($f) => $f->nullable())
    ->rawJson('args', fn ($f) => $f->nullable())
    ->datetime('created_at')
    ->build();

$ns = 'Polymorph\\Sdk\\Tests\\Generated';
$code = (new EntityGenerator)->generate($ns, 'Server', $spec);

// ── Сгенерированный исходник ──
check('extends Entity', str_contains($code, 'final class Server extends Entity'));
check('string accessor', str_contains($code, 'public function code(): string'));
check('int accessor', str_contains($code, 'public function attempts(): int'));
check('bool camelCase accessor', str_contains($code, 'public function isEnabled(): bool'));
check('raw json mixed accessor', str_contains($code, 'public function args(): mixed'));
check('datetime nullable accessor', str_contains($code, 'public function createdAt(): ?string'));
check('enum field is string', str_contains($code, 'public function transport(): string'));
check('no leftover snake method', ! str_contains($code, 'is_enabled('));

// ── Компиляция + гидрация ──
$tmp = tempnam(sys_get_temp_dir(), 'sdk_gen_').'.php';
file_put_contents($tmp, $code);
check('generated lints', shell_exec('php -l '.escapeshellarg($tmp).' 2>&1') !== null && str_contains((string) shell_exec('php -l '.escapeshellarg($tmp)), 'No syntax errors'));
require $tmp;

$serverClass = $ns.'\\Server';
check('class loaded', class_exists($serverClass));

$repo = new InMemoryRepository($spec, $serverClass);
$repo->actingAs(10)->create([
    'code' => 'alpha',
    'transport' => 'http',
    'attempts' => 5,
    'is_enabled' => true,
    'args' => ['--verbose'],
    'created_at' => '2026-06-27T10:00:00Z',
]);

$server = $repo->find(1);
check('repo hydrates generated class', is_a($server, $serverClass));
check('typed code()', $server->code() === 'alpha' && gettype($server->code()) === 'string');
check('typed attempts()', $server->attempts() === 5 && gettype($server->attempts()) === 'integer');
check('typed isEnabled()', $server->isEnabled() === true && gettype($server->isEnabled()) === 'boolean');
check('typed args()', $server->args() === ['--verbose']);
check('typed createdAt()', $server->createdAt() === '2026-06-27T10:00:00Z');
check('query returns generated class', is_a($repo->query()->where('code', 'alpha')->first(), $serverClass));

@unlink($tmp);

echo "Ran {$count} checks.\n";
if ($failures !== []) {
    echo count($failures)." FAILED.\n";
    exit(1);
}
echo "All passed.\n";
exit(0);
