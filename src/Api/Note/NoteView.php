<?php

declare(strict_types=1);

namespace App\Api\Note;

final readonly class NoteView
{
    public function __construct(
        public int $id,
        public ?string $callsign,
        public ?string $remarks,
    ) {
    }

    /**
     * @return array<string, int|string|null>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'callsign' => $this->callsign,
            'remarks' => $this->remarks,
        ];
    }
}
