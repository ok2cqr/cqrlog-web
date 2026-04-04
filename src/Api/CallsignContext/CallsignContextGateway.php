<?php

declare(strict_types=1);

namespace App\Api\CallsignContext;

use DateTimeImmutable;
use Dibi\Connection;
use Dibi\Row;

final readonly class CallsignContextGateway
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function fetch(CallsignContextQuery $query): CallsignContextView
    {
        $clubNames = $this->resolveClubNames();
        $clubs = [];
        $recentRows = $this->fetchRecentQsoRows($query);

        for ($slot = 1; $slot <= 5; $slot++) {
            foreach ($this->fetchClubRows($slot, $query) as $row) {
                $clubNumber = $this->normalizeOptionalString($row->club_nr);

                if ($clubNumber === null) {
                    continue;
                }

                $clubs[] = new ClubMembershipView(
                    slot: $slot,
                    name: $clubNames[$slot] ?? sprintf('Club %d', $slot),
                    number: $clubNumber,
                    fromDate: $this->normalizeOptionalString($row->fromdate),
                    toDate: $this->normalizeOptionalString($row->todate),
                );
            }
        }

        return new CallsignContextView(
            callsign: $query->callsign,
            idCall: $query->idCall,
            note: $this->mapNote($this->fetchNoteRow($query)),
            clubs: $clubs,
            recentQsoCount: count($recentRows),
            recentQsos: array_map(fn (Row $row): RecentQsoView => $this->mapRecentQso($row), array_slice($recentRows, 0, 6)),
            autofill: $this->buildAutofill($query, $recentRows),
        );
    }

    private function fetchNoteRow(CallsignContextQuery $query): ?Row
    {
        $row = $this->connection->fetch(
            'SELECT id_notes, longremarks
            FROM notes
            WHERE UPPER(callsign) = %s
            ORDER BY id_notes ASC
            LIMIT 1',
            $query->callsign,
        );

        if ($row !== null || $query->idCall === $query->callsign) {
            return $row;
        }

        return $this->connection->fetch(
            'SELECT id_notes, longremarks
            FROM notes
            WHERE UPPER(callsign) = %s
            ORDER BY id_notes ASC
            LIMIT 1',
            $query->idCall,
        );
    }

    /**
     * @return list<Row>
     */
    private function fetchClubRows(int $slot, CallsignContextQuery $query): array
    {
        $table = sprintf('club%d', $slot);
        $referenceDate = $query->qsoDate ?? (new DateTimeImmutable())->format('Y-m-d');

        $row = $this->connection->fetch(
            sprintf(
                'SELECT club_nr, fromdate, todate
                FROM %s
                WHERE UPPER(clubcall) = %%s
                  AND TRIM(COALESCE(club_nr, \'\')) <> \'\'
                  AND (fromdate IS NULL OR fromdate <= %%s)
                  AND (todate IS NULL OR todate >= %%s)
                ORDER BY
                  CASE WHEN fromdate IS NULL THEN 1 ELSE 0 END ASC,
                  fromdate DESC,
                  CASE WHEN todate IS NULL THEN 1 ELSE 0 END DESC,
                  todate DESC,
                  club_nr ASC
                LIMIT 1',
                $table,
            ),
            $query->idCall,
            $referenceDate,
            $referenceDate,
        );

        return $row === null ? [] : [$row];
    }

    /**
     * @return list<Row>
     */
    private function fetchRecentQsoRows(CallsignContextQuery $query): array
    {
        return $this->connection->fetchAll(
            'SELECT id_cqrlog_main, qsodate, time_on, time_off, callsign, idcall, band, mode, name, qth, award, qsl_via, state, county, waz, itu, loc, iota
            FROM cqrlog_main
            WHERE UPPER(COALESCE(idcall, \'\')) = %s
            ORDER BY qsodate DESC, time_on DESC, id_cqrlog_main DESC',
            $query->idCall,
        );
    }

    /**
     * @return array<int, string>
     */
    private function resolveClubNames(): array
    {
        $defaultNames = [
            1 => 'Club 1',
            2 => 'Club 2',
            3 => 'Club 3',
            4 => 'Club 4',
            5 => 'Club 5',
        ];

        $config = $this->connection->fetchSingle(
            'SELECT config_file
            FROM cqrlog_config
            ORDER BY id_cqrlog__config DESC
            LIMIT 1',
        );

        if (!is_string($config) || trim($config) === '') {
            return $defaultNames;
        }

        $config = str_replace(["\\r\\n", "\\n", "\\r"], ["\n", "\n", "\r"], $config);
        $names = $defaultNames;
        $clubsSection = $this->extractIniSection($config, 'Clubs');

        for ($slot = 1; $slot <= 5; $slot++) {
            $configuredName = $this->resolveClubNameForSlot($clubsSection, $slot);

            if ($configuredName !== null) {
                $names[$slot] = $configuredName;
            }
        }

        return $names;
    }

    /**
     * @return array<string, string>
     */
    private function extractIniSection(string $config, string $sectionName): array
    {
        if (preg_match(sprintf('/^\[%s\]\R(.*?)(?=^\[|\z)/ms', preg_quote($sectionName, '/')), $config, $matches) !== 1) {
            return [];
        }

        $values = [];
        $lines = preg_split('/\R/', trim($matches[1])) ?: [];

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            if ($trimmedLine === '' || str_starts_with($trimmedLine, ';') || str_starts_with($trimmedLine, '#')) {
                continue;
            }

            $separatorPosition = strpos($trimmedLine, '=');

            if ($separatorPosition === false) {
                continue;
            }

            $key = trim(substr($trimmedLine, 0, $separatorPosition));
            $value = trim(substr($trimmedLine, $separatorPosition + 1));

            if ($key === '' || $value === '') {
                continue;
            }

            $values[$key] = trim($value, " \t\n\r\0\x0B\"'");
        }

        return $values;
    }

    /**
     * @param array<string, mixed> $clubsSection
     */
    private function resolveClubNameForSlot(array $clubsSection, int $slot): ?string
    {
        $orderedKeys = [
            1 => 'First',
            2 => 'Second',
            3 => 'Third',
            4 => 'Fourth',
            5 => 'Fifth',
        ];

        $orderedKey = $orderedKeys[$slot] ?? null;

        if ($orderedKey !== null) {
            $orderedValue = $this->normalizeOptionalString(is_scalar($clubsSection[$orderedKey] ?? null) ? (string) $clubsSection[$orderedKey] : null);

            if ($orderedValue !== null) {
                $parts = array_map('trim', explode(';', $orderedValue, 2));

                if (($parts[0] ?? '') !== '') {
                    return $parts[0];
                }

                if (($parts[1] ?? '') !== '') {
                    return $parts[1];
                }
            }
        }

        $legacyKeys = [
            sprintf('club%dname', $slot),
            sprintf('club%d', $slot),
            sprintf('club%dlabel', $slot),
        ];

        foreach ($legacyKeys as $key) {
            $value = $this->normalizeOptionalString(is_scalar($clubsSection[$key] ?? null) ? (string) $clubsSection[$key] : null);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function mapNote(?Row $row): ?CallsignNoteView
    {
        if ($row === null) {
            return null;
        }

        return new CallsignNoteView(
            id: (int) $row->id_notes,
            remarks: $this->normalizeOptionalString($row->longremarks),
        );
    }

    private function mapRecentQso(Row $row): RecentQsoView
    {
        return new RecentQsoView(
            id: (int) $row->id_cqrlog_main,
            qsoDate: (string) $row->qsodate,
            timeOn: (string) $row->time_on,
            timeOff: $this->normalizeOptionalString($row->time_off),
            callsign: (string) $row->callsign,
            band: $this->normalizeOptionalString($row->band),
            mode: (string) $row->mode,
        );
    }

    /**
     * @param list<Row> $rows
     */
    private function buildAutofill(CallsignContextQuery $query, array $rows): CallsignAutofillView
    {
        $name = null;
        $qth = null;
        $award = null;
        $qslVia = null;
        $state = null;
        $county = null;
        $waz = null;
        $itu = null;
        $grid = null;
        $iota = null;

        foreach ($rows as $row) {
            $name ??= $this->normalizeOptionalString($row->name);
            $qth ??= $this->resolveCallsignExactAutofillValue($query->callsign, $row->callsign, $row->qth);
            $award ??= $this->normalizeOptionalString($row->award);
            $qslVia ??= $this->normalizeOptionalString($row->qsl_via);
            $state ??= $this->normalizeOptionalString($row->state);
            $county ??= $this->normalizeOptionalString($row->county);
            $waz ??= $this->normalizeOptionalInt($row->waz);
            $itu ??= $this->normalizeOptionalInt($row->itu);
            $grid ??= $this->resolveCallsignExactAutofillValue($query->callsign, $row->callsign, $row->loc);
            $iota ??= $this->normalizeOptionalString($row->iota);
        }

        return new CallsignAutofillView(
            name: $name,
            qth: $qth,
            award: $award,
            qslVia: $qslVia,
            state: $state,
            county: $county,
            waz: $waz,
            itu: $itu,
            grid: $grid,
            iota: $iota,
        );
    }

    private function resolveCallsignExactAutofillValue(string $expectedCallsign, mixed $rowCallsign, mixed $value): ?string
    {
        $callsign = $this->normalizeOptionalString($rowCallsign);

        if ($callsign === null || $callsign !== $expectedCallsign) {
            return null;
        }

        return $this->normalizeOptionalString($value);
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeOptionalInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = (int) $value;

        return $normalized === 0 ? null : $normalized;
    }
}
