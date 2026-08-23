<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrganizationUpdateRequest;
use App\Services\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function __construct(
        private OrganizationService $organizationService
    ) {}

    /**
     * دریافت اطلاعات سازمان کاربر فعلی
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isOrganization()) {
            return response()->json([
                'status' => 'error',
                'message' => 'فقط کاربران سازمانی می‌توانند از این بخش استفاده کنند.',
            ], 403);
        }

        $result = $this->organizationService->getOrganizationByUserId($user->id);

        if ($result['status'] === 'error') {
            return response()->json($result, 404);
        }

        return response()->json($result);
    }

    /**
     * به‌روزرسانی اطلاعات سازمان
     */
    public function update(OrganizationUpdateRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('profile_image')) {
            $data['profile_image'] = $request->file('profile_image');
        }

        $result = $this->organizationService->updateOrganization(
            $request->user()->id,
            $data
        );

        if ($result['status'] === 'error') {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    /**
     * دریافت وضعیت تایید و دسترسی سازمان
     */
    public function getStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isOrganization()) {
            return response()->json([
                'success' => false,
                'message' => 'فقط کاربران سازمانی می‌توانند از این بخش استفاده کنند.',
                'error_code' => 'INVALID_USER_TYPE'
            ], 403);
        }

        $result = $this->organizationService->getAccessStatus($user->id);

        if (!$result['success']) {
            return response()->json($result, 404);
        }

        return response()->json($result);
    }
}