<?php

namespace App\Http\Controllers;

use App\DTOs\LoopLearnRegisterationDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLoopLearnRegistrationRequest;
use App\Services\LoopLearnRegistrationService;
use Illuminate\Http\JsonResponse;

class LoopLearnRegistrationController extends Controller
{
    public function __construct(
        protected LoopLearnRegistrationService $service
    ) {
    }

    public function store(StoreLoopLearnRegistrationRequest $request)
    {
        try {
            $dto = LoopLearnRegisterationDTO::fromRequest($request);
            $this->service->register($dto);

            return redirect()->back()->with('success', __("Your information submitted successfully."));
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'خطایی رخ داد: ' . $e->getMessage()])->withInput();
        }
    }
    public function apiStore(StoreLoopLearnRegistrationRequest $request)
    {
        try {
            $dto = LoopLearnRegisterationDTO::fromRequest($request);
            $this->service->register($dto);

            return response()->json(['message' => __("Registration was successful."), 'success' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => __("An error occurred"), 'success' => false], 429);

        }
    }
    public function index()
    {
        return view('main.learn-register');
    }
}
