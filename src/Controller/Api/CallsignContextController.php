<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Api\CallsignContext\CallsignContextGateway;
use App\Api\CallsignContext\CallsignContextQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/callsignContext', name: 'api_callsign_context_')]
final class CallsignContextController extends AbstractController
{
    public function __construct(
        private readonly CallsignContextGateway $gateway,
    ) {
    }

    #[Route('', name: 'detail', methods: ['GET'])]
    public function detail(Request $request): JsonResponse
    {
        $query = CallsignContextQuery::fromArray($request->query->all());

        return $this->json($this->gateway->fetch($query)->toArray());
    }
}
