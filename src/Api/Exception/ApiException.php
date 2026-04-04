<?php

declare(strict_types=1);

namespace App\Api\Exception;

use RuntimeException;
use Throwable;

class ApiException extends RuntimeException
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        private readonly int $statusCode,
        private readonly string $errorCode,
        string $message,
        private readonly array $details = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetails(): array
    {
        return $this->details;
    }
}
