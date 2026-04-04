<?php

declare(strict_types=1);

namespace App\Api\LogEntry;

use Dibi\Row;

final class LogEntryMapper
{
    public function map(Row $row): LogEntryView
    {
        return new LogEntryView(
            id: (int) $row->id_cqrlog_main,
            qsoDate: (string) $row->qsodate,
            timeOn: (string) $row->time_on,
            timeOff: $this->normalizeOptionalString($row->time_off),
            callsign: (string) $row->callsign,
            frequency: (float) $row->freq,
            mode: (string) $row->mode,
            rstSent: $this->normalizeOptionalString($row->rst_s),
            rstReceived: $this->normalizeOptionalString($row->rst_r),
            name: $this->normalizeOptionalString($row->name),
            qth: $this->normalizeOptionalString($row->qth),
            grid: $this->normalizeOptionalString($row->loc),
            state: $this->normalizeOptionalString($row->state),
            county: $this->normalizeOptionalString($row->county),
            award: $this->normalizeOptionalString($row->award),
            adif: $this->normalizeOptionalInt($row->adif),
            band: $this->normalizeOptionalString($row->band),
            dxccRef: $this->normalizeOptionalString($row->dxcc_ref),
            remarks: $this->normalizeOptionalString($row->remarks),
            qslSent: $this->normalizeOptionalString($row->qsl_s),
            qslReceived: $this->normalizeOptionalString($row->qsl_r),
            qslVia: $this->normalizeOptionalString($row->qsl_via),
            iota: $this->normalizeOptionalString($row->iota),
            power: $this->normalizeOptionalString($row->pwr),
            itu: $this->normalizeOptionalInt($row->itu),
            waz: $this->normalizeOptionalInt($row->waz),
            idCall: $this->normalizeOptionalString($row->idcall),
            lotwSentDate: $this->normalizeOptionalString($row->lotw_qslsdate),
            lotwReceivedDate: $this->normalizeOptionalString($row->lotw_qslrdate),
            lotwSent: $this->normalizeOptionalString($row->lotw_qsls),
            lotwReceived: $this->normalizeOptionalString($row->lotw_qslr),
            continent: $this->normalizeOptionalString($row->cont),
            qslSentDate: $this->normalizeOptionalString($row->qsls_date),
            qslReceivedDate: $this->normalizeOptionalString($row->qslr_date),
            clubNumber1: $this->normalizeOptionalString($row->club_nr1),
            clubNumber2: $this->normalizeOptionalString($row->club_nr2),
            clubNumber3: $this->normalizeOptionalString($row->club_nr3),
            clubNumber4: $this->normalizeOptionalString($row->club_nr4),
            clubNumber5: $this->normalizeOptionalString($row->club_nr5),
            eqslSent: $this->normalizeOptionalString($row->eqsl_qsl_sent),
            eqslSentDate: $this->normalizeOptionalString($row->eqsl_qslsdate),
            eqslReceived: $this->normalizeOptionalString($row->eqsl_qsl_rcvd),
            eqslReceivedDate: $this->normalizeOptionalString($row->eqsl_qslrdate),
            receiveFrequency: $this->normalizeOptionalFloat($row->rxfreq),
            satellite: $this->normalizeOptionalString($row->satellite),
            propagationMode: $this->normalizeOptionalString($row->prop_mode),
            stx: $this->normalizeOptionalString($row->stx),
            srx: $this->normalizeOptionalString($row->srx),
            stxString: $this->normalizeOptionalString($row->stx_string),
            srxString: $this->normalizeOptionalString($row->srx_string),
            contestName: $this->normalizeOptionalString($row->contestname),
            dok: $this->normalizeOptionalString($row->dok),
            operator: $this->normalizeOptionalString($row->operator),
            myLocator: $this->normalizeOptionalString($row->my_loc),
            qsoDxcc: $this->normalizeOptionalInt($row->qso_dxcc),
            profileId: $this->normalizeOptionalInt($row->profile),
        );
    }

    private function normalizeOptionalString(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        return $value === '' ? null : $value;
    }

    private function normalizeOptionalInt(int|string|null $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized === 0 ? null : $normalized;
    }

    private function normalizeOptionalFloat(float|int|string|null $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $normalized = (float) $value;

        return $normalized === 0.0 ? null : $normalized;
    }
}
