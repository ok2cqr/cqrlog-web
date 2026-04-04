<?php

declare(strict_types=1);

namespace App\Api\Note;

use App\Api\Exception\ValidationException;

final readonly class NoteInput
{
    private const ALLOWED_FIELDS = [
        'callsign',
        'remarks',
    ];

    /**
     * @param array<string, true> $providedFields
     */
    private function __construct(
        public ?string $callsign,
        public ?string $remarks,
        private array $providedFields,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload, bool $partial = false): self
    {
        $fieldErrors = [];
        $providedFields = [];

        foreach (array_keys($payload) as $field) {
            $providedFields[$field] = true;

            if (!in_array($field, self::ALLOWED_FIELDS, true)) {
                self::addFieldError($fieldErrors, $field, 'This field is not allowed.');
            }
        }

        if ($partial && $providedFields === []) {
            self::addFieldError($fieldErrors, 'body', 'At least one field must be provided.');
        }

        $callsign = self::normalizeCallsign($payload, $partial, $fieldErrors);
        $remarks = self::normalizeRemarks($payload, $fieldErrors);

        if ($fieldErrors !== []) {
            throw new ValidationException($fieldErrors);
        }

        return new self($callsign, $remarks, $providedFields);
    }

    public function hasField(string $field): bool
    {
        return isset($this->providedFields[$field]);
    }

    /**
     * @return array<string, string>
     */
    public function toDatabaseWriteData(): array
    {
        $data = [];

        if ($this->hasField('callsign')) {
            $data['callsign'] = $this->callsign ?? '';
        }

        if ($this->hasField('remarks')) {
            $data['longremarks'] = $this->remarks ?? '';
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, list<string>> $fieldErrors
     */
    private static function normalizeCallsign(array $payload, bool $partial, array &$fieldErrors): ?string
    {
        if (!array_key_exists('callsign', $payload)) {
            if (!$partial) {
                self::addFieldError($fieldErrors, 'callsign', 'This field is required.');
            }

            return null;
        }

        $value = $payload['callsign'];

        if (!is_string($value)) {
            self::addFieldError($fieldErrors, 'callsign', 'This field must be a string.');
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            self::addFieldError($fieldErrors, 'callsign', 'This field must not be empty.');
            return null;
        }

        if (mb_strlen($value) > 20) {
            self::addFieldError($fieldErrors, 'callsign', 'This field must be at most 20 characters long.');
        }

        return strtoupper($value);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, list<string>> $fieldErrors
     */
    private static function normalizeRemarks(array $payload, array &$fieldErrors): ?string
    {
        if (!array_key_exists('remarks', $payload)) {
            return null;
        }

        $value = $payload['remarks'];

        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            self::addFieldError($fieldErrors, 'remarks', 'This field must be a string or null.');
            return null;
        }

        $value = trim($value);

        if (mb_strlen($value) > 256) {
            self::addFieldError($fieldErrors, 'remarks', 'This field must be at most 256 characters long.');
        }

        return $value === '' ? null : $value;
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
