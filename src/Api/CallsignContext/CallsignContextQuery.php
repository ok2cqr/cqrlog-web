<?php

declare(strict_types=1);

namespace App\Api\CallsignContext;

use App\Api\Exception\ValidationException;
use App\Support\CallsignIdResolver;
use DateTimeImmutable;

final readonly class CallsignContextQuery
{
    private function __construct(
        public string $callsign,
        public string $idCall,
        public ?string $qsoDate,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     */
    public static function fromArray(array $query): self
    {
        $fieldErrors = [];

        $callsign = self::normalizeCallsign($query, $fieldErrors);
        $qsoDate = self::normalizeOptionalDate($query, 'qsoDate', $fieldErrors);

        if ($fieldErrors !== []) {
            throw new ValidationException($fieldErrors);
        }

        return new self(
            callsign: $callsign,
            idCall: CallsignIdResolver::resolve($callsign),
            qsoDate: $qsoDate,
        );
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, list<string>> $fieldErrors
     */
    private static function normalizeCallsign(array $query, array &$fieldErrors): string
    {
        if (!array_key_exists('callsign', $query)) {
            self::addFieldError($fieldErrors, 'callsign', 'This field is required.');

            return '';
        }

        $value = $query['callsign'];

        if (!is_scalar($value)) {
            self::addFieldError($fieldErrors, 'callsign', 'This field must be a string.');

            return '';
        }

        $value = CallsignIdResolver::normalize((string) $value);

        if ($value === '') {
            self::addFieldError($fieldErrors, 'callsign', 'This field must not be empty.');
        }

        if (mb_strlen($value) > 20) {
            self::addFieldError($fieldErrors, 'callsign', 'This field must be at most 20 characters long.');
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, list<string>> $fieldErrors
     */
    private static function normalizeOptionalDate(array $query, string $field, array &$fieldErrors): ?string
    {
        if (!array_key_exists($field, $query) || $query[$field] === null || $query[$field] === '') {
            return null;
        }

        $value = $query[$field];

        if (!is_scalar($value)) {
            self::addFieldError($fieldErrors, $field, 'This field must be a string in Y-m-d format.');

            return null;
        }

        $value = trim((string) $value);
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);

        if ($date === false || $date->format('Y-m-d') !== $value) {
            self::addFieldError($fieldErrors, $field, 'This field must use Y-m-d format.');

            return null;
        }

        return $value;
    }

    /**
     * @param array<string, list<string>> $fieldErrors
     */
    private static function addFieldError(array &$fieldErrors, string $field, string $message): void
    {
        $fieldErrors[$field] ??= [];
        $fieldErrors[$field][] = $message;
    }
}
