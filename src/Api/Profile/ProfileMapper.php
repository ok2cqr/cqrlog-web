<?php

declare(strict_types=1);

namespace App\Api\Profile;

use Dibi\Row;

final class ProfileMapper
{
    public function map(Row $row): ProfileView
    {
        return new ProfileView(
            id: (int) $row->id_profiles,
            number: (int) $row->nr,
            locator: $this->normalizeOptionalString($row->locator),
            qth: $this->normalizeOptionalString($row->qth),
            rig: $this->normalizeOptionalString($row->rig),
            remarks: $this->normalizeOptionalString($row->remarks),
            visible: (int) $row->visible === 1,
        );
    }

    private function normalizeOptionalString(?string $value): ?string
    {
        $value = $value === null ? null : trim($value);

        return $value === '' ? null : $value;
    }
}
