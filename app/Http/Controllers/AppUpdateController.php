<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\AppReleaseService;
class AppUpdateController extends Controller
{
    public function check(Request $request, AppReleaseService $service)
    {
        $input = $request->validate(['app' => 'required|in:customer,technician,desktop', 'platform' => 'required|in:android,windows',
            'variant' => 'nullable|in:universal,win10-x64,win10-ia32,win7-x64,win7-ia32', 'version' => ['nullable', 'string', 'regex:/^\d+\.\d+\.\d+$/D'],
            'version_code' => 'nullable|integer|min:0|max:2147483647']);
        return response()->json($service->check($input))->header('Cache-Control', 'no-store');
    }
}
