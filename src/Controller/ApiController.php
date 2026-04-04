<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class ApiController extends AbstractController
{
    #[Route('/', name: 'api_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json([
            'name' => 'Symfony API',
            'status' => 'ok',
            'docs' => '/api/health',
        ]);
    }

    #[Route('/api/health', name: 'api_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return $this->json([
            'status' => 'ok',
            'environment' => $_ENV['APP_ENV'] ?? 'dev',
        ]);
    }

    #[Route('/api/frontendConfig', name: 'api_frontend_config', methods: ['GET'])]
    public function frontendConfig(): JsonResponse
    {
        return $this->json([
            'radioSyncDefaultUrl' => $_ENV['FRONTEND_RADIO_SYNC_DEFAULT_URL'] ?? 'https://example.com/radio-json.php',
            'radioSyncDefaultPollIntervalSeconds' => $this->normalizePositiveInt(
                $_ENV['FRONTEND_RADIO_SYNC_DEFAULT_POLL_INTERVAL_SECONDS'] ?? null,
                2,
            ),
        ]);
    }

    private function normalizePositiveInt(mixed $value, int $fallback): int
    {
        if (!is_scalar($value)) {
            return $fallback;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : $fallback;
    }
}
