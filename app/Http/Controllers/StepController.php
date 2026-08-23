<?php

namespace App\Http\Controllers;

use App\Http\Requests\FetchStepsRequest;
use App\Http\Requests\FetchConditionalStepsRequest;
use App\Services\StepService;
use Illuminate\Support\Facades\Log;

class StepController extends Controller
{
    public function fetch_steps(FetchStepsRequest $request, StepService $service)
    {
        // دریافت account_type کاربر لاگین شده
        $user = $request->user();
        $accountType = $user ? $user->account_type : null;
        
       
        $data = $service->fetchSteps($request->categoryId, $accountType);
        
        return response()->json($data);
    }

    public function fetch_conditional_steps(FetchConditionalStepsRequest $request, StepService $service)
    {
        $data = $service->fetchConditionalSteps($request->categoryId, $request->fieldId, $request->fieldDetailId);
        return response()->json($data);
    }
}
