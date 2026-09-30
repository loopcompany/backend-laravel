<?php

namespace App\Http\Middleware;

use App\Models\Technician;
use App\Support\TechnicianRestriction;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTechnicianHasAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $technician = $request->user();

        if ($technician instanceof Technician && !$technician->has_access) {
            return response()->json([
                ...TechnicianRestriction::error($technician),
                'valid' => false,
            ], 403);
        }

        return $next($request);
    }
}
