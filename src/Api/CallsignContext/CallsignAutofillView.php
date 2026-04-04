<?php

declare(strict_types=1);

namespace App\Api\CallsignContext;

final readonly class CallsignAutofillView
{
    public function __construct(
        public ?string $name,
        public ?string $qth,
        public ?string $award,
        public ?string $qslVia,
        public ?string $state,
        public ?string $county,
        public ?int $waz,
        public ?int $itu,
        public ?string $grid,
        public ?string $iota,
    ) {
    }

    /**
     * @return array<string, int|string|null>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'qth' => $this->qth,
            'award' => $this->award,
            'qslVia' => $this->qslVia,
            'state' => $this->state,
            'county' => $this->county,
            'waz' => $this->waz,
            'itu' => $this->itu,
            'grid' => $this->grid,
            'iota' => $this->iota,
        ];
    }
}
