<?php

namespace App\Http\Controllers;

use App\DTOs\ManpowerRequestDTO;
use App\Http\Requests\StoreManpowerRequestRequest;
use App\Services\ManpowerRequestService;
use Illuminate\Http\JsonResponse;

class ManpowerRequestController extends Controller
{
    public function __construct(
        private ManpowerRequestService $service
    ) {}

    /**
     * Store a new manpower request
     *
     * @param StoreManpowerRequestRequest $request
     * @return JsonResponse
     */
    public function store(StoreManpowerRequestRequest $request): JsonResponse
    {
        $technicianId = auth('sanctum')->id();

        $dto = ManpowerRequestDTO::fromRequest([
            'technician_id' => $technicianId,
            'type' => $request->type,
            'description' => $request->description,
        ]);

        $result = $this->service->create($dto);

        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Get all manpower requests for authenticated technician
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
     * Get details of a specific manpower request
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
