<?php

namespace App\Http\Controllers;

use App\DTOs\PollApplicationDTO;
use App\Http\Requests\StorePollApplicationRequest;
use App\Services\PollApplicationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class PollApplicationController extends Controller
{
    public function __construct(
        private readonly PollApplicationService $service
    ) {}

    /**
     * Submit poll application
     */
    public function store(StorePollApplicationRequest $request): JsonResponse
    {
        try {
            $dto = PollApplicationDTO::fromArray(
                $request->validated(),
                auth()->id()
            );

            $result = $this->service->submitPoll($dto);

            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
                'data' => $result['poll']
            ], 201);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);

        } catch (\Exception $e) {
            \Log::info($e);
            return response()->json([
                'status' => 'error',
                'message' => 'خطایی در ثبت نظرسنجی رخ داد.'
            ], 500);
        }
    }

    /**
     * Get user's poll application
     */
    public function show(): JsonResponse
    {
        try {
            $result = $this->service->getUserPoll(auth()->id());

            return response()->json([
                'status' => 'success',
                'data' => $result['poll']
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطایی در دریافت نظرسنجی رخ داد.'
            ], 500);
        }
    }

    /**
     * Check if user can participate
     */
    public function canParticipate(): JsonResponse
    {
        try {
            $result = $this->service->canUserParticipate(auth()->id());

            return response()->json([
                'status' => 'success',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطایی رخ داد.'
            ], 500);
        }
    }

    /**
     * Get poll statistics (admin only)
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->service->getStatistics();

            return response()->json([
                'status' => 'success',
                'data' => $statistics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطایی در دریافت آمار رخ داد.'
            ], 500);
        }
    }
}