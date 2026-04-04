<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Tests\Support\UsesTestDatabase;
use Dibi\Connection;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ProfileControllerTest extends WebTestCase
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
        $this->connection->query('TRUNCATE TABLE profiles');
    }

    #[Test]
    public function listReturnsMappedProfiles(): void
    {
        $firstId = $this->insertProfile([
            'nr' => 2,
            'locator' => 'JN79',
            'qth' => 'Brno',
            'rig' => 'IC-7300',
            'remarks' => 'Club station',
            'visible' => 1,
        ]);
        $secondId = $this->insertProfile([
            'nr' => 5,
            'locator' => '',
            'qth' => 'Prague',
            'rig' => '',
            'remarks' => '',
            'visible' => 0,
        ]);

        $this->client->request('GET', '/api/profiles');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array{items: list<array<string, mixed>>, totalCount: int} $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(2, $payload['totalCount']);
        self::assertCount(2, $payload['items']);
        self::assertSame($firstId, $payload['items'][0]['id']);
        self::assertSame(2, $payload['items'][0]['number']);
        self::assertSame('JN79', $payload['items'][0]['locator']);
        self::assertSame('Brno', $payload['items'][0]['qth']);
        self::assertSame('IC-7300', $payload['items'][0]['rig']);
        self::assertSame('Club station', $payload['items'][0]['remarks']);
        self::assertTrue($payload['items'][0]['visible']);
        self::assertSame($secondId, $payload['items'][1]['id']);
        self::assertNull($payload['items'][1]['locator']);
        self::assertNull($payload['items'][1]['rig']);
        self::assertNull($payload['items'][1]['remarks']);
        self::assertFalse($payload['items'][1]['visible']);
        self::assertArrayNotHasKey('id_profiles', $payload['items'][0]);
        self::assertArrayNotHasKey('nr', $payload['items'][0]);
    }

    #[Test]
    public function detailReturnsMappedProfile(): void
    {
        $id = $this->insertProfile([
            'nr' => 9,
            'locator' => 'JO70',
            'qth' => 'Olomouc',
            'rig' => 'FT-710',
            'remarks' => 'Portable',
            'visible' => 1,
        ]);

        $this->client->request('GET', sprintf('/api/profiles/%d', $id));

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame([
            'id' => $id,
            'number' => 9,
            'locator' => 'JO70',
            'qth' => 'Olomouc',
            'rig' => 'FT-710',
            'remarks' => 'Portable',
            'visible' => true,
        ], $payload);
    }

    #[Test]
    public function createReturnsCreatedProfile(): void
    {
        $this->client->jsonRequest('POST', '/api/profiles', [
            'number' => 12,
            'locator' => 'JN89',
            'qth' => 'Ostrava',
            'rig' => 'TS-590',
            'remarks' => 'Contest setup',
            'visible' => true,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsInt($payload['id']);
        self::assertSame(12, $payload['number']);
        self::assertSame('JN89', $payload['locator']);
        self::assertSame('Ostrava', $payload['qth']);
        self::assertSame('TS-590', $payload['rig']);
        self::assertSame('Contest setup', $payload['remarks']);
        self::assertTrue($payload['visible']);

        $row = $this->connection->fetch(
            'SELECT id_profiles, nr, locator, qth, rig, remarks, visible FROM profiles WHERE id_profiles = %i',
            $payload['id'],
        );

        self::assertNotNull($row);
        self::assertSame(12, (int) $row->nr);
        self::assertSame('JN89', $row->locator);
    }

    #[Test]
    public function patchUpdatesOnlyProvidedFields(): void
    {
        $id = $this->insertProfile([
            'nr' => 7,
            'locator' => 'JN99',
            'qth' => 'Liberec',
            'rig' => 'KX3',
            'remarks' => 'Initial',
            'visible' => 1,
        ]);

        $this->client->jsonRequest('PATCH', sprintf('/api/profiles/%d', $id), [
            'qth' => 'Pardubice',
            'visible' => false,
            'remarks' => null,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame($id, $payload['id']);
        self::assertSame(7, $payload['number']);
        self::assertSame('JN99', $payload['locator']);
        self::assertSame('Pardubice', $payload['qth']);
        self::assertSame('KX3', $payload['rig']);
        self::assertNull($payload['remarks']);
        self::assertFalse($payload['visible']);
    }

    #[Test]
    public function deleteRemovesProfile(): void
    {
        $id = $this->insertProfile([
            'nr' => 11,
            'locator' => 'JN78',
            'qth' => 'Trebic',
            'rig' => 'IC-705',
            'remarks' => 'Temporary profile',
            'visible' => 1,
        ]);

        $this->client->request('DELETE', sprintf('/api/profiles/%d', $id));

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        self::assertSame('', $this->client->getResponse()->getContent());

        $row = $this->connection->fetch(
            'SELECT id_profiles FROM profiles WHERE id_profiles = %i',
            $id,
        );

        self::assertNull($row);
    }

    #[Test]
    public function createValidationFailureReturnsFieldErrors(): void
    {
        $this->client->jsonRequest('POST', '/api/profiles', [
            'locator' => 123,
            'visible' => 'yes',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('validation_failed', $payload['error']['code']);
        self::assertSame('Request validation failed.', $payload['error']['message']);
        self::assertSame(['This field is required.'], $payload['error']['details']['fields']['number']);
        self::assertSame(['This field must be a string or null.'], $payload['error']['details']['fields']['locator']);
        self::assertSame(['This field must be a boolean.'], $payload['error']['details']['fields']['visible']);
    }

    #[Test]
    public function missingProfileReturnsJsonNotFound(): void
    {
        $this->client->request('GET', '/api/profiles/999999');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('not_found', $payload['error']['code']);
        self::assertSame('Profile with id 999999 was not found.', $payload['error']['message']);
        self::assertSame('Profile', $payload['error']['details']['resource']);
        self::assertSame(999999, $payload['error']['details']['id']);
    }

    /**
     * @param array<string, int|string> $overrides
     */
    private function insertProfile(array $overrides = []): int
    {
        $data = array_replace([
            'nr' => 1,
            'locator' => 'JN88',
            'qth' => 'Default QTH',
            'rig' => 'Default rig',
            'remarks' => 'Default remarks',
            'visible' => 1,
        ], $overrides);

        $this->connection->insert('profiles', $data)->execute();

        return $this->connection->getInsertId();
    }
}
