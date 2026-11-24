<?php

namespace App\Http\Controllers;

use App\Models\Technician;
use App\Services\TechnicianIdentityService;
use Illuminate\Http\Request;

class TechnicianIdentityController extends Controller
{
    public function verify(Request $request, TechnicianIdentityService $service)
    {
        $data = $request->validate(['qr' => ['required', 'string', 'max:500']]);
        $identity = $service->verify($data['qr']);
        return response()->json(['verified' => $identity !== null, 'data' => $identity], $identity ? 200 : 404)->header('Cache-Control', 'no-store');
    }

    public function publicCard(string $uuid, TechnicianIdentityService $service)
    {
        return response()->view('main.technician-identity', ['identity' => $service->verify($uuid)])->header('Cache-Control', 'no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function download(Technician $technician, TechnicianIdentityService $service)
    {
        abort_unless(\App\Filament\Resources\TechnicianResource::canEdit($technician), 403);
        return response($service->card($technician))->header('Content-Type', 'image/svg+xml; charset=UTF-8')->header('Content-Disposition', 'attachment; filename="Loop-Technician-'.$technician->id.'.svg"')->header('Cache-Control', 'no-store');
    }
}
