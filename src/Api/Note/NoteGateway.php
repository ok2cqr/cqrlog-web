<?php

declare(strict_types=1);

namespace App\Api\Note;

use Dibi\Connection;
use Dibi\Row;

final readonly class NoteGateway
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
            'SELECT id_notes, callsign, longremarks
            FROM notes
            ORDER BY callsign ASC, id_notes ASC',
        );
    }

    public function fetchById(int $id): ?Row
    {
        return $this->connection->fetch(
            'SELECT id_notes, callsign, longremarks
            FROM notes
            WHERE id_notes = %i',
            $id,
        );
    }

    public function create(NoteInput $input): Row
    {
        $this->connection->insert('notes', $input->toDatabaseWriteData())->execute();

        /** @var Row $row */
        $row = $this->fetchById($this->connection->getInsertId());

        return $row;
    }

    public function update(int $id, NoteInput $input): ?Row
    {
        $this->connection
            ->update('notes', $input->toDatabaseWriteData())
            ->where('id_notes = %i', $id)
            ->execute();

        return $this->fetchById($id);
    }
}
