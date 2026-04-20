<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Security\LoginRateLimiter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuthController extends AbstractController
{
    public function __construct(
        private readonly LoginRateLimiter $rateLimiter,
    ) {
    }

    #[Route('/api/auth/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $expectedUsername = $this->readEnvString('LOGIN_USERNAME');
        $expectedPassword = $this->readEnvString('LOGIN_PASSWORD');

        if ($expectedUsername === null || $expectedPassword === null) {
            return new JsonResponse([
                'error' => [
                    'code' => 'auth_not_configured',
                    'message' => 'Authentication is not configured.',
                ],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $clientIp = $request->getClientIp() ?? 'unknown';

        if ($this->rateLimiter->isBlocked($clientIp)) {
            return new JsonResponse([
                'error' => [
                    'code' => 'too_many_attempts',
                    'message' => 'Too many login attempts. Please try again later.',
                ],
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $content = $request->getContent();
        $data = json_decode($content, true);

        if (!is_array($data)) {
            return new JsonResponse([
                'error' => [
                    'code' => 'invalid_json',
                    'message' => 'Request body must be valid JSON.',
                ],
            ], Response::HTTP_BAD_REQUEST);
        }

        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (!is_string($username) || !is_string($password) || $username === '' || $password === '') {
            $this->rateLimiter->recordFailedAttempt($clientIp);

            return new JsonResponse([
                'error' => [
                    'code' => 'invalid_credentials',
                    'message' => 'Invalid username or password.',
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (!hash_equals($expectedUsername, $username) || !hash_equals($expectedPassword, $password)) {
            $this->rateLimiter->recordFailedAttempt($clientIp);

            return new JsonResponse([
                'error' => [
                    'code' => 'invalid_credentials',
                    'message' => 'Invalid username or password.',
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        $this->rateLimiter->reset($clientIp);

        $session = $request->getSession();
        $session->set('_authenticated', true);
        $session->set('_last_activity', time());

        return new JsonResponse(['authenticated' => true]);
    }

    #[Route('/api/auth/status', name: 'api_auth_status', methods: ['GET'])]
    public function status(Request $request): JsonResponse
    {
        $expectedUsername = $this->readEnvString('LOGIN_USERNAME');
        $expectedPassword = $this->readEnvString('LOGIN_PASSWORD');

        if ($expectedUsername === null || $expectedPassword === null) {
            return new JsonResponse(['authenticated' => true, 'authRequired' => false]);
        }

        $session = $request->hasSession() ? $request->getSession() : null;
        $authenticated = $session !== null && $session->get('_authenticated') === true;

        if ($authenticated) {
            $idleTimeout = $this->getIdleTimeoutSeconds();

            if ($idleTimeout > 0) {
                $lastActivity = $session->get('_last_activity');

                if ($lastActivity !== null && (time() - (int) $lastActivity) > $idleTimeout) {
                    $session->invalidate();
                    $authenticated = false;
                }
            }
        }

        return new JsonResponse(['authenticated' => $authenticated, 'authRequired' => true]);
    }

    #[Route('/api/auth/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        if ($request->hasSession()) {
            $request->getSession()->invalidate();
        }

        return new JsonResponse(['authenticated' => false]);
    }

    private function getIdleTimeoutSeconds(): int
    {
        $value = $this->readEnvString('SESSION_IDLE_TIMEOUT_SECONDS');

        if ($value === null) {
            return 0;
        }

        $seconds = (int) $value;

        return $seconds > 0 ? $seconds : 0;
    }

    private function readEnvString(string $name): ?string
    {
        if (array_key_exists($name, $_SERVER) && is_string($_SERVER[$name])) {
            $trimmed = trim($_SERVER[$name]);

            return $trimmed === '' ? null : $trimmed;
        }

        if (array_key_exists($name, $_ENV) && is_string($_ENV[$name])) {
            $trimmed = trim($_ENV[$name]);

            return $trimmed === '' ? null : $trimmed;
        }

        $value = getenv($name);

        if ($value === false || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}
