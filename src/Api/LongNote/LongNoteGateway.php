<?php

declare(strict_types=1);

namespace App\Api\LongNote;

use Dibi\Connection;
use Dibi\Row;

final readonly class LongNoteGateway
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @return list<Row>
     */
    public function fetchAll(): array
    {
        return $this->connection->fetchAll(
            'SELECT id_long_note, note
            FROM long_note
            ORDER BY id_long_note ASC',
        );
    }

    public function fetchById(int $id): ?Row
    {
        return $this->connection->fetch(
            'SELECT id_long_note, note
            FROM long_note
            WHERE id_long_note = %i',
            $id,
        );
    }

    public function create(LongNoteInput $input): Row
    {
        $this->connection->insert('long_note', $input->toDatabaseWriteData())->execute();

        /** @var Row $row */
        $row = $this->fetchById($this->connection->getInsertId());

        return $row;
    }

    public function update(int $id, LongNoteInput $input): ?Row
    {
        $this->connection
            ->update('long_note', $input->toDatabaseWriteData())
            ->where('id_long_note = %i', $id)
            ->execute();

        return $this->fetchById($id);
    }
}
