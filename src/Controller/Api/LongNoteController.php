<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Api\Exception\ResourceNotFoundException;
use App\Api\Http\JsonRequestDecoder;
use App\Api\LongNote\LongNoteGateway;
use App\Api\LongNote\LongNoteInput;
use App\Api\LongNote\LongNoteMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/longNotes', name: 'api_long_notes_')]
final class LongNoteController extends AbstractController
{
    public function __construct(
        private readonly LongNoteGateway $gateway,
        private readonly LongNoteMapper $mapper,
        private readonly JsonRequestDecoder $jsonRequestDecoder,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $items = array_map(
            fn ($row) => $this->mapper->map($row)->toArray(),
            $this->gateway->fetchAll(),
        );

        return $this->json([
            'items' => $items,
            'totalCount' => count($items),
        ]);
    }

    #[Route('/{id<\d+>}', name: 'detail', methods: ['GET'])]
    public function detail(int $id): JsonResponse
    {
        $row = $this->gateway->fetchById($id);

        if ($row === null) {
            throw new ResourceNotFoundException('Long note', $id);
        }

        return $this->json($this->mapper->map($row)->toArray());
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $input = LongNoteInput::fromArray($this->jsonRequestDecoder->decode($request));
        $view = $this->mapper->map($this->gateway->create($input));

        return $this->json($view->toArray(), Response::HTTP_CREATED);
    }

    #[Route('/{id<\d+>}', name: 'update', methods: ['PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        if ($this->gateway->fetchById($id) === null) {
            throw new ResourceNotFoundException('Long note', $id);
        }

        $input = LongNoteInput::fromArray($this->jsonRequestDecoder->decode($request), true);
        $row = $this->gateway->update($id, $input);

        if ($row === null) {
            throw new ResourceNotFoundException('Long note', $id);
        }

        return $this->json($this->mapper->map($row)->toArray());
    }
}
