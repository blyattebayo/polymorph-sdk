<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Data;

/** The one field-name validator shared by SDK and host control-plane boundaries. */
final readonly class FieldName
{
    private const PATTERN = '/^[a-z][a-z0-9_-]{0,254}$/D';

    private function __construct(public string $value) {}

    public static function from(string $value): self
    {
        $value = trim($value);
        if ($value === '' || str_contains($value, '.') || preg_match(self::PATTERN, $value) !== 1) {
            throw new \InvalidArgumentException(
                "Field name '{$value}' must be one local a-z segment of at most 255 characters without path syntax.",
            );
        }
        if ($value === '_item_id') {
            throw new \InvalidArgumentException("Field name '_item_id' is reserved for repeated object identity.");
        }

        return new self($value);
    }
}
