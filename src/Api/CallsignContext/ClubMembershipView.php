<?php

declare(strict_types=1);

namespace App\Api\CallsignContext;

final readonly class ClubMembershipView
{
    public function __construct(
        public int $slot,
        public string $name,
        public string $number,
        public ?string $fromDate,
        public ?string $toDate,
    ) {
    }

    /**
     * @return array<string, int|string|null>
     */
    public function toArray(): array
    {
        return [
            'slot' => $this->slot,
            'name' => $this->name,
            'number' => $this->number,
            'fromDate' => $this->fromDate,
            'toDate' => $this->toDate,
        ];
    }
}
