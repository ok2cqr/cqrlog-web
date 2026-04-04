<?php

declare(strict_types=1);

namespace App\Api\LongNote;

use App\Api\Exception\ValidationException;

final readonly class LongNoteInput
{
    private const ALLOWED_FIELDS = ['note'];

    /**
     * @param array<string, true> $providedFields
     */
    private function __construct(
        public ?string $note,
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

        $note = self::normalizeNote($payload, $partial, $fieldErrors);

        if ($fieldErrors !== []) {
            throw new ValidationException($fieldErrors);
        }

        return new self($note, $providedFields);
    }

    public function hasField(string $field): bool
    {
        return isset($this->providedFields[$field]);
    }

    /**
     * @return array<string, string|null>
     */
    public function toDatabaseWriteData(): array
    {
        $data = [];

        if ($this->hasField('note')) {
            $data['note'] = $this->note;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, list<string>> $fieldErrors
     */
    private static function normalizeNote(array $payload, bool $partial, array &$fieldErrors): ?string
    {
        if (!array_key_exists('note', $payload)) {
            if (!$partial) {
                self::addFieldError($fieldErrors, 'note', 'This field is required.');
            }

            return null;
        }

        $value = $payload['note'];

        if ($value === null) {
            if (!$partial) {
                self::addFieldError($fieldErrors, 'note', 'This field must not be null.');
            }

            return null;
        }

        if (!is_string($value)) {
            self::addFieldError($fieldErrors, 'note', 'This field must be a string or null.');
            return null;
        }

        $value = trim($value);

        if ($value === '' && !$partial) {
            self::addFieldError($fieldErrors, 'note', 'This field must not be empty.');
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
