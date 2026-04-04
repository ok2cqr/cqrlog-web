<?php

declare(strict_types=1);

namespace App\Api\Exception;

use Symfony\Component\HttpFoundation\Response;

final class UpstreamLookupException extends ApiException
{
    public function __construct(string $serviceName)
    {
        parent::__construct(
            Response::HTTP_BAD_GATEWAY,
            'upstream_error',
            sprintf('The %s lookup is currently unavailable.', $serviceName),
        );
    }
}
