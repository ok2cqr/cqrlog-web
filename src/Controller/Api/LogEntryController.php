<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Api\Exception\ResourceNotFoundException;
use App\Api\Http\JsonRequestDecoder;
use App\Api\LogEntry\LogEntryGateway;
use App\Api\LogEntry\LogEntryInput;
use App\Api\LogEntry\LogEntryListQuery;
use App\Api\LogEntry\LogEntryMapper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/logEntries', name: 'api_log_entries_')]
final class LogEntryController extends AbstractController
{
    public function __construct(
        private readonly LogEntryGateway $gateway,
        private readonly LogEntryMapper $mapper,
        private readonly JsonRequestDecoder $jsonRequestDecoder,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $query = LogEntryListQuery::fromArray($request->query->all());
        $totalCount = $this->gateway->count($query);
        $items = array_map(
            fn ($row) => $this->mapper->map($row)->toArray(),
            $this->gateway->fetchAll($query),
        );

        return $this->json([
            'items' => $items,
            'totalCount' => $totalCount,
            'page' => $query->page,
            'perPage' => $query->perPage,
            'totalPages' => max(1, (int) ceil($totalCount / $query->perPage)),
            'sortBy' => $query->sortBy,
            'sortDirection' => $query->sortDirection,
        ]);
    }

    #[Route('/{id<\d+>}', name: 'detail', methods: ['GET'])]
    public function detail(int $id): JsonResponse
    {
        $row = $this->gateway->fetchById($id);

        if ($row === null) {
            throw new ResourceNotFoundException('Log entry', $id);
        }

        return $this->json($this->mapper->map($row)->toArray());
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $input = LogEntryInput::fromArray($this->jsonRequestDecoder->decode($request));
        $view = $this->mapper->map($this->gateway->create($input));

        return $this->json($view->toArray(), Response::HTTP_CREATED);
    }

    #[Route('/{id<\d+>}', name: 'update', methods: ['PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        if ($this->gateway->fetchById($id) === null) {
            throw new ResourceNotFoundException('Log entry', $id);
        }

        $input = LogEntryInput::fromArray($this->jsonRequestDecoder->decode($request), true);
        $row = $this->gateway->update($id, $input);

        if ($row === null) {
            throw new ResourceNotFoundException('Log entry', $id);
        }

        return $this->json($this->mapper->map($row)->toArray());
    }
}
