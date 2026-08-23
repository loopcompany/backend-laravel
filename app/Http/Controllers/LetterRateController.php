<?php

namespace App\Http\Controllers;

use App\Models\LetterRate;
use App\Models\LetterRateCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LetterRateController extends Controller
{
    /**
     * Get all letter rates
     */
    public function index(): JsonResponse
    {
        try {
            $letterRates = LetterRate::with('category')->get();

            return response()->json([
                'status' => 'success',
                'data' => $letterRates
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطایی در دریافت لیست نرخ‌ها رخ داد.'
            ], 500);
        }
    }

    /**
     * Get all letter rate categories
     */
    public function categories(): JsonResponse
    {
        try {
            $categories = LetterRateCategory::withCount('letterRates')
                ->orderByDesc('id')
                ->with('letterRates')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطایی در دریافت لیست دسته‌بندی‌ها رخ داد.'
            ], 500);
        }
    }

    /**
     * Get letter rates by category
     */
    public function byCategory(Request $request, $categoryId = null): JsonResponse
    {
        try {
            $query = LetterRate::with('category');

            if ($categoryId) {
                $query->where('letter_rate_category_id', $categoryId);
            } elseif ($request->has('category_id')) {
                $query->where('letter_rate_category_id', $request->category_id);
            }

            // Filter by type if provided
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            $letterRates = $query->get();

            return response()->json([
                'status' => 'success',
                'data' => $letterRates
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطایی در دریافت لیست نرخ‌ها رخ داد.'
            ], 500);
        }
    }
}
