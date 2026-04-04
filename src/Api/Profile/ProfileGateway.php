<?php

declare(strict_types=1);

namespace App\Api\Profile;

use Dibi\Connection;
use Dibi\Row;

final readonly class ProfileGateway
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
            'SELECT id_profiles, nr, locator, qth, rig, remarks, visible
            FROM profiles
            ORDER BY nr ASC, id_profiles ASC',
        );
    }

    public function fetchById(int $id): ?Row
    {
        return $this->connection->fetch(
            'SELECT id_profiles, nr, locator, qth, rig, remarks, visible
            FROM profiles
            WHERE id_profiles = %i',
            $id,
        );
    }

    public function create(ProfileInput $input): Row
    {
        $this->connection->insert('profiles', $input->toDatabaseWriteData())->execute();

        /** @var Row $row */
        $row = $this->fetchById($this->connection->getInsertId());

        return $row;
    }

    public function update(int $id, ProfileInput $input): ?Row
    {
        $this->connection
            ->update('profiles', $input->toDatabaseWriteData())
            ->where('id_profiles = %i', $id)
            ->execute();

        return $this->fetchById($id);
    }

    public function delete(int $id): void
    {
        $this->connection
            ->delete('profiles')
            ->where('id_profiles = %i', $id)
            ->execute();
    }
}
