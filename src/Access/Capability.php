<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Access;

use Polymorph\Sdk\Extension\ExtensionContext;

/**
 * Типизированная пара (resource, action) — ЕДИНЫЙ способ требовать право в V2.
 *
 * Устраняет тройственность V1 (route->requires() vs group requires: vs ручной
 * middleware): и роуты, и группы требуют именно Capability.
 *
 * Ресурс расширения обычно строится из {@see ExtensionContext::resource()}
 * → 'ext.{id}.…'.
 */
final class Capability
{
    public function __construct(
        public readonly string $resource,
        public readonly string $action = CapabilityAction::ACCESS,
    ) {
        if (trim($resource) === '' || trim($action) === '') {
            throw new \InvalidArgumentException('Capability resource and action must not be empty.');
        }
    }

    public static function of(string $resource, string $action = CapabilityAction::ACCESS): self
    {
        return new self(trim($resource), trim($action));
    }
}
