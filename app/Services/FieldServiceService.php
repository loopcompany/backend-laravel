<?php

namespace App\Services;

use App\Models\FieldServiceVisit;
use App\Models\Order;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FieldServiceService
{
    public function technicianOrder(Technician $technician, int $orderId): Order
    {
        $order = Order::with(['user.organization', 'user_address', 'category'])
            ->findOrFail($orderId);

        abort_unless((int) $order->technician_id === (int) $technician->id, 403, 'این سفارش به شما تخصیص داده نشده است.');
        abort_unless($order->user?->isOrganization(), 422, 'گزارش روزانه فقط برای سفارش سازمانی است.');

        return $order;
    }

    public function organizationOrder(User $user, int $orderId): Order
    {
        abort_unless($user->isOrganization(), 403, 'دسترسی مخصوص حساب سازمانی است.');
        $order = Order::with(['user.organization', 'user_address', 'category'])
            ->findOrFail($orderId);
        abort_unless((int) $order->user_id === (int) $user->id, 403, 'این سفارش متعلق به سازمان شما نیست.');

        return $order;
    }

    public function start(Order $order, Technician $technician, array $location): FieldServiceVisit
    {
        return DB::transaction(function () use ($order, $technician, $location) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            abort_if(in_array((int) $order->status, [2, 3, 4, 5, 6], true), 409, 'سفارش بسته شده است.');
            abort_if($order->fieldServiceVisits()->whereIn('status', [
                FieldServiceVisit::IN_PROGRESS,
                FieldServiceVisit::SUBMITTED,
                FieldServiceVisit::CHANGES_REQUESTED,
            ])->exists(), 409, 'ابتدا گزارش بازدید قبلی را تکمیل یا بررسی کنید.');

            return $order->fieldServiceVisits()->create([
                'technician_id' => $technician->id,
                'status' => FieldServiceVisit::IN_PROGRESS,
                'started_at' => now(),
                'latitude' => $location['latitude'] ?? null,
                'longitude' => $location['longitude'] ?? null,
            ]);
        });
    }

    public function editableVisit(Order $order, int $visitId, Technician $technician): FieldServiceVisit
    {
        $visit = $order->fieldServiceVisits()->findOrFail($visitId);
        abort_unless((int) $visit->technician_id === (int) $technician->id, 403, 'این بازدید متعلق به شما نیست.');
        abort_unless(in_array($visit->status, [FieldServiceVisit::IN_PROGRESS, FieldServiceVisit::CHANGES_REQUESTED], true),
            409, 'گزارش در این وضعیت قابل تغییر نیست.');

        return $visit;
    }

    public function submit(Order $order, int $visitId, Technician $technician, array $data): FieldServiceVisit
    {
        return DB::transaction(function () use ($order, $visitId, $technician, $data) {
            $visit = $order->fieldServiceVisits()->lockForUpdate()->findOrFail($visitId);
            abort_unless((int) $visit->technician_id === (int) $technician->id, 403);
            abort_unless(in_array($visit->status, [FieldServiceVisit::IN_PROGRESS, FieldServiceVisit::CHANGES_REQUESTED], true), 409);
            abort_unless((int) $order->technician_id === (int) $technician->id, 403);

            $visit->fill($data);
            $visit->finished_at ??= now();
            $visit->submitted_at = now();
            $visit->status = FieldServiceVisit::SUBMITTED;
            $visit->reviewed_at = null;
            $visit->reviewed_by_user_id = null;
            $visit->review_note = null;
            $visit->save();

            return $visit;
        });
    }

    public function review(Order $order, int $visitId, User $user, string $decision, ?string $note): FieldServiceVisit
    {
        return DB::transaction(function () use ($order, $visitId, $user, $decision, $note) {
            $visit = $order->fieldServiceVisits()->lockForUpdate()->findOrFail($visitId);
            abort_unless($visit->status === FieldServiceVisit::SUBMITTED, 409, 'این گزارش آمادهٔ بررسی نیست.');

            $visit->status = $decision === 'approve' ? FieldServiceVisit::APPROVED : FieldServiceVisit::CHANGES_REQUESTED;
            $visit->reviewed_by_user_id = $user->id;
            $visit->reviewed_at = now();
            $visit->review_note = $note;
            $visit->save();
            $visit->reviews()->create([
                'user_id' => $user->id,
                'decision' => $decision,
                'note' => $note,
            ]);

            // Confirmation of the report and completion of the mission are separate.
            if ($decision === 'approve' && !$visit->has_remaining_work && $visit->outcome === 'completed') {
                $order->status = 2;
                $order->save();
            }

            return $visit;
        });
    }

    public function payload(Order $order): array
    {
        $order->loadMissing(['user.organization', 'user_address', 'category']);
        $visits = $order->fieldServiceVisits()
            ->with(['technician:id,name', 'reviewer:id,name,last_name', 'photos', 'reviews.reviewer:id,name,last_name'])
            ->get();

        return [
            'order' => [
                'id' => $order->id,
                'status' => $order->status,
                'date' => $order->date,
                'request' => $order->des,
                'service' => $order->category?->title,
                'organization' => $order->user?->organization?->organization_name,
                'location' => [
                    'building' => $order->user_address?->title,
                    'address' => $order->user_address?->address,
                    'unit' => $order->user_address?->unit,
                    'floor' => $order->user_address?->floor,
                    'room' => $order->user_address?->room,
                    'number' => $order->user_address?->number,
                ],
            ],
            'work_status' => $this->workStatus($visits->first()),
            'visits' => $visits->map(fn (FieldServiceVisit $visit) => [
                'id' => $visit->id,
                'technician' => $visit->technician?->name,
                'status' => $visit->status,
                'started_at' => $visit->started_at?->toIso8601String(),
                'finished_at' => $visit->finished_at?->toIso8601String(),
                'duration_minutes' => $visit->finished_at ? $visit->started_at->diffInMinutes($visit->finished_at) : null,
                'latitude' => $visit->latitude,
                'longitude' => $visit->longitude,
                'site_condition' => $visit->site_condition,
                'problem_description' => $visit->problem_description,
                'actions' => $visit->actions ?? [],
                'has_remaining_work' => $visit->has_remaining_work,
                'remaining_action' => $visit->remaining_action,
                'continuation_owner' => $visit->continuation_owner,
                'continuation_note' => $visit->continuation_note,
                'outcome' => $visit->outcome,
                'submitted_at' => $visit->submitted_at?->toIso8601String(),
                'reviewed_at' => $visit->reviewed_at?->toIso8601String(),
                'reviewed_by' => $visit->reviewer ? trim($visit->reviewer->name . ' ' . $visit->reviewer->last_name) : null,
                'review_note' => $visit->review_note,
                'reviews' => $visit->reviews->map(fn ($review) => [
                    'decision' => $review->decision,
                    'note' => $review->note,
                    'reviewed_by' => $review->reviewer?->name,
                    'created_at' => $review->created_at?->toIso8601String(),
                ])->all(),
                'photos' => $visit->photos->map(fn ($photo) => [
                    'id' => $photo->id,
                    'kind' => $photo->kind,
                    'url' => $photo->temporaryUrl(),
                ])->all(),
            ])->all(),
        ];
    }

    private function workStatus(?FieldServiceVisit $visit): string
    {
        if (!$visit) return 'awaiting_visit';
        if ($visit->status === FieldServiceVisit::IN_PROGRESS) return 'in_progress';
        if ($visit->status === FieldServiceVisit::SUBMITTED) return 'awaiting_approval';
        if ($visit->status === FieldServiceVisit::CHANGES_REQUESTED) return 'changes_requested';

        return $visit->has_remaining_work ? 'follow_up_required' : 'completed';
    }
}
