<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Tests\Support\UsesTestDatabase;
use Dibi\Connection;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class NoteControllerTest extends WebTestCase
{
    use UsesTestDatabase;

    private KernelBrowser $client;
    private Connection $connection;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();

        /** @var Connection $connection */
        $connection = static::getContainer()->get(Connection::class);
        $this->connection = $connection;
        $this->assertUsingSafeTestDatabase($this->connection);
        $this->connection->query('TRUNCATE TABLE notes');
    }

    #[Test]
    public function listReturnsMappedNotes(): void
    {
        $firstId = $this->insertNote([
            'callsign' => 'OK1ABC',
            'longremarks' => 'First note',
        ]);
        $secondId = $this->insertNote([
            'callsign' => 'OK2XYZ',
            'longremarks' => '',
        ]);

        $this->client->request('GET', '/api/notes');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array{items: list<array<string, mixed>>, totalCount: int} $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(2, $payload['totalCount']);
        self::assertCount(2, $payload['items']);
        self::assertSame($firstId, $payload['items'][0]['id']);
        self::assertSame('OK1ABC', $payload['items'][0]['callsign']);
        self::assertSame('First note', $payload['items'][0]['remarks']);
        self::assertSame($secondId, $payload['items'][1]['id']);
        self::assertSame('OK2XYZ', $payload['items'][1]['callsign']);
        self::assertNull($payload['items'][1]['remarks']);
        self::assertArrayNotHasKey('id_notes', $payload['items'][0]);
        self::assertArrayNotHasKey('longremarks', $payload['items'][0]);
    }

    #[Test]
    public function detailReturnsMappedNote(): void
    {
        $id = $this->insertNote([
            'callsign' => 'OK1TEST',
            'longremarks' => 'Detailed note',
        ]);

        $this->client->request('GET', sprintf('/api/notes/%d', $id));

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame([
            'id' => $id,
            'callsign' => 'OK1TEST',
            'remarks' => 'Detailed note',
        ], $payload);
    }

    #[Test]
    public function createReturnsCreatedNote(): void
    {
        $this->client->jsonRequest('POST', '/api/notes', [
            'callsign' => 'ok1new',
            'remarks' => 'Created via API',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsInt($payload['id']);
        self::assertSame('OK1NEW', $payload['callsign']);
        self::assertSame('Created via API', $payload['remarks']);

        $row = $this->connection->fetch(
            'SELECT id_notes, callsign, longremarks FROM notes WHERE id_notes = %i',
            $payload['id'],
        );

        self::assertNotNull($row);
        self::assertSame('OK1NEW', $row->callsign);
        self::assertSame('Created via API', $row->longremarks);
    }

    #[Test]
    public function patchUpdatesOnlyProvidedFields(): void
    {
        $id = $this->insertNote([
            'callsign' => 'OK1OLD',
            'longremarks' => 'Old remarks',
        ]);

        $this->client->jsonRequest('PATCH', sprintf('/api/notes/%d', $id), [
            'remarks' => null,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame($id, $payload['id']);
        self::assertSame('OK1OLD', $payload['callsign']);
        self::assertNull($payload['remarks']);
    }

    #[Test]
    public function createValidationFailureReturnsFieldErrors(): void
    {
        $this->client->jsonRequest('POST', '/api/notes', [
            'callsign' => '   ',
            'remarks' => 123,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('validation_failed', $payload['error']['code']);
        self::assertSame(['This field must not be empty.'], $payload['error']['details']['fields']['callsign']);
        self::assertSame(['This field must be a string or null.'], $payload['error']['details']['fields']['remarks']);
    }

    #[Test]
    public function missingNoteReturnsJsonNotFound(): void
    {
        $this->client->request('GET', '/api/notes/999999');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('not_found', $payload['error']['code']);
        self::assertSame('Note with id 999999 was not found.', $payload['error']['message']);
    }

    /**
     * @param array<string, string> $overrides
     */
    private function insertNote(array $overrides = []): int
    {
        $data = array_replace([
            'callsign' => 'OK1AAA',
            'longremarks' => 'Default note',
        ], $overrides);

        $this->connection->insert('notes', $data)->execute();

        return $this->connection->getInsertId();
    }
}
