<?php

declare(strict_types=1);

namespace App\Api\SolarData;

use App\Api\Exception\UpstreamLookupException;

final readonly class SolarDataGateway
{
    private const ENDPOINT = 'https://www.hamqth.com/solar_data1.dat';

    public function fetch(): string
    {
        $ch = curl_init(self::ENDPOINT);

        if ($ch === false) {
            throw new UpstreamLookupException('solar data');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_FAILONERROR => false,
            CURLOPT_USERAGENT => 'CQRLOG-REST/0.1 solar data lookup',
        ]);

        $response = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_errno($ch);
        curl_close($ch);

        if ($error !== 0 || !is_string($response) || $statusCode < 200 || $statusCode >= 300) {
            throw new UpstreamLookupException('solar data');
        }

        $normalized = trim($response);

        if ($normalized === '') {
            throw new UpstreamLookupException('solar data');
        }

        return $normalized;
    }
}
