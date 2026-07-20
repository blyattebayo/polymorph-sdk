<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Data;

use Closure;

/**
 * Типизированный DSL описания схемы сущности — замена нетипизированным
 * вложенным массивам V1 (`['name'=>..., 'fields'=>[['name'=>..,'type'=>'string',...]]]`).
 *
 *     $spec = SchemaBuilder::make('MCP Servers')
 *         ->string('code', fn (FieldBuilder $f) => $f->required()->indexed())
 *         ->enum('transport', ['http', 'stdio'], fn (FieldBuilder $f) => $f->required())
 *         ->int('attempts')
 *         ->bool('is_enabled', fn (FieldBuilder $f) => $f->nullable())
 *         ->json('args', fn (FieldBuilder $f) => $f->nullable())
 *         ->build();
 */
final class SchemaBuilder
{
    /** @var list<FieldBuilder> */
    private array $fields = [];

    private function __construct(private readonly string $name)
    {
    }

    public static function make(string $name): self
    {
        return new self($name);
    }

    /**
     * @param Closure(FieldBuilder): void|null $configure
     */
    public function field(string $name, FieldType $type, ?Closure $configure = null): self
    {
        $builder = new FieldBuilder($name, $type);
        if ($configure !== null) {
            $configure($builder);
        }
        $this->fields[] = $builder;

        return $this;
    }

    /** @param Closure(FieldBuilder): void|null $c */
    public function string(string $name, ?Closure $c = null): self
    {
        return $this->field($name, FieldType::STRING, $c);
    }

    /** @param Closure(FieldBuilder): void|null $c */
    public function text(string $name, ?Closure $c = null): self
    {
        return $this->field($name, FieldType::TEXT, $c);
    }

    /** @param Closure(FieldBuilder): void|null $c */
    public function int(string $name, ?Closure $c = null): self
    {
        return $this->field($name, FieldType::INT, $c);
    }

    /** @param Closure(FieldBuilder): void|null $c */
    public function float(string $name, ?Closure $c = null): self
    {
        return $this->field($name, FieldType::FLOAT, $c);
    }

    /** @param Closure(FieldBuilder): void|null $c */
    public function bool(string $name, ?Closure $c = null): self
    {
        return $this->field($name, FieldType::BOOL, $c);
    }

    /** @param Closure(FieldBuilder): void|null $c */
    public function datetime(string $name, ?Closure $c = null): self
    {
        return $this->field($name, FieldType::DATETIME, $c);
    }

    /** @param Closure(FieldBuilder): void|null $c */
    public function json(string $name, ?Closure $c = null): self
    {
        return $this->field($name, FieldType::JSON, $c);
    }

    /** @param Closure(FieldBuilder): void|null $c */
    public function ref(string $name, ?Closure $c = null): self
    {
        return $this->field($name, FieldType::REF, $c);
    }

    /** @param Closure(FieldBuilder): void|null $c */
    public function media(string $name, ?Closure $c = null): self
    {
        return $this->field($name, FieldType::MEDIA, $c);
    }

    /**
     * Строковое поле с ограничением допустимых значений (rule `in`).
     *
     * @param list<string> $values
     * @param Closure(FieldBuilder): void|null $c
     */
    public function enum(string $name, array $values, ?Closure $c = null): self
    {
        return $this->field($name, FieldType::STRING, function (FieldBuilder $f) use ($values, $c): void {
            $f->rule('in', array_values($values));
            if ($c !== null) {
                $c($f);
            }
        });
    }

    public function build(): SchemaSpec
    {
        if ($this->fields === []) {
            throw new \InvalidArgumentException("Schema '{$this->name}' must declare at least one field.");
        }

        $definitions = [];
        foreach ($this->fields as $index => $builder) {
            $definitions[] = $builder->toDefinition($index);
        }

        return new SchemaSpec($this->name, $definitions);
    }
}
