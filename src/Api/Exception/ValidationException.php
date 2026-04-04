<?php

declare(strict_types=1);

namespace App\Api\Exception;

use Symfony\Component\HttpFoundation\Response;

final class ValidationException extends ApiException
{
    /**
     * @param array<string, list<string>> $fieldErrors
     */
    public function __construct(array $fieldErrors)
    {
        parent::__construct(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'validation_failed',
            'Request validation failed.',
            [
                'fields' => $fieldErrors,
            ],
        );
    }
}
