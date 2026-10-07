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

final class StationControllerTest extends WebTestCase
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
        $this->connection->query('TRUNCATE TABLE cqrlog_config');
    }

    #[Test]
    public function detailReturnsStationSectionOfCqrlogConfig(): void
    {
        $this->connection->insert('cqrlog_config', [
            'config_file' => <<<INI
[NewQSO]
Call=WRONG
[Station]
Call=ok2cqr
Name=Petr
QTH=Neratovice
LOC=jo70gg
Email=
[Program]
Call=ALSO-WRONG
INI,
        ])->execute();

        $this->client->request('GET', '/api/station');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertSame([
            'callsign' => 'OK2CQR',
            'name' => 'Petr',
            'qth' => 'Neratovice',
            'locator' => 'JO70GG',
        ], $this->decodeResponse());
    }

    #[Test]
    public function detailReturnsNullsWhenConfigIsMissing(): void
    {
        $this->client->request('GET', '/api/station');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertSame([
            'callsign' => null,
            'name' => null,
            'qth' => null,
            'locator' => null,
        ], $this->decodeResponse());
    }

    #[Test]
    public function detailRequiresAuthentication(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/api/station');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(): array
    {
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        return $data;
    }
}
