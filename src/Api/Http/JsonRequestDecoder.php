<?php

declare(strict_types=1);

namespace App\Api\Http;

use App\Api\Exception\InvalidJsonRequestException;
use JsonException;
use Symfony\Component\HttpFoundation\Request;

final class JsonRequestDecoder
{
    /**
     * @return array<string, mixed>
     */
    public function decode(Request $request): array
    {
        $content = trim($request->getContent());

        if ($content === '') {
            return [];
        }

        try {
            $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw InvalidJsonRequestException::fromJsonException($exception);
        }

        if (!is_array($payload) || array_is_list($payload)) {
            throw InvalidJsonRequestException::becauseBodyIsNotObject();
        }

        return $payload;
    }
}
