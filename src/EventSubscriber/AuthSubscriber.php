<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class AuthSubscriber implements EventSubscriberInterface
{
    private const PUBLIC_PATHS = [
        '/api/health',
        '/api/auth/login',
        '/api/auth/status',
        '/api/auth/logout',
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();

        if (!str_starts_with($path, '/api/')) {
            return;
        }

        if (in_array($path, self::PUBLIC_PATHS, true)) {
            return;
        }

        if (!$this->isAuthConfigured()) {
            return;
        }

        $request = $event->getRequest();
        $session = $request->hasSession() ? $request->getSession() : null;

        if ($session !== null && $session->get('_authenticated') === true) {
            $idleTimeout = $this->getIdleTimeoutSeconds();

            if ($idleTimeout > 0) {
                $lastActivity = $session->get('_last_activity');

                if ($lastActivity !== null && (time() - (int) $lastActivity) > $idleTimeout) {
                    $session->invalidate();

                    $event->setResponse(new JsonResponse([
                        'error' => [
                            'code' => 'not_authenticated',
                            'message' => 'Session expired.',
                        ],
                    ], Response::HTTP_UNAUTHORIZED));

                    return;
                }
            }

            $session->set('_last_activity', time());

            return;
        }

        $event->setResponse(new JsonResponse([
            'error' => [
                'code' => 'not_authenticated',
                'message' => 'Authentication required.',
            ],
        ], Response::HTTP_UNAUTHORIZED));
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

    private function isAuthConfigured(): bool
    {
        $username = $this->readEnvString('LOGIN_USERNAME');
        $password = $this->readEnvString('LOGIN_PASSWORD');

        return $username !== null && $password !== null;
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
