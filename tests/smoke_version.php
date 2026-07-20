<?php

declare(strict_types=1);

/**
 * Version single-source guard: поле "version" в composer.json пакетов SDK должно
 * совпадать с Polymorph\Sdk\Version\Sdk::VERSION (единый источник версии контракта).
 * Рассинхрон при публикации = тихая поломка совместимости, поэтому это кандидат
 * в CI-гейт релиза SDK (ADR 0005, Фаза 5).
 *
 * Запуск: php be/sdk-v2/tests/smoke_version.php   (выход 0 = ок, 1 = провал)
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
$sdkComposer = $composerVersion('sdk-v2/composer.json');
$laravelComposer = $composerVersion('sdk-laravel/composer.json');

$check("Sdk::VERSION present ({$sdkVersion})", $sdkVersion !== '');
$check('sdk-v2/composer.json version === Sdk::VERSION (got: '.($sdkComposer ?? 'null').')', $sdkComposer === $sdkVersion);
$check('sdk-laravel/composer.json version === Sdk::VERSION (got: '.($laravelComposer ?? 'null').')', $laravelComposer === $sdkVersion);

echo PHP_EOL;
if ($failures === 0) {
    echo "Ran 3 checks.\nAll passed.\n";
    exit(0);
}
echo "{$failures} check(s) FAILED.\n";
exit(1);
