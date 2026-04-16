<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Tests\Support\AuthenticatesClient;
use App\Tests\Support\UsesTestDatabase;
use DateTimeImmutable;
use Dibi\Connection;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CallsignContextControllerTest extends WebTestCase
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
        $this->connection->query('TRUNCATE TABLE notes');
        $this->connection->query('TRUNCATE TABLE club1');
        $this->connection->query('TRUNCATE TABLE club2');
        $this->connection->query('TRUNCATE TABLE club3');
        $this->connection->query('TRUNCATE TABLE club4');
        $this->connection->query('TRUNCATE TABLE club5');
        $this->connection->query('TRUNCATE TABLE cqrlog_config');
        $this->connection->query('DELETE FROM log_changes');
        $this->connection->query('DELETE FROM cqrlog_main');
    }

    #[Test]
    public function detailReturnsNoteAndClubMembershipsForCallsign(): void
    {
        $noteId = $this->insertNote([
            'callsign' => 'OK1ABC',
            'longremarks' => 'Known portable station',
        ]);

        $this->connection->insert('cqrlog_config', [
            'config_file' => <<<INI
[Clubs]
club1name = "Czech DX Club"
club2name = "European CW Club"
INI,
        ])->execute();

        $this->connection->insert('club1', [
            'club_nr' => 'CDX-123',
            'clubcall' => 'OK1ABC',
            'fromdate' => '2020-01-01',
            'todate' => null,
        ])->execute();
        $this->connection->insert('club2', [
            'club_nr' => 'ECWC-456',
            'clubcall' => 'ok1abc',
            'fromdate' => '2024-01-01',
            'todate' => '2026-12-31',
        ])->execute();
        $this->connection->insert('club3', [
            'club_nr' => 'EXPIRED-789',
            'clubcall' => 'OK1ABC',
            'fromdate' => '2010-01-01',
            'todate' => '2020-12-31',
        ])->execute();
        $recentId = $this->insertLogEntry([
            'qsodate' => '2026-03-20',
            'time_on' => '12:30',
            'time_off' => '12:33',
            'callsign' => 'OK1ABC',
            'idcall' => 'OK1ABC',
            'freq' => 7.0250,
            'mode' => 'CW',
            'name' => 'Petr',
            'qth' => 'Brno',
            'award' => 'WAC',
            'qsl_via' => 'Bureau',
            'state' => 'JM',
            'county' => 'Brno',
            'waz' => 15,
            'itu' => 28,
            'loc' => 'JN89',
            'iota' => 'EU-123',
        ]);

        $this->client->request('GET', '/api/callsignContext?callsign=ok1abc&qsoDate=2026-03-21');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('OK1ABC', $payload['callsign']);
        self::assertSame('OK1ABC', $payload['idCall']);
        self::assertSame([
            'id' => $noteId,
            'remarks' => 'Known portable station',
        ], $payload['note']);
        self::assertSame([
            [
                'slot' => 1,
                'name' => 'Czech DX Club',
                'number' => 'CDX-123',
                'fromDate' => '2020-01-01',
                'toDate' => null,
            ],
            [
                'slot' => 2,
                'name' => 'European CW Club',
                'number' => 'ECWC-456',
                'fromDate' => '2024-01-01',
                'toDate' => '2026-12-31',
            ],
        ], $payload['clubs']);
        self::assertSame(1, $payload['recentQsoCount']);
        self::assertSame([
            [
                'id' => $recentId,
                'qsoDate' => '2026-03-20',
                'timeOn' => '12:30',
                'timeOff' => '12:33',
                'callsign' => 'OK1ABC',
                'band' => null,
                'mode' => 'CW',
            ],
        ], $payload['recentQsos']);
        self::assertSame([
            'name' => 'Petr',
            'qth' => 'Brno',
            'award' => 'WAC',
            'qslVia' => 'Bureau',
            'state' => 'JM',
            'county' => 'Brno',
            'waz' => 15,
            'itu' => 28,
            'grid' => 'JN89',
            'iota' => 'EU-123',
        ], $payload['autofill']);
    }

    #[Test]
    public function detailFallsBackToDefaultClubNamesAndHandlesMissingData(): void
    {
        $this->connection->insert('club4', [
            'club_nr' => 'FALLBACK-001',
            'clubcall' => 'OK2XYZ',
            'fromdate' => null,
            'todate' => null,
        ])->execute();

        $this->client->request('GET', '/api/callsignContext?callsign=OK2XYZ');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('OK2XYZ', $payload['callsign']);
        self::assertSame('OK2XYZ', $payload['idCall']);
        self::assertNull($payload['note']);
        self::assertSame([
            [
                'slot' => 4,
                'name' => 'Club 4',
                'number' => 'FALLBACK-001',
                'fromDate' => null,
                'toDate' => null,
            ],
        ], $payload['clubs']);
        self::assertSame(0, $payload['recentQsoCount']);
        self::assertSame([], $payload['recentQsos']);
        self::assertSame([
            'name' => null,
            'qth' => null,
            'award' => null,
            'qslVia' => null,
            'state' => null,
            'county' => null,
            'waz' => null,
            'itu' => null,
            'grid' => null,
            'iota' => null,
        ], $payload['autofill']);
    }

    #[Test]
    public function detailUsesClubNamesFromCqrlogClubsSection(): void
    {
        $this->connection->insert('cqrlog_config', [
            'config_file' => <<<INI
[Clubs]
First=FOC;The First Class C.W. Operators' Club
Second=FOC_NAMES;FOC names
Third=CWOPS;The CW Operators' Club
INI,
        ])->execute();

        $this->connection->insert('club1', [
            'club_nr' => 'FOC-123',
            'clubcall' => 'OK1FOC',
            'fromdate' => '2020-01-01',
            'todate' => null,
        ])->execute();
        $this->connection->insert('club3', [
            'club_nr' => 'CW-789',
            'clubcall' => 'OK1FOC',
            'fromdate' => '2020-01-01',
            'todate' => null,
        ])->execute();

        $this->client->request('GET', '/api/callsignContext?callsign=OK1FOC&qsoDate=2026-03-21');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('FOC', $payload['clubs'][0]['name']);
        self::assertSame('CWOPS', $payload['clubs'][1]['name']);
    }

    #[Test]
    public function detailUsesClubNamesFromEscapedIniStoredInDatabase(): void
    {
        $this->connection->insert('cqrlog_config', [
            'config_file' => '[Reminder]\nfoo=1\n\n[Clubs]\nFirst=FOC;The First Class C.W. Operators\' Club\nThird=CWOPS;The CW Operators\' Club',
        ])->execute();

        $this->connection->insert('club1', [
            'club_nr' => 'FOC-123',
            'clubcall' => 'OK1ESC',
            'fromdate' => '2020-01-01',
            'todate' => null,
        ])->execute();
        $this->connection->insert('club3', [
            'club_nr' => 'CW-789',
            'clubcall' => 'OK1ESC',
            'fromdate' => '2020-01-01',
            'todate' => null,
        ])->execute();

        $this->client->request('GET', '/api/callsignContext?callsign=OK1ESC&qsoDate=2026-03-21');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('FOC', $payload['clubs'][0]['name']);
        self::assertSame('CWOPS', $payload['clubs'][1]['name']);
    }

    #[Test]
    public function detailUsesIdCallNormalizationForRecentQsoAndClubLookup(): void
    {
        $this->connection->insert('club1', [
            'club_nr' => 'BASE-001',
            'clubcall' => 'OK1ABC',
            'fromdate' => '2020-01-01',
            'todate' => '2030-01-01',
        ])->execute();
        $recentId = $this->insertLogEntry([
            'qsodate' => '2026-03-20',
            'time_on' => '14:00',
            'callsign' => 'OK1ABC',
            'freq' => 14.0250,
            'mode' => 'CW',
            'idcall' => 'OK1ABC',
        ]);

        $this->client->request('GET', '/api/callsignContext?callsign=OK1ABC/P&qsoDate=2026-03-21');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('OK1ABC/P', $payload['callsign']);
        self::assertSame('OK1ABC', $payload['idCall']);
        self::assertSame('BASE-001', $payload['clubs'][0]['number']);
        self::assertSame($recentId, $payload['recentQsos'][0]['id']);
    }

    #[Test]
    public function detailUsesOnlyIdCallForRecentQsoLookup(): void
    {
        $this->insertLogEntry([
            'qsodate' => '2026-03-20',
            'time_on' => '14:00',
            'callsign' => 'OK1ABC',
            'freq' => 14.0250,
            'mode' => 'CW',
            'idcall' => '',
        ]);

        $this->client->request('GET', '/api/callsignContext?callsign=OK1ABC/P&qsoDate=2026-03-21');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(0, $payload['recentQsoCount']);
        self::assertSame([], $payload['recentQsos']);
    }

    #[Test]
    public function detailAutofillsQthAndGridOnlyFromExactCallsignRows(): void
    {
        $this->insertLogEntry([
            'qsodate' => '2026-03-20',
            'time_on' => '10:00',
            'callsign' => 'OK1ABC',
            'idcall' => 'OK1ABC',
            'freq' => 7.0250,
            'mode' => 'CW',
            'qth' => 'Brno',
            'loc' => 'JN88',
            'award' => 'WAC',
        ]);
        $this->insertLogEntry([
            'qsodate' => '2026-03-21',
            'time_on' => '11:00',
            'callsign' => 'OK1ABC/P',
            'idcall' => 'OK1ABC',
            'freq' => 7.0250,
            'mode' => 'CW',
            'name' => 'Portable Op',
            'qth' => 'Hilltop',
            'award' => 'SOTA',
            'qsl_via' => 'Direct',
            'state' => 'JM',
            'county' => 'Brno',
            'waz' => 15,
            'itu' => 28,
            'loc' => 'JN89',
            'iota' => 'EU-001',
        ]);

        $this->client->request('GET', '/api/callsignContext?callsign=OK1ABC/P&qsoDate=2026-03-21');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame([
            'name' => 'Portable Op',
            'qth' => 'Hilltop',
            'award' => 'SOTA',
            'qslVia' => 'Direct',
            'state' => 'JM',
            'county' => 'Brno',
            'waz' => 15,
            'itu' => 28,
            'grid' => 'JN89',
            'iota' => 'EU-001',
        ], $payload['autofill']);
    }

    #[Test]
    public function detailDoesNotAutofillQthAndGridFromDifferentCallsignVariant(): void
    {
        $this->insertLogEntry([
            'qsodate' => '2026-03-20',
            'time_on' => '10:00',
            'callsign' => 'OK1ABC',
            'idcall' => 'OK1ABC',
            'freq' => 7.0250,
            'mode' => 'CW',
            'name' => 'Base Op',
            'qth' => 'Brno',
            'loc' => 'JN88',
            'award' => 'WAC',
            'qsl_via' => 'Bureau',
        ]);

        $this->client->request('GET', '/api/callsignContext?callsign=OK1ABC/P&qsoDate=2026-03-21');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame([
            'name' => 'Base Op',
            'qth' => null,
            'award' => 'WAC',
            'qslVia' => 'Bureau',
            'state' => null,
            'county' => null,
            'waz' => null,
            'itu' => null,
            'grid' => null,
            'iota' => null,
        ], $payload['autofill']);
    }

    #[Test]
    public function detailReturnsOnlySingleActiveClubMembershipPerSlot(): void
    {
        $this->connection->insert('club1', [
            'club_nr' => 'OLDER-001',
            'clubcall' => 'OK1AAA',
            'fromdate' => '2020-01-01',
            'todate' => '2027-12-31',
        ])->execute();
        $this->connection->insert('club1', [
            'club_nr' => 'CURRENT-002',
            'clubcall' => 'OK1AAA',
            'fromdate' => '2025-01-01',
            'todate' => null,
        ])->execute();

        $this->client->request('GET', '/api/callsignContext?callsign=OK1AAA&qsoDate=2026-03-21');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(1, $payload['clubs']);
        self::assertSame('CURRENT-002', $payload['clubs'][0]['number']);
    }

    #[Test]
    public function detailUsesTodayForClubMembershipWhenQsoDateIsMissing(): void
    {
        $today = new DateTimeImmutable('today');

        $this->connection->insert('club1', [
            'club_nr' => 'ACTIVE-TODAY',
            'clubcall' => 'OK1TODAY',
            'fromdate' => $today->modify('-10 days')->format('Y-m-d'),
            'todate' => $today->modify('+10 days')->format('Y-m-d'),
        ])->execute();
        $this->connection->insert('club1', [
            'club_nr' => 'EXPIRED',
            'clubcall' => 'OK1TODAY',
            'fromdate' => $today->modify('-30 days')->format('Y-m-d'),
            'todate' => $today->modify('-1 day')->format('Y-m-d'),
        ])->execute();

        $this->client->request('GET', '/api/callsignContext?callsign=OK1TODAY');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(1, $payload['clubs']);
        self::assertSame('ACTIVE-TODAY', $payload['clubs'][0]['number']);
    }

    #[Test]
    public function detailValidationFailureReturnsFieldErrors(): void
    {
        $this->client->request('GET', '/api/callsignContext?callsign=&qsoDate=2026/03/21');

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('validation_failed', $payload['error']['code']);
        self::assertSame(['This field must not be empty.'], $payload['error']['details']['fields']['callsign']);
        self::assertSame(['This field must use Y-m-d format.'], $payload['error']['details']['fields']['qsoDate']);
    }

    /**
     * @param array<string, int|string|null> $overrides
     */
    private function insertLogEntry(array $overrides = []): int
    {
        $data = array_replace([
            'qsodate' => '2026-03-16',
            'time_on' => '10:00',
            'time_off' => '',
            'callsign' => 'OK1AAA',
            'freq' => 7.0250,
            'mode' => 'CW',
        ], $overrides);

        $this->connection->insert('cqrlog_main', $data)->execute();

        return $this->connection->getInsertId();
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
