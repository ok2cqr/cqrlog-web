<?php

declare(strict_types=1);

namespace App\Api\Dxcc;

use App\Api\Exception\UpstreamLookupException;
use Dibi\Connection;

final readonly class DxccGateway
{
    private const ENDPOINT = 'https://www.hamqth.com/dxcc_json.php?callsign=';

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function fetch(DxccQuery $query): DxccView
    {
        $ch = curl_init(self::ENDPOINT . rawurlencode($query->callsign));

        if ($ch === false) {
            throw new UpstreamLookupException('DXCC');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_FAILONERROR => false,
            CURLOPT_USERAGENT => 'CQRLOG-REST/0.1 DXCC lookup',
        ]);

        $response = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_errno($ch);
        curl_close($ch);

        if ($error !== 0 || !is_string($response) || $statusCode < 200 || $statusCode >= 300) {
            throw new UpstreamLookupException('DXCC');
        }

        /** @var mixed $payload */
        $payload = json_decode($response, true);

        if (!is_array($payload)) {
            throw new UpstreamLookupException('DXCC');
        }

        $adif = $this->normalizeOptionalInt($payload['adif'] ?? null);

        return new DxccView(
            callsign: $query->callsign,
            name: $this->normalizeOptionalString($payload['name'] ?? null),
            details: $this->normalizeOptionalString($payload['details'] ?? null),
            continent: $this->normalizeOptionalString($payload['continent'] ?? null),
            utc: $this->normalizeOptionalString($payload['utc'] ?? null),
            waz: $this->normalizeOptionalInt($payload['waz'] ?? null),
            itu: $this->normalizeOptionalInt($payload['itu'] ?? null),
            lat: $this->normalizeOptionalString($payload['lat'] ?? null),
            lng: $this->normalizeOptionalString($payload['lng'] ?? null),
            adif: $adif,
            dxccRef: $this->resolveDxccRef($adif),
        );
    }

    private function resolveDxccRef(?int $adif): ?string
    {
        if ($adif === null) {
            return null;
        }

        $value = $this->connection->fetchSingle(
            'SELECT dxcc_ref
            FROM dxcc_id
            WHERE adif = %i
            ORDER BY dxcc_ref ASC
            LIMIT 1',
            $adif,
        );

        if (!is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
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
        if (!is_scalar($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized === 0 ? null : $normalized;
    }
}
