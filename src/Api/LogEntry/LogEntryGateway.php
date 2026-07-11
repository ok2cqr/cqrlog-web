<?php

declare(strict_types=1);

namespace App\Api\LogEntry;

use Dibi\Connection;
use Dibi\Row;

final readonly class LogEntryGateway
{
    private const SELECT_FIELDS = 'id_cqrlog_main,
                qsodate,
                time_on,
                time_off,
                callsign,
                freq,
                mode,
                rst_s,
                rst_r,
                name,
                qth,
                loc,
                state,
                county,
                award,
                adif,
                band,
                (SELECT dxcc_id.dxcc_ref FROM dxcc_id WHERE dxcc_id.adif = cqrlog_main.adif LIMIT 1) AS dxcc_ref,
                remarks,
                qsl_s,
                qsl_r,
                qsl_via,
                iota,
                pwr,
                itu,
                waz,
                lotw_qslsdate,
                lotw_qslrdate,
                lotw_qsls,
                lotw_qslr,
                qsls_date,
                qslr_date,
                club_nr1,
                club_nr2,
                club_nr3,
                club_nr4,
                club_nr5,
                eqsl_qsl_sent,
                eqsl_qslsdate,
                eqsl_qsl_rcvd,
                eqsl_qslrdate,
                rxfreq,
                satellite,
                prop_mode,
                stx,
                srx,
                stx_string,
                srx_string,
                contestname,
                idcall,
                cont,
                dok,
                operator,
                my_loc,
                qso_dxcc,
                profile';

    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @return list<Row>
     */
    public function fetchAll(LogEntryListQuery $query): array
    {
        [$whereSql, $params] = $this->buildListFilters($query);
        $orderBySql = $this->buildOrderBy($query);
        $queryArgs = [
            ...$params,
            $query->perPage,
            $query->offset(),
        ];

        return $this->connection->fetchAll(
            sprintf(
                'SELECT %s
                FROM cqrlog_main%s
                ORDER BY %s
                LIMIT %%i OFFSET %%i',
                self::SELECT_FIELDS,
                $whereSql,
                $orderBySql,
            ),
            ...$queryArgs,
        );
    }

    public function count(LogEntryListQuery $query): int
    {
        [$whereSql, $params] = $this->buildListFilters($query);

        return (int) $this->connection->fetchSingle(
            sprintf('SELECT COUNT(*) FROM cqrlog_main%s', $whereSql),
            ...$params,
        );
    }

    public function fetchById(int $id): ?Row
    {
        return $this->connection->fetch(
            sprintf(
                'SELECT %s
                FROM cqrlog_main
                WHERE id_cqrlog_main = %%i',
                self::SELECT_FIELDS,
            ),
            $id,
        );
    }

    public function create(LogEntryInput $input): Row
    {
        $this->connection->insert('cqrlog_main', $this->buildWriteData($input))->execute();

        /** @var Row $row */
        $row = $this->fetchById($this->connection->getInsertId());

        return $row;
    }

    public function update(int $id, LogEntryInput $input): ?Row
    {
        $this->connection
            ->update('cqrlog_main', $this->buildWriteData($input))
            ->where('id_cqrlog_main = %i', $id)
            ->execute();

        return $this->fetchById($id);
    }

    /**
     * @return array<string, int|float|string>
     */
    private function buildWriteData(LogEntryInput $input): array
    {
        $data = $input->toDatabaseWriteData();

        if ($input->hasField('myLocator') || !$input->hasField('profileId') || $input->profileId === null) {
            return $data;
        }

        $profile = $this->connection->fetch(
            'SELECT locator
            FROM profiles
            WHERE id_profiles = %i',
            $input->profileId,
        );

        if ($profile === null) {
            return $data;
        }

        $data['my_loc'] = strtoupper(trim((string) ($profile->locator ?? '')));

        return $data;
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    private function buildListFilters(LogEntryListQuery $query): array
    {
        $clauses = [];
        $params = [];

        if ($query->callsign !== null) {
            $clauses[] = 'UPPER(callsign) LIKE %s';
            $params[] = '%' . $query->callsign . '%';
        }

        if ($query->contestName !== null) {
            $clauses[] = 'contestname = %s';
            $params[] = $query->contestName;
        }

        if ($query->qsoDateFrom !== null) {
            $clauses[] = 'qsodate >= %s';
            $params[] = $query->qsoDateFrom;
        }

        if ($query->qsoDateTo !== null) {
            $clauses[] = 'qsodate <= %s';
            $params[] = $query->qsoDateTo;
        }

        $whereSql = $clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses);

        return [$whereSql, $params];
    }

    private function buildOrderBy(LogEntryListQuery $query): string
    {
        $direction = strtoupper($query->sortDirection);

        return match ($query->sortBy) {
            'callsign' => sprintf('callsign %1$s, qsodate DESC, time_on DESC, id_cqrlog_main DESC', $direction),
            'frequency' => sprintf('freq %1$s, qsodate DESC, time_on DESC, id_cqrlog_main DESC', $direction),
            'mode' => sprintf('mode %1$s, qsodate DESC, time_on DESC, id_cqrlog_main DESC', $direction),
            'id' => sprintf('id_cqrlog_main %1$s', $direction),
            default => sprintf('qsodate %1$s, time_on %1$s, id_cqrlog_main %1$s', $direction),
        };
    }
}
