<?php

declare(strict_types=1);

namespace App\Support;

final class CallsignIdResolver
{
    /**
     * @var list<string>
     */
    private const CALLSIGN_EXCEPTIONS = [
        '1B', '1C', '1D', '1E', '1F', '1G', '1H', '1I', '1J', '1K', '1L', '1M', '1N', '1O', '1P', '1Q', '1R',
        '1T', '1U', '1V', '1W', '1X', '1Y', '2K', 'AS', 'AT', 'AU', 'AV', 'BF', 'BL', 'BM', 'BS', 'DA', 'DB',
        'DC', 'DD', 'DE', 'DF', 'DG', 'DH', 'DI', 'DJ', 'DK', 'DM', 'DN', 'DO', 'DP', 'DQ', 'DR', 'FF', 'FN',
        'GC', 'JE', 'JP', 'JR', 'JS', 'LH', 'LP', 'LS', 'LT', 'LV', 'LW', 'MA', 'MG', 'MR', 'MT', 'MY', 'ND',
        'RA', 'RD', 'RE', 'RI', 'RT', 'RU', 'RV', 'RW', 'RZ', 'SA', 'SJ', 'TC', 'TS', 'XA', 'QRP', 'QRPP', 'P',
        'M', 'MM', 'AM',
    ];

    public static function normalize(string $callsign): string
    {
        return strtoupper(trim($callsign));
    }

    public static function resolve(string $callsign): string
    {
        $callsign = self::normalize($callsign);
        $parts = explode('/', $callsign);

        if (count($parts) <= 1) {
            return $callsign;
        }

        if (self::isCallsignException($parts[1])) {
            return $parts[0];
        }

        if (strlen($parts[0]) > strlen($parts[1])) {
            return $parts[0];
        }

        return $parts[1];
    }

    private static function isCallsignException(string $callsignPart): bool
    {
        if (!in_array($callsignPart, self::CALLSIGN_EXCEPTIONS, true)) {
            return false;
        }

        return strlen($callsignPart) <= 3 || ctype_alpha($callsignPart);
    }
}
