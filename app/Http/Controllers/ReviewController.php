<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitReviewRequest;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(
        protected ReviewService $reviewService
    ) {}

    public function dismiss(Request $request): JsonResponse
    {
        $actor=$request->user();
        abort_unless($actor instanceof \App\Models\User,403);
        $data=$request->validate(['order_id'=>'required|integer']);
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data,$actor) {
            $order=\App\Models\Order::where('user_id',$actor->id)->lockForUpdate()->findOrFail($data['order_id']);
            abort_unless((int)$order->status===2,422,'این گزینه برای پرونده مختومه است.');
            if (!$order->review_dismissed_at) $order->forceFill(['review_dismissed_at'=>now()])->save();
            return response()->json(['success'=>true,'message'=>'نظر و امتیاز اختیاری است؛ یادآوری این پرونده بسته شد.']);
        });
    }

    /**
     * ثبت نظر برای یک سفارش
     * 
     * @param SubmitReviewRequest $request
     * @return JsonResponse
     */
    public function submitReview(SubmitReviewRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof \App\Models\User,403);
        $validated = $request->validated();

        $result = $this->reviewService->submitReview($user->id, $validated);

        if (!$result['success']) {
            $statusCode = match ($result['error_code']) {
                'ORDER_NOT_FOUND' => 404,
                'ORDER_NOT_COMPLETED' => 400,
                'TECHNICIAN_MISMATCH' => 400,
                'REVIEW_ALREADY_EXISTS' => 409,
                default => 500
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code']
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ], 201);
    }

    /**
     * دریافت نظرات یک تکنسین
     * 
     * @param int $technicianId
     * @param Request $request
     * @return JsonResponse
     */
    public function getTechnicianReviews(int $technicianId, Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 20);

        $result = $this->reviewService->getTechnicianReviews($technicianId, $perPage);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code']
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data']
        ]);
    }

    /**
     * دریافت نظرات خود کاربر
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getMyReviews(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 20);

        $result = $this->reviewService->getUserReviews($user->id, $perPage);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code']
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data']
        ]);
    }
}
