<?php

namespace App\Platform\Shared\Exceptions;

use App\Platform\Shared\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

/**
 * Base class for every domain exception. Carries a stable machine-readable error code,
 * an HTTP status, and optional structured details, and renders itself as the ONE standard
 * error envelope (via ApiResponse::error). Domains extend this — they never hand-roll
 * error responses.
 *
 * Contains no business logic; it is the shared error-shaping contract only.
 */
abstract class BaseDomainException extends RuntimeException
{
    /** Machine-readable, stable error code (e.g. RESOURCE_NOT_FOUND). */
    protected string $errorCode = 'DOMAIN_ERROR';

    /** HTTP status to emit. */
    protected int $status = 400;

    /** @var array<string, mixed> */
    protected array $details = [];

    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(string $message = '', array $details = [], ?Throwable $previous = null)
    {
        parent::__construct($message !== '' ? $message : static::class, 0, $previous);
        $this->details = $details;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function status(): int
    {
        return $this->status;
    }

    /** @return array<string, mixed> */
    public function details(): array
    {
        return $this->details;
    }

    /** Laravel calls this to render the exception into a response. */
    public function render(Request $request): JsonResponse
    {
        return ApiResponse::error($this->errorCode, $this->getMessage(), $this->details, $this->status);
    }
}
