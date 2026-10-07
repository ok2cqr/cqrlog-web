<?php

declare(strict_types=1);

namespace App\Api\Station;

final readonly class StationView
{
    public function __construct(
        public ?string $callsign,
        public ?string $name,
        public ?string $qth,
        public ?string $locator,
    ) {
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'callsign' => $this->callsign,
            'name' => $this->name,
            'qth' => $this->qth,
            'locator' => $this->locator,
        ];
    }
}
