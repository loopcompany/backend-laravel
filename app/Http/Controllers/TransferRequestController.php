<?php

namespace App\Http\Controllers;

use App\DTOs\TransferRequestDTO;
use App\Http\Requests\StoreTransferRequestRequest;
use App\Services\TransferRequestService;
use Illuminate\Http\JsonResponse;

class TransferRequestController extends Controller
{
    public function __construct(
        private TransferRequestService $service
    ) {}

    /**
     * Store a new transfer request
     *
     * @param StoreTransferRequestRequest $request
     * @return JsonResponse
     */
    public function store(StoreTransferRequestRequest $request): JsonResponse
    {
        $technicianId = auth('sanctum')->id();

        $dto = TransferRequestDTO::fromRequest([
            'technician_id' => $technicianId,
            'type' => $request->type,
            'description' => $request->description,
        ]);

        $result = $this->service->create($dto);

        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Get all transfer requests for authenticated technician
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $technicianId = auth('sanctum')->id();

        $result = $this->service->getListByTechnician($technicianId);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get details of a specific transfer request
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $technicianId = auth('sanctum')->id();

        $result = $this->service->getDetail($id, $technicianId);

        return response()->json($result, $result['success'] ? 200 : 404);
    }
}
