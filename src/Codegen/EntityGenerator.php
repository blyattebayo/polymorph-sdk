<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Codegen;

use Polymorph\Sdk\Data\Cardinality;
use Polymorph\Sdk\Data\Entity;
use Polymorph\Sdk\Data\FieldDefinition;
use Polymorph\Sdk\Data\FieldType;
use Polymorph\Sdk\Data\SchemaSpec;

/**
 * EXPERIMENTAL: не подключён в сборку плагинов.
 *
 * Генерирует типизированный класс-сущность из {@see SchemaSpec}: подкласс
 * {@see Entity} с типизированными аксессорами на каждое поле.
 *
 * Это убирает `array<string,mixed>` из кода расширения: вместо
 * `$record->get('attempts')` (mixed) — `$server->attempts()` (int).
 *
 *     $code = (new EntityGenerator())->generate('App\\Ext\\Mcp', 'Server', $spec);
 *     // → final class Server extends Entity { public function code(): string {...} ... }
 *
 * Назначение codegen — ШАРИНГ ТИПОВ BE↔FE; это требует отдельного FE-codegen.
 * BE-only codegen избыточен с принятым в плагинах паттерном «богатый
 * DTO + fromRecord(Entity)» (purr_quest так и делает). Поэтому wiring отложен до
 * Epic 4; класс держим как готовый строительный блок, но НЕ выдаём за «сделано».
 *
 * @internal Не входит в публичный semver-контракт SDK до Epic 4. Не использовать
 *           во внешних расширениях — сигнатура может измениться без мажора.
 */
final class EntityGenerator
{
    public function generate(string $namespace, string $className, SchemaSpec $spec): string
    {
        $methods = [];
        foreach ($spec->fields as $field) {
            $methods[] = $this->accessor($field);
        }
        $body = implode("\n\n", $methods);

        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace {$namespace};

            use Polymorph\\Sdk\\Data\\Entity;

            /**
             * АВТОГЕНЕРАЦИЯ из SchemaSpec '{$spec->name}'. Не редактировать вручную —
             * перегенерируется из определения сущности.
             */
            final class {$className} extends Entity
            {
            {$body}
            }

            PHP;
    }

    private function accessor(FieldDefinition $field): string
    {
        $method = $this->methodName($field->name);
        $returnType = $this->returnType($field);
        $call = $this->accessorCall($field);

        return <<<PHP
                public function {$method}(): {$returnType}
                {
                    return {$call};
                }
            PHP;
    }

    private function returnType(FieldDefinition $field): string
    {
        if ($field->cardinality === Cardinality::MANY || $field->type === FieldType::JSON) {
            return 'array';
        }

        return match ($field->type) {
            FieldType::STRING, FieldType::TEXT, FieldType::MEDIA => 'string',
            FieldType::INT, FieldType::REF => 'int',
            FieldType::FLOAT => 'float',
            FieldType::BOOL => 'bool',
            FieldType::DATETIME => '?string',
            FieldType::JSON => 'array',
        };
    }

    private function accessorCall(FieldDefinition $field): string
    {
        $name = $field->name;

        if ($field->cardinality === Cardinality::MANY || $field->type === FieldType::JSON) {
            return "\$this->array('{$name}')";
        }

        return match ($field->type) {
            FieldType::STRING, FieldType::TEXT, FieldType::MEDIA => "\$this->string('{$name}')",
            FieldType::INT, FieldType::REF => "\$this->int('{$name}')",
            FieldType::FLOAT => "\$this->float('{$name}')",
            FieldType::BOOL => "\$this->bool('{$name}')",
            FieldType::DATETIME => "\$this->datetime('{$name}')",
            FieldType::JSON => "\$this->array('{$name}')",
        };
    }

    /** snake/kebab-case имя поля → camelCase имя метода. */
    private function methodName(string $field): string
    {
        $parts = preg_split('/[_-]+/', trim($field)) ?: [];
        $parts = array_values(array_filter($parts, static fn (string $p): bool => $p !== ''));

        if ($parts === []) {
            return 'value';
        }

        $name = strtolower($parts[0]);
        foreach (array_slice($parts, 1) as $part) {
            $name .= ucfirst(strtolower($part));
        }

        return $name;
    }
}
