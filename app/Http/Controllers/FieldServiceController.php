<?php

namespace App\Http\Controllers;

use App\Models\FieldServicePhoto;
use App\Models\FieldServiceVisit;
use App\Models\Technician;
use App\Models\User;
use App\Services\FieldServiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FieldServiceController extends Controller
{
    public function __construct(private FieldServiceService $service) {}

    public function technicianIndex(Request $request, int $order): JsonResponse
    {
        $technician = $request->user();
        abort_unless($technician instanceof Technician, 403);

        return response()->json(['success' => true, 'data' => $this->service->payload(
            $this->service->technicianOrder($technician, $order)
        )]);
    }

    public function organizationIndex(Request $request, int $order): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return response()->json(['success' => true, 'data' => $this->service->payload(
            $this->service->organizationOrder($user, $order)
        )]);
    }

    public function start(Request $request, int $order): JsonResponse
    {
        $technician = $request->user();
        abort_unless($technician instanceof Technician, 403);
        $data = $request->validate([
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ]);
        $order = $this->service->technicianOrder($technician, $order);
        $visit = $this->service->start($order, $technician, $data);

        return response()->json(['success' => true, 'data' => [
            'visit_id' => $visit->id,
            'started_at' => $visit->started_at->toIso8601String(),
            'status' => $visit->status,
        ]], 201);
    }

    public function submit(Request $request, int $order, int $visit): JsonResponse
    {
        $technician = $request->user();
        abort_unless($technician instanceof Technician, 403);
        $order = $this->service->technicianOrder($technician, $order);
        $data = $request->validate([
            'site_condition' => ['required', Rule::in(FieldServiceVisit::CONDITIONS)],
            'problem_description' => ['nullable', 'string', 'max:2000'],
            'actions' => ['required', 'array', 'min:1', 'max:20'],
            'actions.*' => ['required', 'string', 'max:1000'],
            'has_remaining_work' => ['required', 'boolean'],
            'remaining_action' => ['nullable', 'string', 'max:2000'],
            'continuation_owner' => ['nullable', Rule::in(FieldServiceVisit::CONTINUATION_OWNERS)],
            'continuation_note' => ['nullable', 'string', 'max:2000'],
            'outcome' => ['required', Rule::in(FieldServiceVisit::OUTCOMES)],
        ]);

        if (in_array($data['site_condition'], ['problem', 'broken'], true) && blank($data['problem_description'] ?? null)) {
            throw ValidationException::withMessages(['problem_description' => 'شرح مشکل الزامی است.']);
        }
        if ($data['has_remaining_work']) {
            if (blank($data['remaining_action'] ?? null) || blank($data['continuation_owner'] ?? null)) {
                throw ValidationException::withMessages(['remaining_action' => 'کار باقیمانده و مسئول ادامهٔ کار الزامی است.']);
            }
            if ($data['outcome'] === 'completed') {
                throw ValidationException::withMessages(['outcome' => 'با وجود کار باقیمانده، نتیجه نمی‌تواند تکمیل‌شده باشد.']);
            }
        } else {
            if ($data['outcome'] !== 'completed') {
                throw ValidationException::withMessages(['outcome' => 'اگر کاری باقی نمانده است، نتیجه باید completed باشد.']);
            }
            $data['remaining_action'] = null;
            $data['continuation_owner'] = null;
            $data['continuation_note'] = null;
        }

        $visit = $this->service->submit($order, $visit, $technician, $data);

        return response()->json(['success' => true, 'data' => [
            'visit_id' => $visit->id,
            'status' => $visit->status,
            'finished_at' => $visit->finished_at->toIso8601String(),
        ]]);
    }

    public function uploadPhoto(Request $request, int $order, int $visit): JsonResponse
    {
        $technician = $request->user();
        abort_unless($technician instanceof Technician, 403);
        $data = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            'kind' => ['required', Rule::in(['before', 'after', 'problem', 'part', 'other'])],
        ]);
        $order = $this->service->technicianOrder($technician, $order);
        $visit = $this->service->editableVisit($order, $visit, $technician);
        abort_if($visit->photos()->count() >= 12, 422, 'حداکثر ۱۲ عکس برای هر بازدید مجاز است.');

        $file = $data['photo'];
        $path = $file->store("field-service/{$order->id}/{$visit->id}", 'local');
        $photo = $visit->photos()->create([
            'path' => $path,
            'kind' => $data['kind'],
            'original_name' => $file->getClientOriginalName(),
        ]);

        return response()->json(['success' => true, 'data' => [
            'id' => $photo->id, 'kind' => $photo->kind, 'url' => $photo->temporaryUrl(),
        ]], 201);
    }

    public function deletePhoto(Request $request, int $order, int $visit, int $photo): JsonResponse
    {
        $technician = $request->user();
        abort_unless($technician instanceof Technician, 403);
        $order = $this->service->technicianOrder($technician, $order);
        $visit = $this->service->editableVisit($order, $visit, $technician);
        $photo = $visit->photos()->findOrFail($photo);
        Storage::disk('local')->delete($photo->path);
        $photo->delete();

        return response()->json(['success' => true]);
    }

    public function review(Request $request, int $order, int $visit): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'request_changes'])],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        if ($data['decision'] === 'request_changes' && blank($data['note'] ?? null)) {
            throw ValidationException::withMessages(['note' => 'توضیح اصلاح موردنیاز است.']);
        }
        $order = $this->service->organizationOrder($user, $order);
        $visit = $this->service->review($order, $visit, $user, $data['decision'], $data['note'] ?? null);

        return response()->json(['success' => true, 'data' => [
            'visit_id' => $visit->id, 'status' => $visit->status,
            'work_status' => $this->service->payload($order)['work_status'],
        ]]);
    }

    public function photo(FieldServicePhoto $photo): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($photo->path), 404);

        return Storage::disk('local')->response($photo->path, $photo->original_name, [
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
