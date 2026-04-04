<?php

declare(strict_types=1);

namespace App\Api\Profile;

final readonly class ProfileView
{
    public function __construct(
        public int $id,
        public int $number,
        public ?string $locator,
        public ?string $qth,
        public ?string $rig,
        public ?string $remarks,
        public bool $visible,
    ) {
    }

    /**
     * @return array<string, int|string|bool|null>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'locator' => $this->locator,
            'qth' => $this->qth,
            'rig' => $this->rig,
            'remarks' => $this->remarks,
            'visible' => $this->visible,
        ];
    }
}
