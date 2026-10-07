<?php

declare(strict_types=1);

namespace App\Api\Station;

use App\Support\CqrlogConfigReader;

final readonly class StationGateway
{
    public function __construct(
        private CqrlogConfigReader $configReader,
    ) {
    }

    /**
     * @return array<string, string> `[Station]` section of the CQRLOG configuration
     */
    public function fetch(): array
    {
        return $this->configReader->readSection('Station');
    }
}
