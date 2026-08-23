<?php

namespace App\Http\Controllers;

use App\Models\Expertise;
use Illuminate\Http\JsonResponse;

class ExpertiseController extends Controller
{
    public function index(): JsonResponse
    {
        $expertises = Expertise::select('id', 'title')
            ->orderBy('title')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Expertises retrieved successfully',
            'data' => $expertises,
        ]);
    }
}