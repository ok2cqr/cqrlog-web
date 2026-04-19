<?php

declare(strict_types=1);

namespace App\Tests\Api;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class AuthControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
    }

    #[Test]
    public function loginWithValidCredentialsReturnsAuthenticated(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['username' => 'testuser', 'password' => 'testpass']));

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($payload['authenticated']);
    }

    #[Test]
    public function loginWithWrongPasswordReturns401(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['username' => 'testuser', 'password' => 'wrongpass']));

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('invalid_credentials', $payload['error']['code']);
    }

    #[Test]
    public function loginWithMissingFieldsReturns401(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['username' => '']));

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[Test]
    public function statusReturnsUnauthenticatedBeforeLogin(): void
    {
        $this->client->request('GET', '/api/auth/status');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertFalse($payload['authenticated']);
        self::assertTrue($payload['authRequired']);
    }

    #[Test]
    public function statusReturnsAuthenticatedAfterLogin(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['username' => 'testuser', 'password' => 'testpass']));

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->client->request('GET', '/api/auth/status');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($payload['authenticated']);
        self::assertTrue($payload['authRequired']);
    }

    #[Test]
    public function protectedEndpointReturns401WhenNotAuthenticated(): void
    {
        $this->client->request('GET', '/api/profiles');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('not_authenticated', $payload['error']['code']);
    }

    #[Test]
    public function protectedEndpointWorksAfterLogin(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['username' => 'testuser', 'password' => 'testpass']));

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->client->request('GET', '/api/profiles');

        self::assertResponseIsSuccessful();
    }

    #[Test]
    public function logoutInvalidatesSession(): void
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['username' => 'testuser', 'password' => 'testpass']));

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->client->request('POST', '/api/auth/logout');

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($payload['authenticated']);

        $this->client->request('GET', '/api/auth/status');

        $statusPayload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($statusPayload['authenticated']);
    }

    #[Test]
    public function healthEndpointIsAlwaysAccessible(): void
    {
        $this->client->request('GET', '/api/health');

        self::assertResponseIsSuccessful();
    }

    #[Test]
    public function idleTimeoutExpiresStaleSessions(): void
    {
        $_SERVER['SESSION_IDLE_TIMEOUT_SECONDS'] = '1';

        try {
            $this->client->request('POST', '/api/auth/login', [], [], [
                'CONTENT_TYPE' => 'application/json',
            ], json_encode(['username' => 'testuser', 'password' => 'testpass']));

            self::assertResponseStatusCodeSame(Response::HTTP_OK);

            sleep(2);

            $this->client->request('GET', '/api/profiles');

            self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);

            $payload = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

            self::assertSame('not_authenticated', $payload['error']['code']);
        } finally {
            unset($_SERVER['SESSION_IDLE_TIMEOUT_SECONDS']);
        }
    }

    #[Test]
    public function idleTimeoutDoesNotExpireActiveSessions(): void
    {
        $_SERVER['SESSION_IDLE_TIMEOUT_SECONDS'] = '300';

        try {
            $this->client->request('POST', '/api/auth/login', [], [], [
                'CONTENT_TYPE' => 'application/json',
            ], json_encode(['username' => 'testuser', 'password' => 'testpass']));

            self::assertResponseStatusCodeSame(Response::HTTP_OK);

            $this->client->request('GET', '/api/profiles');

            self::assertResponseIsSuccessful();
        } finally {
            unset($_SERVER['SESSION_IDLE_TIMEOUT_SECONDS']);
        }
    }
}
