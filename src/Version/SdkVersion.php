<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Version;

use Stringable;

/**
 * Semver-версия SDK (major.minor.patch). Pre-release/build-метаданные
 * игнорируются для целей совместимости.
 */
final class SdkVersion implements Stringable
{
    public function __construct(
        public readonly int $major,
        public readonly int $minor,
        public readonly int $patch,
    ) {}

    public static function fromString(string $version): self
    {
        if (preg_match('/^(\d+)\.(\d+)\.(\d+)/', trim($version), $m) !== 1) {
            throw new \InvalidArgumentException("Invalid semver string '{$version}'.");
        }

        return new self((int) $m[1], (int) $m[2], (int) $m[3]);
    }

    public function __toString(): string
    {
        return "{$this->major}.{$this->minor}.{$this->patch}";
    }
}
