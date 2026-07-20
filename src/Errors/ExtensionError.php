<?php

declare(strict_types=1);

namespace Polymorph\Sdk\Errors;

use RuntimeException;
use Throwable;

/**
 * Доменная ошибка расширения. Бросается расширением напрямую (без инъекции
 * хоста), хост ловит и рендерит в ProblemJson-ответ ядра по {@see toProblem()}.
 *
 *     throw ExtensionError::notFound('Server not found', ['id' => $id]);
 *
 * Это устраняет протечку Symfony-исключений и service-locator из V1.
 */
final class ExtensionError extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $meta
     */
    private function __construct(
        public readonly ErrorCode $errorCode,
        public readonly ?string $detail = null,
        public readonly array $meta = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($detail ?? $errorCode->value, 0, $previous);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function of(ErrorCode $code, ?string $detail = null, array $meta = [], ?Throwable $previous = null): self
    {
        return new self($code, $detail, $meta, $previous);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    /**
     * Некорректный запрос (400) — для случаев, не сводящихся к field-validation
     * (несовместимое состояние ресурса, неприменимый флоу и т.п.).
     *
     * @param  array<string, mixed>  $meta
     */
    public static function badRequest(?string $detail = null, array $meta = []): self
    {
        return new self(ErrorCode::BAD_REQUEST, $detail, $meta);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function notFound(?string $detail = null, array $meta = []): self
    {
        return new self(ErrorCode::NOT_FOUND, $detail, $meta);
    }

    /**
     * @param  array<string, list<string>>  $errors  поле => список сообщений
     */
    public static function validation(string $detail, array $errors = []): self
    {
        return new self(ErrorCode::VALIDATION_ERROR, $detail, $errors === [] ? [] : ['errors' => $errors]);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function unauthorized(?string $detail = null, array $meta = []): self
    {
        return new self(ErrorCode::UNAUTHORIZED, $detail, $meta);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function forbidden(?string $detail = null, array $meta = []): self
    {
        return new self(ErrorCode::FORBIDDEN, $detail, $meta);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function conflict(?string $detail = null, array $meta = []): self
    {
        return new self(ErrorCode::CONFLICT, $detail, $meta);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function tooManyRequests(?string $detail = null, array $meta = []): self
    {
        return new self(ErrorCode::TOO_MANY_REQUESTS, $detail, $meta);
    }

    /**
     * Сервис временно недоступен (503) — напр. сбой/недоступность апстрима, на
     * который проксирует расширение. $previous сохраняет первопричину для логов.
     *
     * @param  array<string, mixed>  $meta
     */
    public static function serviceUnavailable(?string $detail = null, array $meta = [], ?Throwable $previous = null): self
    {
        return new self(ErrorCode::SERVICE_UNAVAILABLE, $detail, $meta, $previous);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function internal(string $detail, array $meta = [], ?Throwable $previous = null): self
    {
        return new self(ErrorCode::INTERNAL_ERROR, $detail, $meta, $previous);
    }

    public function httpStatus(): int
    {
        return $this->errorCode->httpStatus();
    }

    /**
     * Нейтральное представление для рендеринга хостом (ProblemJson-совместимое).
     *
     * @return array{code: string, detail: string|null, meta: array<string, mixed>}
     */
    public function toProblem(): array
    {
        return [
            'code' => $this->errorCode->value,
            'detail' => $this->detail,
            'meta' => $this->meta,
        ];
    }
}
