<?php

declare(strict_types=1);

namespace App\Api\CallsignContext;

final readonly class RecentQsoView
{
    public function __construct(
        public int $id,
        public string $qsoDate,
        public string $timeOn,
        public ?string $timeOff,
        public string $callsign,
        public ?string $band,
        public string $mode,
    ) {
    }

    /**
     * @return array<string, int|string|null>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'qsoDate' => $this->qsoDate,
            'timeOn' => $this->timeOn,
            'timeOff' => $this->timeOff,
            'callsign' => $this->callsign,
            'band' => $this->band,
            'mode' => $this->mode,
        ];
    }
}
