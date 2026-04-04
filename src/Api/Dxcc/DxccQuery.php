<?php

declare(strict_types=1);

namespace App\Api\Dxcc;

use App\Api\Exception\ValidationException;
use App\Support\CallsignIdResolver;

final readonly class DxccQuery
{
    private function __construct(
        public string $callsign,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     */
    public static function fromArray(array $query): self
    {
        $fieldErrors = [];

        if (!array_key_exists('callsign', $query)) {
            $fieldErrors['callsign'][] = 'This field is required.';
        }

        $value = $query['callsign'] ?? null;

        if (!is_scalar($value)) {
            $fieldErrors['callsign'][] = 'This field must be a string.';
        }

        $callsign = CallsignIdResolver::normalize((string) $value);

        if ($callsign === '') {
            $fieldErrors['callsign'][] = 'This field must not be empty.';
        }

        if (mb_strlen($callsign) > 20) {
            $fieldErrors['callsign'][] = 'This field must be at most 20 characters long.';
        }

        if ($fieldErrors !== []) {
            throw new ValidationException($fieldErrors);
        }

        return new self($callsign);
    }
}
