<?php

namespace App\Http\Controllers;

use App\DTOs\TerminationRequestDTO;
use App\Http\Requests\StoreTerminationRequestRequest;
use App\Services\TerminationRequestService;
use Illuminate\Http\JsonResponse;

class TerminationRequestController extends Controller
{
    public function __construct(
        private TerminationRequestService $service
    ) {}

    /**
     * Store a new termination request
     *
     * @param StoreTerminationRequestRequest $request
     * @return JsonResponse
     */
    public function store(StoreTerminationRequestRequest $request): JsonResponse
    {
        $technicianId = auth('sanctum')->id();

        $dto = TerminationRequestDTO::fromRequest([
            'technician_id' => $technicianId,
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'description' => $request->description,
        ]);

        $result = $this->service->create($dto);

        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Get all termination requests for authenticated technician
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
     * Get details of a specific termination request
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
