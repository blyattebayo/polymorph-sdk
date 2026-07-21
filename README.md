# blyattebayo/polymorph-sdk (Extension SDK V2)

Backend-SDK расширений Polymorph. **Единственная разрешённая поверхность** между
кодом расширения и ядром (`be/app`). Прямые импорты `App\*`, `Illuminate\*`,
`Symfony\*` в коде расширения запрещены (проверяется линтером пакета и contract-guard
тестом ядра). См. архитектурное решение: [docs/adr/0002-extension-sdk-v2.md](../../docs/adr/0002-extension-sdk-v2.md).

> Статус: BE Epics 0–3 в основном построены (примитивы, слой данных, identity/ACL/
> routing, lifecycle) — 44 класса; FE SDK V2 (Epic 4) ещё не начат. Актуальный статус
> и план упаковки — ADR 0003/0004/0005.

## Ключевые примитивы (выборка; полный обзор — `src/` и ADR 0002)

| Раздел | Что даёт |
|---|---|
| `Extension\ExtensionId` | типизированный id расширения (структурный slug-guard) |
| `Extension\ExtensionContext` | явный скоуп: `resourcePrefix()` / `resource(...)` → `ext.{id}.…` |
| `Http\Reply` | нейтральный иммутабельный ответ (`ok`/`created`/`noContent`/`page`/`raw`), рендерит хост |
| `Http\Pagination` | метаданные пагинации для `Reply::page()` |
| `Errors\ErrorCode` | каталог ошибок (зеркало ядра) + HTTP-статусы |
| `Errors\ExtensionError` | доменная ошибка, бросается без хоста; `toProblem()` для рендера |
| `Version\SdkVersion` / `Version\Compatibility` | semver + переговоры совместимости (окно мажоров) |

## Принципы

1. Скоуп явный (`ExtensionContext`), не ambient. 2. Граница непроницаема (нет
`App\*`/`Illuminate\*`/`Symfony\*` в поверхности; нет статических фасадов/`app()`).
3. Типобезопасность вместо `array<string,mixed>`. 4. Semver-контракт с окном
совместимости (хост держит последний контракт + N−1 мажор). 5. Один способ для
каждой задачи.

## Тесты

Каркасный smoke-тест без зависимостей:

```bash
php be/sdk-v2/tests/smoke.php
```

Pest/PHPUnit-обвязка и contract-kit — следующие шаги по плану.
