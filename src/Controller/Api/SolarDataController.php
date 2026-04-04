<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Api\SolarData\SolarDataGateway;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/solarData', name: 'api_solar_data_')]
final class SolarDataController extends AbstractController
{
    public function __construct(
        private readonly SolarDataGateway $gateway,
    ) {
    }

    #[Route('', name: 'detail', methods: ['GET'])]
    public function detail(): Response
    {
        return new Response(
            $this->gateway->fetch(),
            Response::HTTP_OK,
            ['Content-Type' => 'text/plain; charset=UTF-8'],
        );
    }
}
