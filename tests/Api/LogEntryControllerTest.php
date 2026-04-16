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

final class LogEntryControllerTest extends WebTestCase
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
        $this->connection->query('TRUNCATE TABLE profiles');
        $this->connection->query('DELETE FROM log_changes');
        $this->connection->query('DELETE FROM cqrlog_main');
    }

    #[Test]
    public function listReturnsMappedLogEntries(): void
    {
        $olderId = $this->insertLogEntry([
            'qsodate' => '2026-03-14',
            'time_on' => '08:15',
            'callsign' => 'OK1OLD',
            'freq' => 7.0740,
            'mode' => 'FT8',
            'remarks' => 'Older contact',
        ]);
        $newerId = $this->insertLogEntry([
            'qsodate' => '2026-03-15',
            'time_on' => '09:30',
            'callsign' => 'OK1NEW',
            'freq' => 14.0740,
            'mode' => 'FT8',
            'remarks' => '',
            'loc' => 'JN79',
            'state' => 'CA',
            'county' => 'Santa Clara',
            'iota' => 'EU-001',
            'pwr' => '100W',
            'itu' => 28,
            'waz' => 15,
            'cont' => 'NA',
            'lotw_qsls' => 'Y',
            'club_nr1' => 'C1',
            'eqsl_qsl_sent' => 'Y',
            'satellite' => 'QO-100',
            'profile' => 2,
        ]);

        $this->client->request('GET', '/api/logEntries');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array{items: list<array<string, mixed>>, totalCount: int, page: int, perPage: int, totalPages: int, sortBy: string, sortDirection: string} $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(2, $payload['totalCount']);
        self::assertSame(1, $payload['page']);
        self::assertSame(50, $payload['perPage']);
        self::assertSame(1, $payload['totalPages']);
        self::assertSame('qsoDate', $payload['sortBy']);
        self::assertSame('desc', $payload['sortDirection']);
        self::assertCount(2, $payload['items']);
        self::assertSame($newerId, $payload['items'][0]['id']);
        self::assertSame('2026-03-15', $payload['items'][0]['qsoDate']);
        self::assertSame('09:30', $payload['items'][0]['timeOn']);
        self::assertSame('OK1NEW', $payload['items'][0]['callsign']);
        self::assertSame(14.074, $payload['items'][0]['frequency']);
        self::assertSame('JN79', $payload['items'][0]['grid']);
        self::assertSame('CA', $payload['items'][0]['state']);
        self::assertSame('Santa Clara', $payload['items'][0]['county']);
        self::assertSame('EU-001', $payload['items'][0]['iota']);
        self::assertSame('100W', $payload['items'][0]['power']);
        self::assertSame(28, $payload['items'][0]['itu']);
        self::assertSame(15, $payload['items'][0]['waz']);
        self::assertSame('NA', $payload['items'][0]['continent']);
        self::assertSame('Y', $payload['items'][0]['lotwSent']);
        self::assertSame('C1', $payload['items'][0]['clubNumber1']);
        self::assertSame('Y', $payload['items'][0]['eqslSent']);
        self::assertSame('QO-100', $payload['items'][0]['satellite']);
        self::assertNull($payload['items'][0]['remarks']);
        self::assertSame(2, $payload['items'][0]['profileId']);
        self::assertSame($olderId, $payload['items'][1]['id']);
        self::assertArrayNotHasKey('id_cqrlog_main', $payload['items'][0]);
        self::assertArrayNotHasKey('qsodate', $payload['items'][0]);
    }

    #[Test]
    public function listSupportsPaginationAndFiltering(): void
    {
        $firstId = $this->insertLogEntry([
            'qsodate' => '2026-03-14',
            'time_on' => '08:00',
            'callsign' => 'OK1AAA',
            'freq' => 7.0740,
            'mode' => 'FT8',
        ]);
        $secondId = $this->insertLogEntry([
            'qsodate' => '2026-03-15',
            'time_on' => '08:00',
            'callsign' => 'OK1BBB',
            'freq' => 7.0740,
            'mode' => 'FT8',
        ]);
        $thirdId = $this->insertLogEntry([
            'qsodate' => '2026-03-16',
            'time_on' => '08:00',
            'callsign' => 'OK2BBB',
            'freq' => 7.0740,
            'mode' => 'FT8',
        ]);

        $this->client->request('GET', '/api/logEntries?page=1&perPage=1&callsign=bbb&qsoDateFrom=2026-03-15&qsoDateTo=2026-03-16');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array{items: list<array<string, mixed>>, totalCount: int, page: int, perPage: int, totalPages: int, sortBy: string, sortDirection: string} $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(2, $payload['totalCount']);
        self::assertSame(1, $payload['page']);
        self::assertSame(1, $payload['perPage']);
        self::assertSame(2, $payload['totalPages']);
        self::assertSame('qsoDate', $payload['sortBy']);
        self::assertSame('desc', $payload['sortDirection']);
        self::assertCount(1, $payload['items']);
        self::assertSame($thirdId, $payload['items'][0]['id']);

        $this->client->request('GET', '/api/logEntries?page=2&perPage=1&callsign=bbb&qsoDateFrom=2026-03-15&qsoDateTo=2026-03-16');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array{items: list<array<string, mixed>>, totalCount: int, page: int, perPage: int, totalPages: int} $secondPage */
        $secondPage = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(1, $secondPage['items']);
        self::assertSame($secondId, $secondPage['items'][0]['id']);
        self::assertNotSame($firstId, $secondPage['items'][0]['id']);
    }

    #[Test]
    public function listSupportsSorting(): void
    {
        $alphaId = $this->insertLogEntry([
            'qsodate' => '2026-03-15',
            'time_on' => '09:00',
            'callsign' => 'OK1AAA',
            'freq' => 14.0740,
            'mode' => 'SSB',
        ]);
        $middleId = $this->insertLogEntry([
            'qsodate' => '2026-03-16',
            'time_on' => '09:00',
            'callsign' => 'OK1MMM',
            'freq' => 7.0740,
            'mode' => 'CW',
        ]);
        $zuluId = $this->insertLogEntry([
            'qsodate' => '2026-03-14',
            'time_on' => '09:00',
            'callsign' => 'OK1ZZZ',
            'freq' => 3.5990,
            'mode' => 'FT8',
        ]);

        $this->client->request('GET', '/api/logEntries?sortBy=callsign&sortDirection=asc');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array{items: list<array<string, mixed>>, sortBy: string, sortDirection: string} $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('callsign', $payload['sortBy']);
        self::assertSame('asc', $payload['sortDirection']);
        self::assertSame([$alphaId, $middleId, $zuluId], array_column($payload['items'], 'id'));

        $this->client->request('GET', '/api/logEntries?sortBy=frequency&sortDirection=desc');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array{items: list<array<string, mixed>>, sortBy: string, sortDirection: string} $sortedByFrequency */
        $sortedByFrequency = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('frequency', $sortedByFrequency['sortBy']);
        self::assertSame('desc', $sortedByFrequency['sortDirection']);
        self::assertSame([$alphaId, $middleId, $zuluId], array_column($sortedByFrequency['items'], 'id'));
    }

    #[Test]
    public function listValidationFailureReturnsFieldErrors(): void
    {
        $this->client->request('GET', '/api/logEntries?page=0&perPage=101&qsoDateFrom=2026/03/16&sortBy=rawSql&sortDirection=sideways');

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('validation_failed', $payload['error']['code']);
        self::assertSame(['This field must be greater than or equal to 1.'], $payload['error']['details']['fields']['page']);
        self::assertSame(['This field must be less than or equal to 100.'], $payload['error']['details']['fields']['perPage']);
        self::assertSame(['This field must use Y-m-d format.'], $payload['error']['details']['fields']['qsoDateFrom']);
        self::assertSame(['This field must be one of: qsoDate, callsign, frequency, mode, id.'], $payload['error']['details']['fields']['sortBy']);
        self::assertSame(['This field must be one of: asc, desc.'], $payload['error']['details']['fields']['sortDirection']);
    }

    #[Test]
    public function detailReturnsMappedLogEntry(): void
    {
        $id = $this->insertLogEntry([
            'qsodate' => '2026-03-16',
            'time_on' => '10:45',
            'time_off' => '10:46',
            'callsign' => 'OK1TEST',
            'freq' => 3.5990,
            'mode' => 'CW',
            'rst_s' => '599',
            'rst_r' => '579',
            'name' => 'Petr',
            'qth' => 'Prague',
            'loc' => 'JO70VA',
            'state' => 'PR',
            'county' => 'Praha',
            'award' => 'WAC',
            'adif' => 291,
            'band' => '80m',
            'remarks' => 'Morning QSO',
            'qsl_s' => 'Y',
            'qsl_r' => 'N',
            'qsl_via' => 'Bureau',
            'iota' => 'EU-001',
            'pwr' => '50W',
            'itu' => 28,
            'waz' => 15,
            'idcall' => 'OK1TEST/P',
            'lotw_qslsdate' => '2026-03-17',
            'lotw_qslrdate' => '2026-03-18',
            'lotw_qsls' => 'Y',
            'lotw_qslr' => 'R',
            'cont' => 'EU',
            'qsls_date' => '2026-03-19',
            'qslr_date' => '2026-03-20',
            'club_nr1' => '100',
            'club_nr2' => '200',
            'club_nr3' => '300',
            'club_nr4' => '400',
            'club_nr5' => '500',
            'eqsl_qsl_sent' => 'Y',
            'eqsl_qslsdate' => '2026-03-21',
            'eqsl_qsl_rcvd' => 'N',
            'eqsl_qslrdate' => '2026-03-22',
            'rxfreq' => 3.6000,
            'satellite' => 'QO-100',
            'prop_mode' => 'SAT',
            'stx' => '001',
            'srx' => '002',
            'stx_string' => 'STX-001',
            'srx_string' => 'SRX-002',
            'contestname' => 'CQ WW',
            'dok' => 'A01',
            'operator' => 'OK1OP',
            'my_loc' => 'JO70VA',
            'qso_dxcc' => 503,
            'profile' => 1,
        ]);

        $this->client->request('GET', sprintf('/api/logEntries/%d', $id));

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame($id, $payload['id']);
        self::assertSame('2026-03-16', $payload['qsoDate']);
        self::assertSame('10:45', $payload['timeOn']);
        self::assertSame('10:46', $payload['timeOff']);
        self::assertSame('OK1TEST', $payload['callsign']);
        self::assertSame(3.599, $payload['frequency']);
        self::assertSame('CW', $payload['mode']);
        self::assertSame('599', $payload['rstSent']);
        self::assertSame('579', $payload['rstReceived']);
        self::assertSame('Petr', $payload['name']);
        self::assertSame('Prague', $payload['qth']);
        self::assertSame('JO70VA', $payload['grid']);
        self::assertSame('PR', $payload['state']);
        self::assertSame('Praha', $payload['county']);
        self::assertSame('WAC', $payload['award']);
        self::assertSame(291, $payload['adif']);
        self::assertSame('80m', $payload['band']);
        self::assertSame('Morning QSO', $payload['remarks']);
        self::assertSame('Y', $payload['qslSent']);
        self::assertSame('N', $payload['qslReceived']);
        self::assertSame('Bureau', $payload['qslVia']);
        self::assertSame('EU-001', $payload['iota']);
        self::assertSame('50W', $payload['power']);
        self::assertSame(28, $payload['itu']);
        self::assertSame(15, $payload['waz']);
        self::assertSame('OK1TEST/P', $payload['idCall']);
        self::assertSame('2026-03-17', $payload['lotwSentDate']);
        self::assertSame('2026-03-18', $payload['lotwReceivedDate']);
        self::assertSame('Y', $payload['lotwSent']);
        self::assertSame('R', $payload['lotwReceived']);
        self::assertSame('EU', $payload['continent']);
        self::assertSame('2026-03-19', $payload['qslSentDate']);
        self::assertSame('2026-03-20', $payload['qslReceivedDate']);
        self::assertSame('100', $payload['clubNumber1']);
        self::assertSame('200', $payload['clubNumber2']);
        self::assertSame('300', $payload['clubNumber3']);
        self::assertSame('400', $payload['clubNumber4']);
        self::assertSame('500', $payload['clubNumber5']);
        self::assertSame('Y', $payload['eqslSent']);
        self::assertSame('2026-03-21', $payload['eqslSentDate']);
        self::assertSame('N', $payload['eqslReceived']);
        self::assertSame('2026-03-22', $payload['eqslReceivedDate']);
        self::assertSame(3.6, $payload['receiveFrequency']);
        self::assertSame('QO-100', $payload['satellite']);
        self::assertSame('SAT', $payload['propagationMode']);
        self::assertSame('001', $payload['stx']);
        self::assertSame('002', $payload['srx']);
        self::assertSame('STX-001', $payload['stxString']);
        self::assertSame('SRX-002', $payload['srxString']);
        self::assertSame('CQ WW', $payload['contestName']);
        self::assertSame('A01', $payload['dok']);
        self::assertSame('OK1OP', $payload['operator']);
        self::assertSame('JO70VA', $payload['myLocator']);
        self::assertSame(503, $payload['qsoDxcc']);
        self::assertSame(1, $payload['profileId']);
    }

    #[Test]
    public function createReturnsCreatedLogEntry(): void
    {
        $this->client->jsonRequest('POST', '/api/logEntries', [
            'qsoDate' => '2026-03-16',
            'timeOn' => '11:30',
            'callsign' => 'ok1new',
            'frequency' => 14.074,
            'mode' => 'FT8',
            'grid' => 'jn79',
            'state' => 'ca',
            'county' => 'Santa Clara',
            'award' => 'WAS',
            'adif' => 291,
            'remarks' => 'Created via API',
            'iota' => 'eu-001',
            'power' => '100W',
            'itu' => 28,
            'waz' => 15,
            'idCall' => 'ok1new/p',
            'lotwSentDate' => '2026-03-17',
            'lotwReceivedDate' => '2026-03-18',
            'lotwSent' => 'y',
            'lotwReceived' => 'r',
            'continent' => 'eu',
            'qslSentDate' => '2026-03-19',
            'qslReceivedDate' => '2026-03-20',
            'clubNumber1' => '100',
            'clubNumber2' => '200',
            'clubNumber3' => '300',
            'clubNumber4' => '400',
            'clubNumber5' => '500',
            'eqslSent' => 'y',
            'eqslSentDate' => '2026-03-21',
            'eqslReceived' => 'n',
            'eqslReceivedDate' => '2026-03-22',
            'receiveFrequency' => 14.075,
            'satellite' => 'QO-100',
            'propagationMode' => 'SAT',
            'stx' => '001',
            'srx' => '002',
            'stxString' => 'STX-001',
            'srxString' => 'SRX-002',
            'contestName' => 'CQ WW',
            'dok' => 'd01',
            'operator' => 'ok1op',
            'profileId' => 3,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsInt($payload['id']);
        self::assertSame('2026-03-16', $payload['qsoDate']);
        self::assertSame('11:30', $payload['timeOn']);
        self::assertSame('OK1NEW', $payload['callsign']);
        self::assertSame(14.074, $payload['frequency']);
        self::assertSame('FT8', $payload['mode']);
        self::assertSame('JN79', $payload['grid']);
        self::assertSame('CA', $payload['state']);
        self::assertSame('Santa Clara', $payload['county']);
        self::assertSame('WAS', $payload['award']);
        self::assertSame(291, $payload['adif']);
        self::assertSame('Created via API', $payload['remarks']);
        self::assertSame('EU-001', $payload['iota']);
        self::assertSame('100W', $payload['power']);
        self::assertSame(28, $payload['itu']);
        self::assertSame(15, $payload['waz']);
        self::assertSame('OK1NEW/P', $payload['idCall']);
        self::assertSame('2026-03-17', $payload['lotwSentDate']);
        self::assertSame('2026-03-18', $payload['lotwReceivedDate']);
        self::assertSame('Y', $payload['lotwSent']);
        self::assertSame('R', $payload['lotwReceived']);
        self::assertSame('EU', $payload['continent']);
        self::assertSame('2026-03-19', $payload['qslSentDate']);
        self::assertSame('2026-03-20', $payload['qslReceivedDate']);
        self::assertSame('100', $payload['clubNumber1']);
        self::assertSame('200', $payload['clubNumber2']);
        self::assertSame('300', $payload['clubNumber3']);
        self::assertSame('400', $payload['clubNumber4']);
        self::assertSame('500', $payload['clubNumber5']);
        self::assertSame('Y', $payload['eqslSent']);
        self::assertSame('2026-03-21', $payload['eqslSentDate']);
        self::assertSame('N', $payload['eqslReceived']);
        self::assertSame('2026-03-22', $payload['eqslReceivedDate']);
        self::assertSame(14.075, $payload['receiveFrequency']);
        self::assertSame('QO-100', $payload['satellite']);
        self::assertSame('SAT', $payload['propagationMode']);
        self::assertSame('001', $payload['stx']);
        self::assertSame('002', $payload['srx']);
        self::assertSame('STX-001', $payload['stxString']);
        self::assertSame('SRX-002', $payload['srxString']);
        self::assertSame('CQ WW', $payload['contestName']);
        self::assertSame('D01', $payload['dok']);
        self::assertSame('OK1OP', $payload['operator']);
        self::assertSame(3, $payload['profileId']);

        $row = $this->connection->fetch(
            'SELECT id_cqrlog_main, callsign, freq, profile, loc, state, award, adif, iota, pwr, itu, waz, idcall,
                lotw_qslsdate, lotw_qslrdate, lotw_qsls, lotw_qslr, cont, qsls_date, qslr_date, club_nr1, club_nr2,
                club_nr3, club_nr4, club_nr5, eqsl_qsl_sent, eqsl_qslsdate, eqsl_qsl_rcvd, eqsl_qslrdate, rxfreq,
                satellite, prop_mode, stx, srx, stx_string, srx_string, contestname, dok, operator, remarks
            FROM cqrlog_main
            WHERE id_cqrlog_main = %i',
            $payload['id'],
        );

        self::assertNotNull($row);
        self::assertSame('OK1NEW', $row->callsign);
        self::assertSame(14.074, (float) $row->freq);
        self::assertSame('JN79', $row->loc);
        self::assertSame('CA', $row->state);
        self::assertSame('WAS', $row->award);
        self::assertSame(291, (int) $row->adif);
        self::assertSame('EU-001', $row->iota);
        self::assertSame('100W', $row->pwr);
        self::assertSame(28, (int) $row->itu);
        self::assertSame(15, (int) $row->waz);
        self::assertSame('OK1NEW/P', $row->idcall);
        self::assertSame('2026-03-17', $row->lotw_qslsdate);
        self::assertSame('2026-03-18', $row->lotw_qslrdate);
        self::assertSame('Y', $row->lotw_qsls);
        self::assertSame('R', $row->lotw_qslr);
        self::assertSame('EU', $row->cont);
        self::assertSame('2026-03-19', $row->qsls_date);
        self::assertSame('2026-03-20', $row->qslr_date);
        self::assertSame('100', $row->club_nr1);
        self::assertSame('200', $row->club_nr2);
        self::assertSame('300', $row->club_nr3);
        self::assertSame('400', $row->club_nr4);
        self::assertSame('500', $row->club_nr5);
        self::assertSame('Y', $row->eqsl_qsl_sent);
        self::assertSame('2026-03-21', $row->eqsl_qslsdate);
        self::assertSame('N', $row->eqsl_qsl_rcvd);
        self::assertSame('2026-03-22', $row->eqsl_qslrdate);
        self::assertSame(14.075, (float) $row->rxfreq);
        self::assertSame('QO-100', $row->satellite);
        self::assertSame('SAT', $row->prop_mode);
        self::assertSame('001', $row->stx);
        self::assertSame('002', $row->srx);
        self::assertSame('STX-001', $row->stx_string);
        self::assertSame('SRX-002', $row->srx_string);
        self::assertSame('CQ WW', $row->contestname);
        self::assertSame('D01', $row->dok);
        self::assertSame('OK1OP', $row->operator);
        self::assertSame(3, (int) $row->profile);
    }

    #[Test]
    public function createUsesSelectedProfileLocatorAsMyLocator(): void
    {
        $profileId = $this->insertProfile([
            'locator' => 'JO70GG',
        ]);

        $this->client->jsonRequest('POST', '/api/logEntries', [
            'qsoDate' => '2026-03-16',
            'timeOn' => '11:30',
            'callsign' => 'ok1profile',
            'frequency' => 14.074,
            'mode' => 'FT8',
            'profileId' => $profileId,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame($profileId, $payload['profileId']);
        self::assertSame('JO70GG', $payload['myLocator']);

        $row = $this->connection->fetch(
            'SELECT my_loc, profile FROM cqrlog_main WHERE id_cqrlog_main = %i',
            $payload['id'],
        );

        self::assertNotNull($row);
        self::assertSame('JO70GG', $row->my_loc);
        self::assertSame($profileId, (int) $row->profile);
    }

    #[Test]
    public function createAutomaticallyResolvesIdCallFromCallsign(): void
    {
        $this->client->jsonRequest('POST', '/api/logEntries', [
            'qsoDate' => '2026-03-16',
            'timeOn' => '11:30',
            'callsign' => 'ok1xyz/p',
            'frequency' => 14.074,
            'mode' => 'FT8',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('OK1XYZ/P', $payload['callsign']);
        self::assertSame('OK1XYZ', $payload['idCall']);

        $row = $this->connection->fetch(
            'SELECT idcall FROM cqrlog_main WHERE id_cqrlog_main = %i',
            $payload['id'],
        );

        self::assertNotNull($row);
        self::assertSame('OK1XYZ', $row->idcall);
    }

    #[Test]
    public function patchUpdatesResolvedIdCallWhenCallsignChanges(): void
    {
        $id = $this->insertLogEntry([
            'callsign' => 'OK1PATCH',
            'idcall' => 'OK1PATCH',
        ]);

        $this->client->jsonRequest('PATCH', sprintf('/api/logEntries/%d', $id), [
            'callsign' => 'ok1patch/p',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('OK1PATCH/P', $payload['callsign']);
        self::assertSame('OK1PATCH', $payload['idCall']);

        $row = $this->connection->fetch(
            'SELECT callsign, idcall FROM cqrlog_main WHERE id_cqrlog_main = %i',
            $id,
        );

        self::assertNotNull($row);
        self::assertSame('OK1PATCH/P', $row->callsign);
        self::assertSame('OK1PATCH', $row->idcall);
    }

    #[Test]
    public function patchUpdatesOnlyProvidedFields(): void
    {
        $id = $this->insertLogEntry([
            'qsodate' => '2026-03-16',
            'time_on' => '12:00',
            'callsign' => 'OK1PATCH',
            'freq' => 7.0400,
            'mode' => 'CW',
            'remarks' => 'Old remarks',
            'time_off' => '12:05',
        ]);

        $this->client->jsonRequest('PATCH', sprintf('/api/logEntries/%d', $id), [
            'remarks' => null,
            'timeOff' => null,
            'mode' => 'SSB',
            'receiveFrequency' => 7.041,
            'contestName' => null,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame($id, $payload['id']);
        self::assertSame('OK1PATCH', $payload['callsign']);
        self::assertSame('SSB', $payload['mode']);
        self::assertNull($payload['remarks']);
        self::assertNull($payload['timeOff']);
        self::assertSame(7.041, $payload['receiveFrequency']);
        self::assertNull($payload['contestName']);
    }

    #[Test]
    public function createValidationFailureReturnsFieldErrors(): void
    {
        $this->client->jsonRequest('POST', '/api/logEntries', [
            'qsoDate' => '16-03-2026',
            'timeOn' => '25:61',
            'callsign' => '',
            'frequency' => 0,
            'mode' => '',
            'lotwSentDate' => '17/03/2026',
            'receiveFrequency' => -1,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('validation_failed', $payload['error']['code']);
        self::assertSame(['This field must use Y-m-d format.'], $payload['error']['details']['fields']['qsoDate']);
        self::assertSame(['This field must use HH:MM format.'], $payload['error']['details']['fields']['timeOn']);
        self::assertSame(['This field must not be empty.'], $payload['error']['details']['fields']['callsign']);
        self::assertSame(['This field must be greater than 0.'], $payload['error']['details']['fields']['frequency']);
        self::assertSame(['This field must not be empty.'], $payload['error']['details']['fields']['mode']);
        self::assertSame(['This field must use Y-m-d format.'], $payload['error']['details']['fields']['lotwSentDate']);
        self::assertSame(['This field must be greater than 0.'], $payload['error']['details']['fields']['receiveFrequency']);
    }

    #[Test]
    public function missingLogEntryReturnsJsonNotFound(): void
    {
        $this->client->request('GET', '/api/logEntries/999999');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('not_found', $payload['error']['code']);
        self::assertSame('Log entry with id 999999 was not found.', $payload['error']['message']);
    }

    /**
     * @param array<string, int|float|string> $overrides
     */
    private function insertLogEntry(array $overrides = []): int
    {
        $data = array_replace([
            'qsodate' => '2026-03-16',
            'time_on' => '10:00',
            'time_off' => '',
            'callsign' => 'OK1AAA',
            'freq' => 14.0740,
            'mode' => 'FT8',
            'rst_s' => '',
            'rst_r' => '',
            'name' => '',
            'qth' => '',
            'loc' => '',
            'state' => '',
            'county' => '',
            'award' => '',
            'adif' => 0,
            'band' => '',
            'remarks' => '',
            'qsl_s' => '',
            'qsl_r' => '',
            'qsl_via' => '',
            'iota' => '',
            'pwr' => '',
            'itu' => 0,
            'waz' => 0,
            'idcall' => '',
            'lotw_qslsdate' => null,
            'lotw_qslrdate' => null,
            'lotw_qsls' => '',
            'lotw_qslr' => '',
            'cont' => '',
            'qsls_date' => null,
            'qslr_date' => null,
            'club_nr1' => '',
            'club_nr2' => '',
            'club_nr3' => '',
            'club_nr4' => '',
            'club_nr5' => '',
            'eqsl_qsl_sent' => '',
            'eqsl_qslsdate' => null,
            'eqsl_qsl_rcvd' => '',
            'eqsl_qslrdate' => null,
            'rxfreq' => null,
            'satellite' => '',
            'prop_mode' => '',
            'stx' => null,
            'srx' => null,
            'stx_string' => null,
            'srx_string' => null,
            'contestname' => null,
            'dok' => '',
            'operator' => '',
            'my_loc' => '',
            'qso_dxcc' => 0,
            'profile' => 0,
        ], $overrides);

        $this->connection->insert('cqrlog_main', $data)->execute();

        return $this->connection->getInsertId();
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
