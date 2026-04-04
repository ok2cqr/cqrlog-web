<?php

declare(strict_types=1);

namespace App\Api\Note;

use Dibi\Row;

final class NoteMapper
{
    public function map(Row $row): NoteView
    {
        return new NoteView(
            id: (int) $row->id_notes,
            callsign: $this->normalizeOptionalString($row->callsign),
            remarks: $this->normalizeOptionalString($row->longremarks),
        );
    }

    private function normalizeOptionalString(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        return $value === '' ? null : $value;
    }
}
