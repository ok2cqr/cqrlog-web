<?php

declare(strict_types=1);

namespace App\Api\Station;

final class StationMapper
{
    /**
     * @param array<string, string> $section
     */
    public function map(array $section): StationView
    {
        return new StationView(
            callsign: $this->normalizeOptionalUppercase($section['Call'] ?? null),
            name: $this->normalizeOptionalString($section['Name'] ?? null),
            qth: $this->normalizeOptionalString($section['QTH'] ?? null),
            locator: $this->normalizeOptionalUppercase($section['LOC'] ?? null),
        );
    }

    private function normalizeOptionalUppercase(?string $value): ?string
    {
        $value = $this->normalizeOptionalString($value);

        return $value === null ? null : strtoupper($value);
    }

    private function normalizeOptionalString(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        return $value === '' ? null : $value;
    }
}
