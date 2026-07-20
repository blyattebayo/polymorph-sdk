<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Logging;

/**
 * Структурное логирование событий расширения. Реализация — хост.
 */
interface Logger
{
    /**
     * @param array<string, mixed> $context
     */
    public function info(string $event, array $context = []): void;

    /**
     * @param array<string, mixed> $context
     */
    public function warning(string $event, array $context = []): void;

    /**
     * @param array<string, mixed> $context
     */
    public function error(string $event, array $context = []): void;

    /**
     * Неизменяемое расширение контекста.
     *
     * @param array<string, mixed> $context
     */
    public function withContext(array $context): self;
}
