<?php

declare(strict_types=1);

namespace App\Api\LongNote;

use Dibi\Row;

final class LongNoteMapper
{
    public function map(Row $row): LongNoteView
    {
        $note = $row->note;
        $note = $note === null ? null : trim($note);

        return new LongNoteView(
            id: (int) $row->id_long_note,
            note: $note === '' ? null : $note,
        );
    }
}
