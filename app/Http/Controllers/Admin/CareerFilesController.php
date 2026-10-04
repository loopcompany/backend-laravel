<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CooperationRequest;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * دانلود رزومه، مدارک و نمونه‌کار متقاضی همکاری — فقط برای ادمین با مجوز مشاهده‌ی درخواست‌ها.
 */
class CareerFilesController extends Controller
{
    private const FILES = [
        'resume' => 'resume_path',
        'certificates' => 'certificates_path',
        'portfolio' => 'portfolio_path',
    ];

    public function show(CooperationRequest $cooperationRequest, string $type): StreamedResponse
    {
        abort_unless(auth('admin')->user()?->can('view-cooperation-requests'), 403);
        abort_unless(isset(self::FILES[$type]), 404);

        $path = $cooperationRequest->{self::FILES[$type]};
        abort_if(!$path || !Storage::disk(CooperationRequest::DISK)->exists($path), 404);

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return Storage::disk(CooperationRequest::DISK)->response(
            $path,
            "{$cooperationRequest->tracking_code}-{$type}.{$extension}"
        );
    }
}
