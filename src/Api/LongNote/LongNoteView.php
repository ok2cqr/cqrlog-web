<?php

declare(strict_types=1);

namespace App\Api\LongNote;

final readonly class LongNoteView
{
    public function __construct(
        public int $id,
        public ?string $note,
    ) {
    }

    /**
     * @return array<string, int|string|null>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'note' => $this->note,
        ];
    }
}
