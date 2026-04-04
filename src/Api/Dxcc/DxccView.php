<?php

declare(strict_types=1);

namespace App\Api\Dxcc;

final readonly class DxccView
{
    public function __construct(
        public string $callsign,
        public ?string $name,
        public ?string $details,
        public ?string $continent,
        public ?string $utc,
        public ?int $waz,
        public ?int $itu,
        public ?string $lat,
        public ?string $lng,
        public ?int $adif,
        public ?string $dxccRef,
    ) {
    }

    /**
     * @return array<string, int|string|null>
     */
    public function toArray(): array
    {
        return [
            'callsign' => $this->callsign,
            'name' => $this->name,
            'details' => $this->details,
            'continent' => $this->continent,
            'utc' => $this->utc,
            'waz' => $this->waz,
            'itu' => $this->itu,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'adif' => $this->adif,
            'dxccRef' => $this->dxccRef,
        ];
    }
}
