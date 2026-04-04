<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Api\Exception\ApiException;
use Dibi\Exception as DibiException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $throwable = $event->getThrowable();

        if ($throwable instanceof ApiException) {
            $event->setResponse($this->createResponse(
                $throwable->getStatusCode(),
                $throwable->getErrorCode(),
                $throwable->getMessage(),
                $throwable->getDetails(),
            ));

            return;
        }

        if ($throwable instanceof HttpExceptionInterface) {
            $event->setResponse($this->createResponse(
                $throwable->getStatusCode(),
                'http_error',
                $throwable->getMessage() !== '' ? $throwable->getMessage() : Response::$statusTexts[$throwable->getStatusCode()] ?? 'HTTP error.',
            ));

            return;
        }

        if ($throwable instanceof DibiException) {
            $event->setResponse($this->createResponse(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'database_error',
                'A database error occurred.',
            ));

            return;
        }

        $event->setResponse($this->createResponse(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            'internal_error',
            'Unexpected server error.',
        ));
    }

    /**
     * @param array<string, mixed> $details
     */
    private function createResponse(int $statusCode, string $errorCode, string $message, array $details = []): JsonResponse
    {
        $payload = [
            'error' => [
                'code' => $errorCode,
                'message' => $message,
            ],
        ];

        if ($details !== []) {
            $payload['error']['details'] = $details;
        }

        return new JsonResponse($payload, $statusCode);
    }
}
