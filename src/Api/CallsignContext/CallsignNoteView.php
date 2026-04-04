<?php

declare(strict_types=1);

namespace App\Api\CallsignContext;

final readonly class CallsignNoteView
{
    public function __construct(
        public int $id,
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
            'remarks' => $this->remarks,
        ];
    }
}
