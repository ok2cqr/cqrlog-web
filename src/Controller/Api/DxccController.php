<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Api\Dxcc\DxccGateway;
use App\Api\Dxcc\DxccQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/dxcc', name: 'api_dxcc_')]
final class DxccController extends AbstractController
{
    public function __construct(
        private readonly DxccGateway $gateway,
    ) {
    }

    #[Route('', name: 'detail', methods: ['GET'])]
    public function detail(Request $request): JsonResponse
    {
        $query = DxccQuery::fromArray($request->query->all());

        return $this->json($this->gateway->fetch($query)->toArray());
    }
}
