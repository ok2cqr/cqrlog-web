<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Tests\Support\AuthenticatesClient;
use App\Tests\Support\UsesTestDatabase;
use Dibi\Connection;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class LongNoteControllerTest extends WebTestCase
{
    use AuthenticatesClient;
    use UsesTestDatabase;

    private KernelBrowser $client;
    private Connection $connection;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->authenticateClient($this->client);

        /** @var Connection $connection */
        $connection = static::getContainer()->get(Connection::class);
        $this->connection = $connection;
        $this->assertUsingSafeTestDatabase($this->connection);
        $this->connection->query('TRUNCATE TABLE long_note');
    }

    #[Test]
    public function listReturnsMappedLongNotes(): void
    {
        $firstId = $this->insertLongNote(['note' => 'First long note']);
        $secondId = $this->insertLongNote(['note' => '']);

        $this->client->request('GET', '/api/longNotes');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array{items: list<array<string, mixed>>, totalCount: int} $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(2, $payload['totalCount']);
        self::assertCount(2, $payload['items']);
        self::assertSame($firstId, $payload['items'][0]['id']);
        self::assertSame('First long note', $payload['items'][0]['note']);
        self::assertSame($secondId, $payload['items'][1]['id']);
        self::assertNull($payload['items'][1]['note']);
        self::assertArrayNotHasKey('id_long_note', $payload['items'][0]);
    }

    #[Test]
    public function detailReturnsMappedLongNote(): void
    {
        $id = $this->insertLongNote(['note' => 'Detailed long note']);

        $this->client->request('GET', sprintf('/api/longNotes/%d', $id));

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame([
            'id' => $id,
            'note' => 'Detailed long note',
        ], $payload);
    }

    #[Test]
    public function createReturnsCreatedLongNote(): void
    {
        $this->client->jsonRequest('POST', '/api/longNotes', [
            'note' => 'Created long note',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsInt($payload['id']);
        self::assertSame('Created long note', $payload['note']);

        $row = $this->connection->fetch(
            'SELECT id_long_note, note FROM long_note WHERE id_long_note = %i',
            $payload['id'],
        );

        self::assertNotNull($row);
        self::assertSame('Created long note', $row->note);
    }

    #[Test]
    public function patchUpdatesNote(): void
    {
        $id = $this->insertLongNote(['note' => 'Old note']);

        $this->client->jsonRequest('PATCH', sprintf('/api/longNotes/%d', $id), [
            'note' => null,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame($id, $payload['id']);
        self::assertNull($payload['note']);
    }

    #[Test]
    public function createValidationFailureReturnsFieldErrors(): void
    {
        $this->client->jsonRequest('POST', '/api/longNotes', [
            'note' => '   ',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('validation_failed', $payload['error']['code']);
        self::assertSame(['This field must not be empty.'], $payload['error']['details']['fields']['note']);
    }

    #[Test]
    public function missingLongNoteReturnsJsonNotFound(): void
    {
        $this->client->request('GET', '/api/longNotes/999999');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('not_found', $payload['error']['code']);
        self::assertSame('Long note with id 999999 was not found.', $payload['error']['message']);
    }

    /**
     * @param array<string, string|null> $overrides
     */
    private function insertLongNote(array $overrides = []): int
    {
        $data = array_replace([
            'note' => 'Default long note',
        ], $overrides);

        $this->connection->insert('long_note', $data)->execute();

        return $this->connection->getInsertId();
    }
}
