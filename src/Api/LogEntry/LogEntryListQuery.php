<?php

declare(strict_types=1);

namespace App\Api\LogEntry;

use App\Api\Exception\ValidationException;
use DateTimeImmutable;

final readonly class LogEntryListQuery
{
    private const DEFAULT_PAGE = 1;
    private const DEFAULT_PER_PAGE = 50;
    private const MAX_PER_PAGE = 100;
    private const DEFAULT_SORT_BY = 'qsoDate';
    private const DEFAULT_SORT_DIRECTION = 'desc';
    private const ALLOWED_SORT_FIELDS = [
        'qsoDate',
        'callsign',
        'frequency',
        'mode',
        'id',
    ];
    private const ALLOWED_SORT_DIRECTIONS = [
        'asc',
        'desc',
    ];

    private function __construct(
        public int $page,
        public int $perPage,
        public ?string $callsign,
        public ?string $contestName,
        public ?string $qsoDateFrom,
        public ?string $qsoDateTo,
        public string $sortBy,
        public string $sortDirection,
    ) {
    }

    /**
     * @param array<string, mixed> $query
     */
    public static function fromArray(array $query): self
    {
        $fieldErrors = [];

        $page = self::normalizePositiveInt($query, 'page', self::DEFAULT_PAGE, $fieldErrors);
        $perPage = self::normalizePositiveInt($query, 'perPage', self::DEFAULT_PER_PAGE, $fieldErrors);

        if ($perPage > self::MAX_PER_PAGE) {
            self::addFieldError($fieldErrors, 'perPage', sprintf('This field must be less than or equal to %d.', self::MAX_PER_PAGE));
        }

        $callsign = self::normalizeOptionalString($query, 'callsign', 20, true, $fieldErrors);
        $contestName = self::normalizeOptionalString($query, 'contestName', 40, false, $fieldErrors);
        $qsoDateFrom = self::normalizeOptionalDate($query, 'qsoDateFrom', $fieldErrors);
        $qsoDateTo = self::normalizeOptionalDate($query, 'qsoDateTo', $fieldErrors);
        $sortBy = self::normalizeSortBy($query, $fieldErrors);
        $sortDirection = self::normalizeSortDirection($query, $fieldErrors);

        if (
            $qsoDateFrom !== null
            && $qsoDateTo !== null
            && strcmp($qsoDateFrom, $qsoDateTo) > 0
        ) {
            self::addFieldError($fieldErrors, 'qsoDateFrom', 'This field must be less than or equal to qsoDateTo.');
        }

        if ($fieldErrors !== []) {
            throw new ValidationException($fieldErrors);
        }

        return new self(
            page: $page,
            perPage: $perPage,
            callsign: $callsign,
            contestName: $contestName,
            qsoDateFrom: $qsoDateFrom,
            qsoDateTo: $qsoDateTo,
            sortBy: $sortBy,
            sortDirection: $sortDirection,
        );
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    private static function normalizePositiveInt(array $query, string $field, int $default, array &$fieldErrors): int
    {
        if (!array_key_exists($field, $query) || $query[$field] === '' || $query[$field] === null) {
            return $default;
        }

        $value = $query[$field];

        if (!is_scalar($value)) {
            self::addFieldError($fieldErrors, $field, 'This field must be an integer.');
            return $default;
        }

        $value = (string) $value;

        if (!preg_match('/^\d+$/', $value)) {
            self::addFieldError($fieldErrors, $field, 'This field must be an integer.');
            return $default;
        }

        $normalized = (int) $value;

        if ($normalized < 1) {
            self::addFieldError($fieldErrors, $field, 'This field must be greater than or equal to 1.');
            return $default;
        }

        return $normalized;
    }

    private static function normalizeOptionalString(array $query, string $field, int $maxLength, bool $uppercase, array &$fieldErrors): ?string
    {
        if (!array_key_exists($field, $query) || $query[$field] === null || $query[$field] === '') {
            return null;
        }

        $value = $query[$field];

        if (!is_scalar($value)) {
            self::addFieldError($fieldErrors, $field, 'This field must be a string.');
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (mb_strlen($value) > $maxLength) {
            self::addFieldError($fieldErrors, $field, sprintf('This field must be at most %d characters long.', $maxLength));
        }

        return $uppercase ? strtoupper($value) : $value;
    }

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

    private static function normalizeSortBy(array $query, array &$fieldErrors): string
    {
        if (!array_key_exists('sortBy', $query) || $query['sortBy'] === null || $query['sortBy'] === '') {
            return self::DEFAULT_SORT_BY;
        }

        $value = $query['sortBy'];

        if (!is_scalar($value)) {
            self::addFieldError($fieldErrors, 'sortBy', 'This field must be a string.');
            return self::DEFAULT_SORT_BY;
        }

        $value = trim((string) $value);

        if (!in_array($value, self::ALLOWED_SORT_FIELDS, true)) {
            self::addFieldError(
                $fieldErrors,
                'sortBy',
                sprintf('This field must be one of: %s.', implode(', ', self::ALLOWED_SORT_FIELDS)),
            );

            return self::DEFAULT_SORT_BY;
        }

        return $value;
    }

    private static function normalizeSortDirection(array $query, array &$fieldErrors): string
    {
        if (!array_key_exists('sortDirection', $query) || $query['sortDirection'] === null || $query['sortDirection'] === '') {
            return self::DEFAULT_SORT_DIRECTION;
        }

        $value = $query['sortDirection'];

        if (!is_scalar($value)) {
            self::addFieldError($fieldErrors, 'sortDirection', 'This field must be a string.');
            return self::DEFAULT_SORT_DIRECTION;
        }

        $value = strtolower(trim((string) $value));

        if (!in_array($value, self::ALLOWED_SORT_DIRECTIONS, true)) {
            self::addFieldError(
                $fieldErrors,
                'sortDirection',
                sprintf('This field must be one of: %s.', implode(', ', self::ALLOWED_SORT_DIRECTIONS)),
            );

            return self::DEFAULT_SORT_DIRECTION;
        }

        return $value;
    }

    private static function addFieldError(array &$fieldErrors, string $field, string $message): void
    {
        $fieldErrors[$field] ??= [];
        $fieldErrors[$field][] = $message;
    }
}
