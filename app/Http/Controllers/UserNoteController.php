<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserNoteRequest;
use App\Services\UserNoteService;
use Illuminate\Http\JsonResponse;

class UserNoteController extends Controller
{
    public function __construct(
        private UserNoteService $service
    ) {}

    /**
     * ثبت یادداشت جدید
     *
     * @param StoreUserNoteRequest $request
     * @return JsonResponse
     */
    public function store(StoreUserNoteRequest $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است'
            ], 401);
        }

        $result = $this->service->createNote(
            $request->validated(),
            $user->id
        );

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        return response()->json($result, 201);
    }

    /**
     * لیست یادداشت‌های کاربر
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است'
            ], 401);
        }

        $result = $this->service->getUserNotes($user->id);

        return response()->json($result, 200);
    }

    /**
     * نمایش جزئیات یک یادداشت
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است'
            ], 401);
        }

        $result = $this->service->getNoteDetail($id, $user->id);

        if (!$result['success']) {
            $statusCode = $result['error_code'] === 'NOT_FOUND' ? 404 : 400;
            return response()->json($result, $statusCode);
        }

        return response()->json($result, 200);
    }

    /**
     * بروزرسانی یادداشت
     *
     * @param StoreUserNoteRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(StoreUserNoteRequest $request, int $id): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است'
            ], 401);
        }

        $result = $this->service->updateNote(
            $id,
            $request->validated(),
            $user->id
        );

        if (!$result['success']) {
            $statusCode = $result['error_code'] === 'NOT_FOUND' ? 404 : 400;
            return response()->json($result, $statusCode);
        }

        return response()->json($result, 200);
    }

    /**
     * حذف یادداشت
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است'
            ], 401);
        }

        $result = $this->service->deleteNote($id, $user->id);

        if (!$result['success']) {
            $statusCode = $result['error_code'] === 'NOT_FOUND' ? 404 : 400;
            return response()->json($result, $statusCode);
        }

        return response()->json($result, 200);
    }
}
