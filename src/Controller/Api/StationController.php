<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Api\Station\StationGateway;
use App\Api\Station\StationMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/station', name: 'api_station_')]
final class StationController extends AbstractController
{
    public function __construct(
        private readonly StationGateway $gateway,
        private readonly StationMapper $mapper,
    ) {
    }

    #[Route('', name: 'detail', methods: ['GET'])]
    public function detail(): JsonResponse
    {
        return $this->json($this->mapper->map($this->gateway->fetch())->toArray());
    }
}
