<?php

declare(strict_types=1);

namespace App\Api\LogEntry;

use App\Api\Exception\ValidationException;
use App\Support\CallsignIdResolver;
use DateTimeImmutable;

final readonly class LogEntryInput
{
    private const ALLOWED_FIELDS = [
        'qsoDate',
        'timeOn',
        'timeOff',
        'callsign',
        'frequency',
        'mode',
        'rstSent',
        'rstReceived',
        'name',
        'qth',
        'grid',
        'state',
        'county',
        'award',
        'adif',
        'band',
        'remarks',
        'qslSent',
        'qslReceived',
        'qslVia',
        'iota',
        'power',
        'itu',
        'waz',
        'idCall',
        'lotwSentDate',
        'lotwReceivedDate',
        'lotwSent',
        'lotwReceived',
        'continent',
        'qslSentDate',
        'qslReceivedDate',
        'clubNumber1',
        'clubNumber2',
        'clubNumber3',
        'clubNumber4',
        'clubNumber5',
        'eqslSent',
        'eqslSentDate',
        'eqslReceived',
        'eqslReceivedDate',
        'receiveFrequency',
        'satellite',
        'propagationMode',
        'stx',
        'srx',
        'stxString',
        'srxString',
        'contestName',
        'dok',
        'operator',
        'myLocator',
        'qsoDxcc',
        'profileId',
    ];

    /**
     * @param array<string, true> $providedFields
     */
    private function __construct(
        public ?string $qsoDate,
        public ?string $timeOn,
        public ?string $timeOff,
        public ?string $callsign,
        public ?float $frequency,
        public ?string $mode,
        public ?string $rstSent,
        public ?string $rstReceived,
        public ?string $name,
        public ?string $qth,
        public ?string $grid,
        public ?string $state,
        public ?string $county,
        public ?string $award,
        public ?int $adif,
        public ?string $band,
        public ?string $remarks,
        public ?string $qslSent,
        public ?string $qslReceived,
        public ?string $qslVia,
        public ?string $iota,
        public ?string $power,
        public ?int $itu,
        public ?int $waz,
        public ?string $idCall,
        public ?string $lotwSentDate,
        public ?string $lotwReceivedDate,
        public ?string $lotwSent,
        public ?string $lotwReceived,
        public ?string $continent,
        public ?string $qslSentDate,
        public ?string $qslReceivedDate,
        public ?string $clubNumber1,
        public ?string $clubNumber2,
        public ?string $clubNumber3,
        public ?string $clubNumber4,
        public ?string $clubNumber5,
        public ?string $eqslSent,
        public ?string $eqslSentDate,
        public ?string $eqslReceived,
        public ?string $eqslReceivedDate,
        public ?float $receiveFrequency,
        public ?string $satellite,
        public ?string $propagationMode,
        public ?string $stx,
        public ?string $srx,
        public ?string $stxString,
        public ?string $srxString,
        public ?string $contestName,
        public ?string $dok,
        public ?string $operator,
        public ?string $myLocator,
        public ?int $qsoDxcc,
        public ?int $profileId,
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

        $qsoDate = self::normalizeDateField($payload, 'qsoDate', !$partial, $fieldErrors);
        $timeOn = self::normalizeTimeField($payload, 'timeOn', !$partial, $fieldErrors);
        $timeOff = self::normalizeTimeField($payload, 'timeOff', false, $fieldErrors);
        $callsign = self::normalizeRequiredString($payload, 'callsign', 20, !$partial, true, $fieldErrors);
        $frequency = self::normalizeFrequency($payload, !$partial, $fieldErrors);
        $mode = self::normalizeRequiredString($payload, 'mode', 12, !$partial, false, $fieldErrors);
        $rstSent = self::normalizeOptionalString($payload, 'rstSent', 20, false, $fieldErrors);
        $rstReceived = self::normalizeOptionalString($payload, 'rstReceived', 20, false, $fieldErrors);
        $name = self::normalizeOptionalString($payload, 'name', 40, false, $fieldErrors);
        $qth = self::normalizeOptionalString($payload, 'qth', 60, false, $fieldErrors);
        $grid = self::normalizeOptionalString($payload, 'grid', 10, true, $fieldErrors);
        $state = self::normalizeOptionalString($payload, 'state', 4, true, $fieldErrors);
        $county = self::normalizeOptionalString($payload, 'county', 30, false, $fieldErrors);
        $award = self::normalizeOptionalString($payload, 'award', 50, false, $fieldErrors);
        $adif = self::normalizeOptionalInteger($payload, 'adif', $fieldErrors);
        $band = self::normalizeOptionalString($payload, 'band', 6, false, $fieldErrors);
        $remarks = self::normalizeOptionalString($payload, 'remarks', 200, false, $fieldErrors);
        $qslSent = self::normalizeOptionalString($payload, 'qslSent', 4, false, $fieldErrors);
        $qslReceived = self::normalizeOptionalString($payload, 'qslReceived', 3, false, $fieldErrors);
        $qslVia = self::normalizeOptionalString($payload, 'qslVia', 30, false, $fieldErrors);
        $iota = self::normalizeOptionalString($payload, 'iota', 6, true, $fieldErrors);
        $power = self::normalizeOptionalString($payload, 'power', 10, false, $fieldErrors);
        $itu = self::normalizeOptionalInteger($payload, 'itu', $fieldErrors);
        $waz = self::normalizeOptionalInteger($payload, 'waz', $fieldErrors);
        $idCall = self::normalizeOptionalString($payload, 'idCall', 20, true, $fieldErrors);
        $lotwSentDate = self::normalizeOptionalDateField($payload, 'lotwSentDate', $fieldErrors);
        $lotwReceivedDate = self::normalizeOptionalDateField($payload, 'lotwReceivedDate', $fieldErrors);
        $lotwSent = self::normalizeOptionalString($payload, 'lotwSent', 3, true, $fieldErrors);
        $lotwReceived = self::normalizeOptionalString($payload, 'lotwReceived', 3, true, $fieldErrors);
        $continent = self::normalizeOptionalString($payload, 'continent', 3, true, $fieldErrors);
        $qslSentDate = self::normalizeOptionalString($payload, 'qslSentDate', 10, false, $fieldErrors);
        $qslReceivedDate = self::normalizeOptionalString($payload, 'qslReceivedDate', 10, false, $fieldErrors);
        $clubNumber1 = self::normalizeOptionalString($payload, 'clubNumber1', 100, false, $fieldErrors);
        $clubNumber2 = self::normalizeOptionalString($payload, 'clubNumber2', 100, false, $fieldErrors);
        $clubNumber3 = self::normalizeOptionalString($payload, 'clubNumber3', 100, false, $fieldErrors);
        $clubNumber4 = self::normalizeOptionalString($payload, 'clubNumber4', 100, false, $fieldErrors);
        $clubNumber5 = self::normalizeOptionalString($payload, 'clubNumber5', 100, false, $fieldErrors);
        $eqslSent = self::normalizeOptionalString($payload, 'eqslSent', 1, true, $fieldErrors);
        $eqslSentDate = self::normalizeOptionalDateField($payload, 'eqslSentDate', $fieldErrors);
        $eqslReceived = self::normalizeOptionalString($payload, 'eqslReceived', 1, true, $fieldErrors);
        $eqslReceivedDate = self::normalizeOptionalDateField($payload, 'eqslReceivedDate', $fieldErrors);
        $receiveFrequency = self::normalizeOptionalFrequency($payload, 'receiveFrequency', $fieldErrors);
        $satellite = self::normalizeOptionalString($payload, 'satellite', 30, false, $fieldErrors);
        $propagationMode = self::normalizeOptionalString($payload, 'propagationMode', 30, false, $fieldErrors);
        $stx = self::normalizeOptionalString($payload, 'stx', 6, false, $fieldErrors);
        $srx = self::normalizeOptionalString($payload, 'srx', 6, false, $fieldErrors);
        $stxString = self::normalizeOptionalString($payload, 'stxString', 50, false, $fieldErrors);
        $srxString = self::normalizeOptionalString($payload, 'srxString', 50, false, $fieldErrors);
        $contestName = self::normalizeOptionalString($payload, 'contestName', 40, false, $fieldErrors);
        $dok = self::normalizeOptionalString($payload, 'dok', 12, true, $fieldErrors);
        $operator = self::normalizeOptionalString($payload, 'operator', 20, true, $fieldErrors);
        $myLocator = self::normalizeOptionalString($payload, 'myLocator', 10, true, $fieldErrors);
        $qsoDxcc = self::normalizeOptionalInteger($payload, 'qsoDxcc', $fieldErrors);
        $profileId = self::normalizeOptionalInteger($payload, 'profileId', $fieldErrors);

        if ($fieldErrors !== []) {
            throw new ValidationException($fieldErrors);
        }

        return new self(
            qsoDate: $qsoDate,
            timeOn: $timeOn,
            timeOff: $timeOff,
            callsign: $callsign,
            frequency: $frequency,
            mode: $mode,
            rstSent: $rstSent,
            rstReceived: $rstReceived,
            name: $name,
            qth: $qth,
            grid: $grid,
            state: $state,
            county: $county,
            award: $award,
            adif: $adif,
            band: $band,
            remarks: $remarks,
            qslSent: $qslSent,
            qslReceived: $qslReceived,
            qslVia: $qslVia,
            iota: $iota,
            power: $power,
            itu: $itu,
            waz: $waz,
            idCall: $idCall,
            lotwSentDate: $lotwSentDate,
            lotwReceivedDate: $lotwReceivedDate,
            lotwSent: $lotwSent,
            lotwReceived: $lotwReceived,
            continent: $continent,
            qslSentDate: $qslSentDate,
            qslReceivedDate: $qslReceivedDate,
            clubNumber1: $clubNumber1,
            clubNumber2: $clubNumber2,
            clubNumber3: $clubNumber3,
            clubNumber4: $clubNumber4,
            clubNumber5: $clubNumber5,
            eqslSent: $eqslSent,
            eqslSentDate: $eqslSentDate,
            eqslReceived: $eqslReceived,
            eqslReceivedDate: $eqslReceivedDate,
            receiveFrequency: $receiveFrequency,
            satellite: $satellite,
            propagationMode: $propagationMode,
            stx: $stx,
            srx: $srx,
            stxString: $stxString,
            srxString: $srxString,
            contestName: $contestName,
            dok: $dok,
            operator: $operator,
            myLocator: $myLocator,
            qsoDxcc: $qsoDxcc,
            profileId: $profileId,
            providedFields: $providedFields,
        );
    }

    public function hasField(string $field): bool
    {
        return isset($this->providedFields[$field]);
    }

    /**
     * @return array<string, int|float|string>
     */
    public function toDatabaseWriteData(): array
    {
        $data = [];
        $resolvedIdCall = $this->resolveIdCallForWrite();

        $mapping = [
            'qsoDate' => ['qsodate', $this->qsoDate],
            'timeOn' => ['time_on', $this->timeOn],
            'timeOff' => ['time_off', $this->timeOff ?? ''],
            'callsign' => ['callsign', $this->callsign],
            'frequency' => ['freq', $this->frequency],
            'mode' => ['mode', $this->mode],
            'rstSent' => ['rst_s', $this->rstSent ?? ''],
            'rstReceived' => ['rst_r', $this->rstReceived ?? ''],
            'name' => ['name', $this->name ?? ''],
            'qth' => ['qth', $this->qth ?? ''],
            'grid' => ['loc', $this->grid ?? ''],
            'state' => ['state', $this->state ?? ''],
            'county' => ['county', $this->county ?? ''],
            'award' => ['award', $this->award ?? ''],
            'adif' => ['adif', $this->adif ?? 0],
            'band' => ['band', $this->band ?? ''],
            'remarks' => ['remarks', $this->remarks ?? ''],
            'qslSent' => ['qsl_s', $this->qslSent ?? ''],
            'qslReceived' => ['qsl_r', $this->qslReceived ?? ''],
            'qslVia' => ['qsl_via', $this->qslVia ?? ''],
            'iota' => ['iota', $this->iota ?? ''],
            'power' => ['pwr', $this->power ?? ''],
            'itu' => ['itu', $this->itu ?? 0],
            'waz' => ['waz', $this->waz ?? 0],
            'idCall' => ['idcall', $resolvedIdCall ?? ''],
            'lotwSentDate' => ['lotw_qslsdate', $this->lotwSentDate],
            'lotwReceivedDate' => ['lotw_qslrdate', $this->lotwReceivedDate],
            'lotwSent' => ['lotw_qsls', $this->lotwSent ?? ''],
            'lotwReceived' => ['lotw_qslr', $this->lotwReceived ?? ''],
            'continent' => ['cont', $this->continent ?? ''],
            'qslSentDate' => ['qsls_date', $this->qslSentDate],
            'qslReceivedDate' => ['qslr_date', $this->qslReceivedDate],
            'clubNumber1' => ['club_nr1', $this->clubNumber1 ?? ''],
            'clubNumber2' => ['club_nr2', $this->clubNumber2 ?? ''],
            'clubNumber3' => ['club_nr3', $this->clubNumber3 ?? ''],
            'clubNumber4' => ['club_nr4', $this->clubNumber4 ?? ''],
            'clubNumber5' => ['club_nr5', $this->clubNumber5 ?? ''],
            'eqslSent' => ['eqsl_qsl_sent', $this->eqslSent ?? ''],
            'eqslSentDate' => ['eqsl_qslsdate', $this->eqslSentDate],
            'eqslReceived' => ['eqsl_qsl_rcvd', $this->eqslReceived ?? ''],
            'eqslReceivedDate' => ['eqsl_qslrdate', $this->eqslReceivedDate],
            'receiveFrequency' => ['rxfreq', $this->receiveFrequency],
            'satellite' => ['satellite', $this->satellite ?? ''],
            'propagationMode' => ['prop_mode', $this->propagationMode ?? ''],
            'stx' => ['stx', $this->stx],
            'srx' => ['srx', $this->srx],
            'stxString' => ['stx_string', $this->stxString],
            'srxString' => ['srx_string', $this->srxString],
            'contestName' => ['contestname', $this->contestName],
            'dok' => ['dok', $this->dok ?? ''],
            'operator' => ['operator', $this->operator ?? ''],
            'myLocator' => ['my_loc', $this->myLocator ?? ''],
            'qsoDxcc' => ['qso_dxcc', $this->qsoDxcc ?? 0],
            'profileId' => ['profile', $this->profileId ?? 0],
        ];

        foreach ($mapping as $field => [$column, $value]) {
            if ($this->hasField($field)) {
                $data[$column] = $value;
            }
        }

        if (!$this->hasField('idCall') && $resolvedIdCall !== null && $this->hasField('callsign')) {
            $data['idcall'] = $resolvedIdCall;
        }

        return $data;
    }

    private function resolveIdCallForWrite(): ?string
    {
        if ($this->idCall !== null) {
            return $this->idCall;
        }

        if ($this->callsign === null) {
            return null;
        }

        return CallsignIdResolver::resolve($this->callsign);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, list<string>> $fieldErrors
     */
    private static function normalizeDateField(array $payload, string $field, bool $required, array &$fieldErrors): ?string
    {
        if (!array_key_exists($field, $payload)) {
            if ($required) {
                self::addFieldError($fieldErrors, $field, 'This field is required.');
            }

            return null;
        }

        $value = $payload[$field];

        if (!is_string($value)) {
            self::addFieldError($fieldErrors, $field, 'This field must be a string in Y-m-d format.');
            return null;
        }

        $value = trim($value);
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);

        if ($date === false || $date->format('Y-m-d') !== $value) {
            self::addFieldError($fieldErrors, $field, 'This field must use Y-m-d format.');
            return null;
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, list<string>> $fieldErrors
     */
    private static function normalizeTimeField(array $payload, string $field, bool $required, array &$fieldErrors): ?string
    {
        if (!array_key_exists($field, $payload)) {
            if ($required) {
                self::addFieldError($fieldErrors, $field, 'This field is required.');
            }

            return null;
        }

        $value = $payload[$field];

        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            self::addFieldError($fieldErrors, $field, 'This field must be a string in HH:MM format.');
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value)) {
            self::addFieldError($fieldErrors, $field, 'This field must use HH:MM format.');
            return null;
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, list<string>> $fieldErrors
     */
    private static function normalizeOptionalDateField(array $payload, string $field, array &$fieldErrors): ?string
    {
        if (!array_key_exists($field, $payload)) {
            return null;
        }

        $value = $payload[$field];

        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            self::addFieldError($fieldErrors, $field, 'This field must be a string in Y-m-d format or null.');
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);

        if ($date === false || $date->format('Y-m-d') !== $value) {
            self::addFieldError($fieldErrors, $field, 'This field must use Y-m-d format.');
            return null;
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, list<string>> $fieldErrors
     */
    private static function normalizeRequiredString(
        array $payload,
        string $field,
        int $maxLength,
        bool $required,
        bool $uppercase,
        array &$fieldErrors,
    ): ?string {
        if (!array_key_exists($field, $payload)) {
            if ($required) {
                self::addFieldError($fieldErrors, $field, 'This field is required.');
            }

            return null;
        }

        $value = $payload[$field];

        if (!is_string($value)) {
            self::addFieldError($fieldErrors, $field, 'This field must be a string.');
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            self::addFieldError($fieldErrors, $field, 'This field must not be empty.');
            return null;
        }

        if (mb_strlen($value) > $maxLength) {
            self::addFieldError($fieldErrors, $field, sprintf('This field must be at most %d characters long.', $maxLength));
        }

        return $uppercase ? strtoupper($value) : $value;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, list<string>> $fieldErrors
     */
    private static function normalizeOptionalString(
        array $payload,
        string $field,
        int $maxLength,
        bool $uppercase,
        array &$fieldErrors,
    ): ?string {
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

        if ($value === '') {
            return null;
        }

        return $uppercase ? strtoupper($value) : $value;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, list<string>> $fieldErrors
     */
    private static function normalizeFrequency(array $payload, bool $required, array &$fieldErrors): ?float
    {
        if (!array_key_exists('frequency', $payload)) {
            if ($required) {
                self::addFieldError($fieldErrors, 'frequency', 'This field is required.');
            }

            return null;
        }

        $value = $payload['frequency'];

        if (!is_int($value) && !is_float($value)) {
            self::addFieldError($fieldErrors, 'frequency', 'This field must be a number.');
            return null;
        }

        $normalized = (float) $value;

        if ($normalized <= 0) {
            self::addFieldError($fieldErrors, 'frequency', 'This field must be greater than 0.');
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, list<string>> $fieldErrors
     */
    private static function normalizeOptionalFrequency(array $payload, string $field, array &$fieldErrors): ?float
    {
        if (!array_key_exists($field, $payload)) {
            return null;
        }

        $value = $payload[$field];

        if ($value === null) {
            return null;
        }

        if (!is_int($value) && !is_float($value)) {
            self::addFieldError($fieldErrors, $field, 'This field must be a number or null.');
            return null;
        }

        $normalized = (float) $value;

        if ($normalized <= 0) {
            self::addFieldError($fieldErrors, $field, 'This field must be greater than 0.');
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, list<string>> $fieldErrors
     */
    private static function normalizeOptionalInteger(array $payload, string $field, array &$fieldErrors): ?int
    {
        if (!array_key_exists($field, $payload)) {
            return null;
        }

        $value = $payload[$field];

        if ($value === null) {
            return null;
        }

        if (!is_int($value)) {
            self::addFieldError($fieldErrors, $field, 'This field must be an integer or null.');
            return null;
        }

        if ($value < 0) {
            self::addFieldError($fieldErrors, $field, 'This field must be greater than or equal to 0.');
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
