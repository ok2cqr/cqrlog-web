<?php

declare(strict_types=1);

namespace App\Api\Exception;

use Symfony\Component\HttpFoundation\Response;

final class ResourceNotFoundException extends ApiException
{
    public function __construct(string $resource, int $id)
    {
        parent::__construct(
            Response::HTTP_NOT_FOUND,
            'not_found',
            sprintf('%s with id %d was not found.', $resource, $id),
            [
                'resource' => $resource,
                'id' => $id,
            ],
        );
    }
}
