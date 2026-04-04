<?php

declare(strict_types=1);

namespace App\Api\Profile;

use App\Api\Exception\ValidationException;

final readonly class ProfileInput
{
    private const ALLOWED_FIELDS = [
        'number',
        'locator',
        'qth',
        'rig',
        'remarks',
        'visible',
    ];

    /**
     * @param array<string, true> $providedFields
     */
    private function __construct(
        public ?int $number,
        public ?string $locator,
        public ?string $qth,
        public ?string $rig,
        public ?string $remarks,
        public ?bool $visible,
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

        $number = self::normalizeNumber($payload, $partial, $fieldErrors);
        $locator = self::normalizeOptionalString($payload, 'locator', 6, $fieldErrors);
        $qth = self::normalizeOptionalString($payload, 'qth', 250, $fieldErrors);
        $rig = self::normalizeOptionalString($payload, 'rig', 250, $fieldErrors);
        $remarks = self::normalizeOptionalString($payload, 'remarks', 250, $fieldErrors);
        $visible = self::normalizeVisible($payload, $fieldErrors);

        if ($fieldErrors !== []) {
            throw new ValidationException($fieldErrors);
        }

        return new self($number, $locator, $qth, $rig, $remarks, $visible, $providedFields);
    }

    public function hasField(string $field): bool
    {
        return isset($this->providedFields[$field]);
    }

    /**
     * @return array<string, int|string>
     */
    public function toDatabaseWriteData(): array
    {
        $data = [];

        if ($this->hasField('number')) {
            $data['nr'] = $this->number;
        }

        if ($this->hasField('locator')) {
            $data['locator'] = $this->locator ?? '';
        }

        if ($this->hasField('qth')) {
            $data['qth'] = $this->qth ?? '';
        }

        if ($this->hasField('rig')) {
            $data['rig'] = $this->rig ?? '';
        }

        if ($this->hasField('remarks')) {
            $data['remarks'] = $this->remarks ?? '';
        }

        if ($this->hasField('visible')) {
            $data['visible'] = $this->visible ? 1 : 0;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, list<string>> $fieldErrors
     */
    private static function normalizeNumber(array $payload, bool $partial, array &$fieldErrors): ?int
    {
        if (!array_key_exists('number', $payload)) {
            if (!$partial) {
                self::addFieldError($fieldErrors, 'number', 'This field is required.');
            }

            return null;
        }

        $value = $payload['number'];

        if (!is_int($value)) {
            self::addFieldError($fieldErrors, 'number', 'This field must be an integer.');
            return null;
        }

        if ($value < 1) {
            self::addFieldError($fieldErrors, 'number', 'This field must be greater than 0.');
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, list<string>> $fieldErrors
     */
    private static function normalizeOptionalString(array $payload, string $field, int $maxLength, array &$fieldErrors): ?string
    {
        if (!array_key_exists($field, $payload)) {
            return null;
        }

        $value = $payload[$field];

        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            self::addFieldError($fieldErrors, $field, 'This field must be a string or null.');
            return null;
        }

        $value = trim($value);

        if (mb_strlen($value) > $maxLength) {
            self::addFieldError($fieldErrors, $field, sprintf('This field must be at most %d characters long.', $maxLength));
        }

        return $value === '' ? null : $value;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, list<string>> $fieldErrors
     */
    private static function normalizeVisible(array $payload, array &$fieldErrors): ?bool
    {
        if (!array_key_exists('visible', $payload)) {
            return null;
        }

        $value = $payload['visible'];

        if (!is_bool($value)) {
            self::addFieldError($fieldErrors, 'visible', 'This field must be a boolean.');
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
