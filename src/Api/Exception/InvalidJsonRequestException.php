<?php

declare(strict_types=1);

namespace App\Api\Exception;

use JsonException;
use Symfony\Component\HttpFoundation\Response;

final class InvalidJsonRequestException extends ApiException
{
    public static function becauseBodyIsNotObject(): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            'invalid_json',
            'Request body must contain a JSON object.',
        );
    }

    public static function fromJsonException(JsonException $exception): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            'invalid_json',
            'Request body contains invalid JSON.',
            [],
            $exception,
        );
    }
}
