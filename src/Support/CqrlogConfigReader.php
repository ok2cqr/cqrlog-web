<?php

declare(strict_types=1);

namespace App\Support;

use Dibi\Connection;

/**
 * Reads sections of the desktop CQRLOG INI configuration stored in `cqrlog_config.config_file`.
 */
final readonly class CqrlogConfigReader
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @return array<string, string> non-empty values of the section, keyed by INI key
     */
    public function readSection(string $sectionName): array
    {
        $config = $this->connection->fetchSingle(
            'SELECT config_file
            FROM cqrlog_config
            ORDER BY id_cqrlog__config DESC
            LIMIT 1',
        );

        if (!is_string($config) || trim($config) === '') {
            return [];
        }

        $config = str_replace(["\\r\\n", "\\n", "\\r"], ["\n", "\n", "\r"], $config);

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
}
