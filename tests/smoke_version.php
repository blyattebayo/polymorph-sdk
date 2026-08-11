<?php

declare(strict_types=1);

/**
 * Version single-source guard.
 *
 * Версия пакета берётся из git-ТЕГА в сплит-репозитории, а не из поля "version"
 * в composer.json (Composer прямо не рекомендует его для пакетов из VCS: оно
 * перекрывает версию тега и расходится с ней молча). Поэтому проверяем то, что
 * действительно может разъехаться:
 *
 *  - Sdk::VERSION задан и валиден;
 *  - поля "version" в composer.json НЕТ — иначе оно перекроет тег;
 *  - если передан тег (аргументом или CI_COMMIT_TAG / GITHUB_REF_NAME) — он
 *    совпадает с Sdk::VERSION. Рассинхрон здесь = хост объявит одну версию
 *    контракта, а Packagist отдаст другую, и окно совместимости расширений
 *    поедет незаметно.
 *
 * Запуск: php be/sdk-v2/tests/smoke_version.php [v4.0.0]   (0 = ок, 1 = провал)
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

use Polymorph\Sdk\Version\Sdk;

$failures = 0;
$check = static function (string $label, bool $ok) use (&$failures): void {
    echo ($ok ? '  ok  ' : ' FAIL ').$label.PHP_EOL;
    if (! $ok) {
        $failures++;
    }
};

/** Читает поле "version" из composer.json по пути относительно be/. */
$composerVersion = static function (string $relFromBe): ?string {
    $path = __DIR__.'/../../'.$relFromBe;
    if (! is_file($path)) {
        return null;
    }
    $data = json_decode((string) file_get_contents($path), true);

    return is_array($data) && isset($data['version']) ? (string) $data['version'] : null;
};

$sdkVersion = Sdk::VERSION;

$check(
    "Sdk::VERSION валиден ({$sdkVersion})",
    preg_match('/^\d+\.\d+\.\d+$/', $sdkVersion) === 1,
);

foreach (['sdk-v2', 'sdk-laravel', 'sdk-testing', 'platform'] as $package) {
    $pinned = $composerVersion("{$package}/composer.json");
    $check(
        "{$package}/composer.json не пиннит version (версия приходит из тега)".
            ($pinned === null ? '' : " — найдено '{$pinned}'"),
        $pinned === null,
    );
}

$tag = $argv[1] ?? getenv('CI_COMMIT_TAG') ?: getenv('GITHUB_REF_NAME') ?: null;

if (is_string($tag) && $tag !== '') {
    $normalized = ltrim($tag, 'v');
    $check("тег {$tag} === Sdk::VERSION {$sdkVersion}", $normalized === $sdkVersion);
} else {
    echo '  --  тег не передан — сверка с тегом пропущена'.PHP_EOL;
}

echo PHP_EOL;
if ($failures === 0) {
    echo "All passed.\n";
    exit(0);
}
echo "{$failures} check(s) FAILED.\n";
exit(1);
