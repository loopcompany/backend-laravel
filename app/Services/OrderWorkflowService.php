<?php

namespace App\Services;

use App\Jobs\SendFirebaseNotificationJob;
use App\Models\Admin;
use App\Models\AppSetting;
use App\Models\Order;
use App\Models\OrderQuote;
use App\Models\OrderStageLog;
use App\Models\OrderWorkflow;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * فرآیند ثبت، بررسی، قیمت‌گذاری، تخصیص، مراجعه، قیمت‌گذاری مجدد، انجام در محل، ارجاع، لغو و پایان سفارش.
 * همه‌ی انتقال‌های مرحله از این سرویس می‌گذرند و در order_stage_logs ثبت می‌شوند (بند ۱۲-۴).
 * وضعیت قدیمی orders.status (که اپ‌ها می‌خوانند) هم‌زمان به‌روز می‌شود.
 */
class OrderWorkflowService
{
    public const USER_MESSAGE = 'کاربر گرامی، پیام جدیدی درباره سفارش شما ارسال شده است. لطفاً مشاهده فرمایید.';

    /** مقادیر ستون orders.platform */
    public const CHANNELS = [
        'android' => 'اپلیکیشن (اندروید)',
        'ios' => 'اپلیکیشن (iOS)',
        'web' => 'وب‌سایت',
        'windows' => 'نسخه ویندوز',
        'phone' => 'تماس تلفنی (ثبت در پنل)',
        'person' => 'حضوری',
    ];

    /** سفارش‌هایی که همین حالا توسط سرویس ذخیره می‌شوند (OrderObserver آن‌ها را دوباره همگام نمی‌کند). */
    private static array $managing = [];

    public static function isManaging(Order $order): bool
    {
        return isset(self::$managing[$order->getKey()]);
    }

    public static function channelLabel(?string $platform): string
    {
        return self::CHANNELS[strtolower((string) $platform)] ?? ($platform ?: 'نامشخص');
    }

    // ───────────────────────────── داده‌ها ─────────────────────────────

    public function details(Order $order): OrderWorkflow
    {
        if ($order->relationLoaded('workflow') && $order->workflow) {
            return $order->workflow;
        }

        $stage = $order->workflow_stage ?? OrderWorkflow::STAGE_REVIEW;
        $pastReview = $stage !== OrderWorkflow::STAGE_REVIEW;
        $pastAssignment = !in_array($stage, [OrderWorkflow::STAGE_REVIEW, OrderWorkflow::STAGE_QUOTING, OrderWorkflow::STAGE_ASSIGNMENT], true);

        // سفارش‌های قبل از این فرآیند: مراحل گذشته را انجام‌شده فرض می‌کنیم
        $workflow = OrderWorkflow::firstOrCreate(['order_id' => $order->id], [
            'customer_type' => OrderWorkflow::customerTypeFromAccount($order->user?->account_type),
            'review_status' => $pastReview ? 'approved' : 'pending',
            'assignment_status' => $order->technician_id ? ($pastAssignment ? 'accepted' : 'pending') : null,
        ]);

        $order->setRelation('workflow', $workflow);

        return $workflow;
    }

    public function stage(Order $order): string
    {
        return $order->workflow_stage ?? OrderWorkflow::STAGE_REVIEW;
    }

    /** آخرین قیمتی که برای کاربر ارسال شده (پیش‌نویس‌ها و پیشنهادهای ردشده‌ی پنل حساب نمی‌شوند). */
    public function customerQuote(Order $order, ?string $kind = null): ?OrderQuote
    {
        return $order->quotes()
            ->whereIn('status', [OrderQuote::STATUS_SENT, OrderQuote::STATUS_ACCEPTED, OrderQuote::STATUS_REJECTED])
            ->whereIn('kind', $kind ? [$kind] : [OrderQuote::KIND_INITIAL, OrderQuote::KIND_REVISED])
            ->reorder('version', 'desc')
            ->first();
    }

    /** نسخه‌ی باز (در انتظار بررسی پنل یا تصمیم کاربر). */
    public function openQuote(Order $order): ?OrderQuote
    {
        return $order->quotes()
            ->whereIn('status', [OrderQuote::STATUS_PENDING_REVIEW, OrderQuote::STATUS_SENT])
            ->reorder('version', 'desc')
            ->first();
    }

    /** نسخه‌ی تأییدشده‌ی معتبر (شرط شروع خدمت، بند ۸-۱). */
    public function acceptedQuote(Order $order): ?OrderQuote
    {
        return $order->quotes()
            ->where('status', OrderQuote::STATUS_ACCEPTED)
            ->reorder('version', 'desc')
            ->first();
    }

    // ───────────────────────────── ۲ — ثبت ─────────────────────────────

    public function onCreated(Order $order): void
    {
        $this->log($order, null, $this->stage($order), 'created', 'ثبت سفارش از کانال «' . self::channelLabel($order->platform) . '» با شماره پیگیری ' . $order->id, [
            'platform' => $order->platform,
            // زمان درخواستی کاربر؛ orders.date / time بعداً با زمان مراجعه‌ی تعیین‌شده عوض می‌شوند
            'requested_date' => $order->date ? \Illuminate\Support\Carbon::parse($order->date)->toDateString() : null,
            'requested_time' => $order->time,
        ]);
    }

    // ───────────────────────────── ۳ — بررسی ─────────────────────────────

    public function classify(Order $order, array $data): void
    {
        $this->expectStage($order, [OrderWorkflow::STAGE_REVIEW, OrderWorkflow::STAGE_QUOTING, OrderWorkflow::STAGE_ASSIGNMENT]);

        $data = array_intersect_key($data, array_flip(['service_type', 'customer_type', 'requester_name', 'requester_phone', 'contract_reference']));
        $this->expectOption($data['service_type'] ?? null, OrderWorkflow::SERVICE_TYPES, 'نوع خدمت');
        $this->expectOption($data['customer_type'] ?? null, OrderWorkflow::CUSTOMER_TYPES, 'نوع کاربر');

        $workflow = $this->details($order);
        $workflow->fill($data);
        $changes = $workflow->getDirty();
        $workflow->save();

        if ($changes) {
            $this->log($order, null, null, 'classified', OrderWorkflow::SERVICE_TYPES[$workflow->service_type] ?? null, ['changes' => array_keys($changes)]);
        }
    }

    /** ۳-۳ — اطلاعات ناقص: سفارش در مرحله‌ی بررسی می‌ماند و تخصیص تکنسین ممکن نیست. */
    public function markIncomplete(Order $order, string $note): void
    {
        $this->expectStage($order, [OrderWorkflow::STAGE_REVIEW]);
        $this->expectFilled($note, 'شرح اطلاعات ناقص');

        $this->details($order)->update(['review_status' => 'incomplete', 'review_note' => $note]);
        $this->log($order, null, null, 'review_incomplete', $note);
        $this->notify($order->user, 'تکمیل اطلاعات سفارش', self::USER_MESSAGE, $order, 'order_info_incomplete');
    }

    public function approveReview(Order $order, ?string $note = null): void
    {
        $this->expectStage($order, [OrderWorkflow::STAGE_REVIEW]);
        $workflow = $this->details($order);

        if (!$workflow->service_type || !$workflow->customer_type) {
            throw new OrderWorkflowException('ابتدا دسته‌ی درخواست و نوع کاربر را مشخص کنید (بند ۳-۴ و ۳-۶).');
        }
        // ۲-۳ — سفارش سازمانی / شرکتی بدون شخص درخواست‌دهنده تأیید نمی‌شود
        if ($workflow->customer_type !== 'individual' && blank($workflow->requester_name)) {
            throw new OrderWorkflowException('برای سفارش سازمانی / شرکتی، نام شخص درخواست‌دهنده را ثبت کنید.');
        }

        $workflow->update([
            'review_status' => 'approved',
            'review_note' => $note,
            'reviewed_at' => now(),
            'reviewed_by' => auth('admin')->id(),
        ]);

        $this->moveTo($order, OrderWorkflow::STAGE_QUOTING, 'review_approved', $note);
    }

    /** ۳-۵ — از ابتدا مشخص است که انجام در محل ممکن نیست. */
    public function routeToLoop(Order $order, string $reason): void
    {
        $this->expectStage($order, [OrderWorkflow::STAGE_REVIEW, OrderWorkflow::STAGE_QUOTING]);
        $this->expectFilled($reason, 'دلیل ارجاع');

        $workflow = $this->details($order);
        $workflow->update([
            'service_type' => $workflow->service_type ?: 'pickup_to_loop',
            'review_status' => 'approved',
            'reviewed_at' => $workflow->reviewed_at ?? now(),
            'reviewed_by' => $workflow->reviewed_by ?? auth('admin')->id(),
            'route_to_loop_reason' => $reason,
            'transfer_reason' => $reason,
        ]);

        $this->moveTo($order, OrderWorkflow::STAGE_TRANSFER, 'routed_to_loop', $reason);
    }

    // ───────────────────────────── ۴ و ۷ — قیمت ─────────────────────────────

    /**
     * ثبت نسخه‌ی جدید قیمت. پیشنهاد تکنسین همیشه اول به بررسی پنل می‌رود (بند ۷-۲).
     *
     * @param  array{service_fee?:int, parts_fee?:int, extra_fee?:int, is_estimate?:bool, estimated_duration?:string,
     *               visit_date?:string, visit_time?:string, parts_description?:string, change_reason?:string,
     *               description?:string, requires_user_approval?:bool}  $data
     */
    public function createQuote(Order $order, string $kind, array $data, bool $send = true, ?array $actor = null): OrderQuote
    {
        $actor ??= $this->actor();

        if ($kind === OrderQuote::KIND_INITIAL) {
            $this->expectStage($order, [OrderWorkflow::STAGE_QUOTING]);
        } elseif ($kind === OrderQuote::KIND_REVISED) {
            $this->expectStage($order, [OrderWorkflow::STAGE_VISIT, OrderWorkflow::STAGE_REQUOTE, OrderWorkflow::STAGE_ONSITE_SERVICE]);
            $this->expectFilled($data['change_reason'] ?? null, 'علت تغییر هزینه');
        } else {
            throw new OrderWorkflowException('نوع قیمت نامعتبر است.');
        }

        if (((int) ($data['service_fee'] ?? 0) + (int) ($data['parts_fee'] ?? 0) + (int) ($data['extra_fee'] ?? 0)) <= 0) {
            throw new OrderWorkflowException('مبلغ قیمت باید بیشتر از صفر باشد.');
        }

        $byTechnician = $actor['type'] === 'technician';
        // ۷-۲ — پیشنهاد تکنسین در محدوده‌ی اختیار تعریف‌شده، بدون بررسی پنل برای کاربر ارسال می‌شود
        $withinAuthority = $byTechnician && $kind === OrderQuote::KIND_REVISED && $this->withinTechnicianAuthority($order, $data);

        $quote = DB::transaction(function () use ($order, $kind, $data, $send, $actor, $byTechnician, $withinAuthority) {
            $order->quotes()
                ->whereIn('status', [OrderQuote::STATUS_PENDING_REVIEW, OrderQuote::STATUS_SENT])
                ->update(['status' => OrderQuote::STATUS_SUPERSEDED]);

            $version = (int) $order->quotes()->max('version') + 1;
            $isSent = $byTechnician ? $withinAuthority : $send;

            return $order->quotes()->create([
                'version' => $version,
                'kind' => $kind,
                'service_fee' => (int) ($data['service_fee'] ?? 0),
                'parts_fee' => (int) ($data['parts_fee'] ?? 0),
                'extra_fee' => (int) ($data['extra_fee'] ?? 0),
                'total' => (int) ($data['service_fee'] ?? 0) + (int) ($data['parts_fee'] ?? 0) + (int) ($data['extra_fee'] ?? 0),
                // ۴-۳ — قیمت اولیه تا بررسی فنی، برآورد است
                'is_estimate' => (bool) ($data['is_estimate'] ?? $kind === OrderQuote::KIND_INITIAL),
                'estimated_duration' => $data['estimated_duration'] ?? null,
                'visit_date' => $data['visit_date'] ?? null,
                'visit_time' => $data['visit_time'] ?? null,
                'parts_description' => $data['parts_description'] ?? null,
                'change_reason' => $data['change_reason'] ?? null,
                'description' => $data['description'] ?? null,
                // قیمت اصلاح‌شده همیشه تأیید کاربر می‌خواهد (۷-۴ و ۷-۵)
                'requires_user_approval' => $kind === OrderQuote::KIND_REVISED ? true : (bool) ($data['requires_user_approval'] ?? true),
                'status' => $isSent ? OrderQuote::STATUS_SENT : OrderQuote::STATUS_PENDING_REVIEW,
                'proposed_by_type' => $byTechnician ? 'technician' : 'admin',
                'proposed_by_id' => $actor['id'],
                'approved_by_admin_id' => $isSent && !$byTechnician ? $actor['id'] : null,
                'approved_at' => $isSent ? now() : null,
                'sent_at' => $isSent ? now() : null,
            ]);
        });

        // ۴-۴ — زمان مراجعه در سفارش ثبت می‌شود
        $orderAttributes = [];
        if ($kind === OrderQuote::KIND_INITIAL && $quote->visit_date) {
            $orderAttributes['date'] = $quote->visit_date->toDateString();
        }
        if ($kind === OrderQuote::KIND_INITIAL && $quote->visit_time) {
            $orderAttributes['time'] = $quote->visit_time;
        }

        $toStage = $kind === OrderQuote::KIND_REVISED ? OrderWorkflow::STAGE_REQUOTE : $this->stage($order);
        $this->moveTo($order, $toStage, $byTechnician ? 'requote_proposed' : 'quote_created',
            OrderQuote::KINDS[$kind] . ' نسخه ' . $quote->version . ' — ' . number_format($quote->total) . ' تومان'
                . ($quote->change_reason ? ' — علت: ' . $quote->change_reason : '')
                . ($withinAuthority ? ' — در محدوده‌ی اختیار قیمت‌گذاری تکنسین، مستقیم برای کاربر ارسال شد' : ''),
            ['quote_id' => $quote->id], $orderAttributes, $actor);

        if ($quote->status === OrderQuote::STATUS_SENT) {
            $this->afterQuoteSent($order, $quote);
        }

        return $quote;
    }

    /**
     * ۷-۲ — اختیار قیمت‌گذاری مشخص و محدود تکنسین: افزایش نسبت به آخرین قیمت تأییدشده‌ی کاربر
     * حداکثر به اندازه‌ی درصد تنظیمات مالی (۰ = بدون اختیار؛ بدون قیمت تأییدشده هم اختیاری نیست).
     */
    public function withinTechnicianAuthority(Order $order, array $data): bool
    {
        $limit = AppSetting::int(AppSetting::TECHNICIAN_REQUOTE_LIMIT_PERCENT);
        $accepted = $this->acceptedQuote($order);

        if ($limit <= 0 || !$accepted || $accepted->total <= 0) {
            return false;
        }

        $total = (int) ($data['service_fee'] ?? 0) + (int) ($data['parts_fee'] ?? 0) + (int) ($data['extra_fee'] ?? 0);

        return $total <= $accepted->total * (100 + $limit) / 100;
    }

    /** تأیید پیشنهاد (یا پیش‌نویس) و ارسال برای کاربر. */
    public function sendQuote(OrderQuote $quote): void
    {
        if ($quote->status !== OrderQuote::STATUS_PENDING_REVIEW) {
            throw new OrderWorkflowException('این نسخه‌ی قیمت در انتظار بررسی نیست.');
        }

        $actor = $this->actor();
        $quote->update([
            'status' => OrderQuote::STATUS_SENT,
            'approved_by_admin_id' => $actor['id'],
            'approved_at' => now(),
            'sent_at' => now(),
        ]);

        $order = $quote->order;
        $this->log($order, null, null, 'quote_sent', OrderQuote::KINDS[$quote->kind] . ' نسخه ' . $quote->version . ' برای کاربر ارسال شد', ['quote_id' => $quote->id]);
        $this->afterQuoteSent($order, $quote);
    }

    /** رد پیشنهاد قیمت تکنسین توسط پنل. */
    public function discardQuote(OrderQuote $quote, string $reason): void
    {
        if ($quote->status !== OrderQuote::STATUS_PENDING_REVIEW) {
            throw new OrderWorkflowException('این نسخه‌ی قیمت در انتظار بررسی نیست.');
        }
        $this->expectFilled($reason, 'علت رد');

        $quote->update(['status' => OrderQuote::STATUS_DISCARDED, 'decision_note' => $reason, 'decided_at' => now(), 'decided_by_type' => 'admin', 'decided_by_id' => auth('admin')->id()]);
        $this->log($quote->order, null, null, 'quote_discarded', $reason, ['quote_id' => $quote->id]);
    }

    /**
     * تصمیم کاربر درباره‌ی قیمت ارسال‌شده (از اپ، یا ثبت تلفنی توسط پنل از طرف کاربر).
     */
    public function decideQuote(OrderQuote $quote, bool $accepted, ?string $note = null, ?array $actor = null): void
    {
        $actor ??= $this->actor();

        if ($quote->status !== OrderQuote::STATUS_SENT) {
            throw new OrderWorkflowException('این نسخه‌ی قیمت در انتظار تصمیم کاربر نیست.', 'QUOTE_NOT_PENDING');
        }
        if (!$accepted) {
            $this->expectFilled($note, 'علت رد قیمت');
        }

        $order = $quote->order;
        $quote->update([
            'status' => $accepted ? OrderQuote::STATUS_ACCEPTED : OrderQuote::STATUS_REJECTED,
            'decided_at' => now(),
            'decided_by_type' => $actor['type'] === 'user' ? 'user' : 'admin',
            'decided_by_id' => $actor['id'],
            'decision_note' => $note,
        ]);

        $onBehalf = $actor['type'] === 'admin' ? ' (ثبت توسط پنل از طرف کاربر)' : '';
        $label = OrderQuote::KINDS[$quote->kind] . ' نسخه ' . $quote->version;

        if (!$accepted) {
            // ۴-۷ — سفارش در انتظار تعیین تکلیف می‌ماند؛ ۷-۶ — سفارش برای تعیین تکلیف به پنل برمی‌گردد
            $this->log($order, null, null, 'quote_rejected', $label . ' رد شد' . $onBehalf . ' — ' . $note, ['quote_id' => $quote->id], $actor);
            if ($quote->kind === OrderQuote::KIND_REVISED) {
                $this->notify($order->technician, 'رد قیمت جدید', 'کاربر قیمت جدید سفارش ' . $order->id . ' را تأیید نکرد. خدمات اضافه را انجام ندهید.', $order, 'order_requote_rejected');
            }

            return;
        }

        if ($quote->kind === OrderQuote::KIND_INITIAL) {
            $this->moveTo($order, OrderWorkflow::STAGE_ASSIGNMENT, 'quote_accepted', $label . ' تأیید شد' . $onBehalf . ($note ? ' — ' . $note : ''), ['quote_id' => $quote->id], [
                'user_initial_accept' => $order->user_initial_accept ?? now(),
            ], $actor);
        } else {
            // ۷-۵ — سفارش با قیمت و شرایط جدید ادامه می‌یابد
            $this->moveTo($order, OrderWorkflow::STAGE_ONSITE_SERVICE, 'quote_accepted', $label . ' تأیید شد' . $onBehalf . ($note ? ' — ' . $note : ''), ['quote_id' => $quote->id], [], $actor);
            $this->notify($order->technician, 'تأیید قیمت جدید', 'کاربر قیمت جدید سفارش ' . $order->id . ' را تأیید کرد.', $order, 'order_requote_accepted');
        }
    }

    /** ۷-۷ — پس از رد قیمت جدید، پنل مسیر ادامه را تعیین می‌کند (لغو از اقدام «لغو سفارش»). */
    public function resolveRejectedRequote(Order $order, string $path, ?string $note = null): void
    {
        $this->expectStage($order, [OrderWorkflow::STAGE_REQUOTE]);

        if ($this->openQuote($order)) {
            throw new OrderWorkflowException('یک نسخه‌ی قیمت هنوز باز است؛ ابتدا تکلیف آن را مشخص کنید.');
        }

        match ($path) {
            'continue' => $this->moveTo($order, OrderWorkflow::STAGE_ONSITE_SERVICE, 'requote_resolved', 'ادامه‌ی خدمت با شرایط تأییدشده‌ی قبلی' . ($note ? ' — ' . $note : '')),
            'transfer' => $this->transferFrom($order, 'requote_resolved', $note ?: 'انتقال دستگاه به شرکت برای بررسی بیشتر پس از رد قیمت جدید'),
            default => throw new OrderWorkflowException('مسیر نامعتبر است.'),
        };
    }

    // ───────────────────────────── ۵ — تخصیص تکنسین ─────────────────────────────

    public function assignTechnician(Order $order, int $technicianId, ?string $note = null, ?string $date = null, ?string $time = null): void
    {
        $this->expectStage($order, [OrderWorkflow::STAGE_ASSIGNMENT]);
        $workflow = $this->details($order);

        // ۳-۳ — تا تکمیل اطلاعات، تخصیص نهایی انجام نمی‌شود
        if ($workflow->review_status !== 'approved') {
            throw new OrderWorkflowException('اطلاعات سفارش هنوز تأیید نشده است.');
        }
        if ($workflow->assignment_status === 'accepted' && (int) $order->technician_id === $technicianId) {
            throw new OrderWorkflowException('این تکنسین قبلاً مأموریت را تأیید کرده است.');
        }

        $technician = Technician::findOrFail($technicianId);
        $previous = $order->technician_id;

        $workflow->update([
            'assignment_status' => 'pending',
            'assigned_at' => now(),
            'assignment_responded_at' => null,
            'assignment_note' => $note,
            'assignment_reject_reason' => null,
            'field_result' => null,
        ]);

        $this->moveTo($order, OrderWorkflow::STAGE_ASSIGNMENT, 'technician_assigned', 'تخصیص به ' . $technician->name . ($note ? ' — ' . $note : ''), [
            'technician_id' => $technician->id,
            'previous_technician_id' => $previous,
        ], [
            'technician_id' => $technician->id,
            'status' => (int) $order->status === 0 ? 1 : $order->status,
        ] + array_filter(['date' => $date, 'time' => $time]));

        // پیامک / نوتیف تخصیص اول را OrderObserver می‌فرستد؛ برای تخصیص مجدد این‌جا
        if ($previous !== null && (int) $previous !== $technician->id) {
            $this->notify($technician, 'سفارش جدید', 'سفارش ' . $order->id . ' به شما اختصاص داده شد.', $order, 'order_assigned');
        }
    }

    /** ۵-۴ / ۵-۵ — پاسخ تکنسین به مأموریت (از اپ تکنسین یا ثبت توسط پنل). */
    public function technicianResponse(Order $order, bool $accepted, ?string $reason = null, ?array $actor = null): void
    {
        $actor ??= $this->actor();
        $this->expectStage($order, [OrderWorkflow::STAGE_ASSIGNMENT]);
        $workflow = $this->details($order);

        if ($workflow->assignment_status !== 'pending' || !$order->technician_id) {
            throw new OrderWorkflowException('مأموریتی در انتظار پاسخ تکنسین نیست.', 'NO_PENDING_ASSIGNMENT');
        }

        $technicianName = $order->technician?->name ?? '-';

        if ($accepted) {
            $workflow->update(['assignment_status' => 'accepted', 'assignment_responded_at' => now()]);
            $this->moveTo($order, OrderWorkflow::STAGE_VISIT, 'technician_accepted', 'مأموریت توسط ' . $technicianName . ' تأیید شد' . ($reason ? ' — ' . $reason : ''), [], [], $actor);
            $this->notify($order->user, 'به‌روزرسانی سفارش', self::USER_MESSAGE, $order, 'order_technician_accepted');

            return;
        }

        $this->expectFilled($reason, 'علت عدم امکان انجام مأموریت');
        $workflow->update(['assignment_status' => 'rejected', 'assignment_responded_at' => now(), 'assignment_reject_reason' => $reason]);

        $this->moveTo($order, OrderWorkflow::STAGE_ASSIGNMENT, 'technician_rejected', $technicianName . ': ' . $reason, [
            'technician_id' => $order->technician_id,
        ], [
            'technician_id' => null,
            'status' => (int) $order->status === 1 && !$order->started_at ? 0 : $order->status,
        ], $actor);
    }

    /**
     * «لغو» از اپ تکنسین: سفارش لغو نمی‌شود و برای تخصیص مجدد به پنل برمی‌گردد (بند ۵-۵).
     * اگر تکنسین حرکت کرده بود، مأموریت ناموفق و نیازمند هماهنگی مجدد ثبت می‌شود (بند ۱۱-۱ هـ).
     */
    public function technicianWithdraw(Order $order, string $reason, ?array $actor = null): void
    {
        $actor ??= $this->actor();
        $this->expectFilled($reason, 'علت عدم امکان انجام مأموریت');

        if (in_array($this->stage($order), [OrderWorkflow::STAGE_CLOSED, OrderWorkflow::STAGE_CANCELLED, OrderWorkflow::STAGE_TRANSFER], true)) {
            throw new OrderWorkflowException('این سفارش در مرحله‌ی «' . OrderWorkflow::stageLabel($this->stage($order)) . '» است و قابل بازگرداندن نیست.', 'INVALID_STAGE');
        }
        if ($order->started_at) {
            throw new OrderWorkflowException('سفارش شروع شده است و امکان انصراف وجود ندارد. لطفاً سفارش را تکمیل کنید.', 'ORDER_ALREADY_STARTED');
        }

        $workflow = $this->details($order);
        $wasOnTheWay = $order->set_off_at !== null;
        $technicianName = $order->technician?->name ?? '-';

        $workflow->update([
            'review_status' => 'approved',
            'assignment_status' => 'rejected',
            'assignment_responded_at' => now(),
            'assignment_reject_reason' => $reason,
        ] + ($wasOnTheWay ? [
            'visit_outcome' => 'mission_failed',
            'visit_outcome_note' => $reason,
            'visit_outcome_at' => now(),
            'field_result' => 'failed_needs_recoordination',
        ] : []));

        $this->moveTo($order, OrderWorkflow::STAGE_ASSIGNMENT, $wasOnTheWay ? 'visit_outcome' : 'technician_rejected',
            $technicianName . ': ' . $reason . ' — بازگشت به پنل برای تخصیص مجدد', [
                'technician_id' => $order->technician_id,
                'set_off_at' => $order->set_off_at?->toIso8601String(),
                'arrived_at' => $order->arrived_at?->toIso8601String(),
            ], [
                'technician_id' => null,
                'status' => 0,
                'set_off_at' => null,
                'arrived_at' => null,
                'technician_cancel_reason' => $reason,
            ], $actor);

        // کاربری که منتظر تکنسین بود از هماهنگی مجدد باخبر می‌شود
        if ($wasOnTheWay) {
            $this->notify($order->user, 'به‌روزرسانی سفارش', self::USER_MESSAGE, $order, 'order_recoordination');
        }
    }

    // ───────────────────────────── ۶ — مراجعه ─────────────────────────────

    public function recordSetOff(Order $order): void
    {
        $this->expectStage($order, [OrderWorkflow::STAGE_VISIT]);
        if ($order->set_off_at) {
            throw new OrderWorkflowException('زمان حرکت قبلاً ثبت شده است.');
        }

        $this->moveTo($order, OrderWorkflow::STAGE_VISIT, 'set_off', 'حرکت تکنسین به سمت محل (ثبت توسط پنل)', [], ['set_off_at' => now(), 'status' => 1]);
    }

    public function recordArrival(Order $order): void
    {
        $this->expectStage($order, [OrderWorkflow::STAGE_VISIT]);
        if (!$order->set_off_at) {
            throw new OrderWorkflowException('ابتدا زمان حرکت تکنسین را ثبت کنید.');
        }
        if ($order->arrived_at) {
            throw new OrderWorkflowException('زمان رسیدن قبلاً ثبت شده است.');
        }

        $this->moveTo($order, OrderWorkflow::STAGE_VISIT, 'arrived', 'رسیدن تکنسین به محل (ثبت توسط پنل)', [], ['arrived_at' => now()]);
    }

    /** ۶-۴ / ۶-۵ — نتیجه‌ی بررسی اولیه‌ی تکنسین در محل. */
    public function recordVisitOutcome(Order $order, string $outcome, ?string $note = null, ?array $actor = null): void
    {
        $actor ??= $this->actor();
        $this->expectStage($order, [OrderWorkflow::STAGE_VISIT]);
        $this->expectOption($outcome, OrderWorkflow::VISIT_OUTCOMES, 'نتیجه‌ی بررسی', required: true);
        if ($outcome !== 'serviceable_onsite') {
            $this->expectFilled($note, 'شرح نتیجه‌ی بررسی');
        }

        $workflow = $this->details($order);
        $workflow->update(['visit_outcome' => $outcome, 'visit_outcome_note' => $note, 'visit_outcome_at' => now()]);
        $text = OrderWorkflow::VISIT_OUTCOMES[$outcome] . ($note ? ' — ' . $note : '');

        switch ($outcome) {
            case 'serviceable_onsite':
                $this->moveTo($order, OrderWorkflow::STAGE_ONSITE_SERVICE, 'visit_outcome', $text, ['outcome' => $outcome], [], $actor);
                break;
            case 'needs_requote':
                $this->moveTo($order, OrderWorkflow::STAGE_REQUOTE, 'visit_outcome', $text, ['outcome' => $outcome], [], $actor);
                break;
            case 'transfer_to_loop':
                $this->transferFrom($order, 'visit_outcome', $note, $actor);
                break;
            case 'user_declined':
                // پنل تکلیف را مشخص می‌کند (لغو سفارش)
                $this->log($order, null, null, 'visit_outcome', $text, ['outcome' => $outcome], $actor);
                break;
            case 'mission_failed':
                // ۱۱-۱ (هـ) — نیازمند هماهنگی مجدد: سفارش برای زمان‌بندی و تخصیص دوباره به مرحله‌ی تخصیص برمی‌گردد
                $workflow->update(['field_result' => 'failed_needs_recoordination', 'assignment_status' => null]);
                $this->moveTo($order, OrderWorkflow::STAGE_ASSIGNMENT, 'visit_outcome', $text, [
                    'outcome' => $outcome,
                    'set_off_at' => $order->set_off_at?->toIso8601String(),
                    'arrived_at' => $order->arrived_at?->toIso8601String(),
                ], ['set_off_at' => null, 'arrived_at' => null], $actor);
                break;
        }
    }

    // ───────────────────────────── ۸ — انجام خدمت در محل ─────────────────────────────

    public function requestRequote(Order $order, string $note): void
    {
        $this->expectStage($order, [OrderWorkflow::STAGE_VISIT, OrderWorkflow::STAGE_ONSITE_SERVICE]);
        $this->expectFilled($note, 'شرح خدمات / قطعات اضافی');

        $this->moveTo($order, OrderWorkflow::STAGE_REQUOTE, 'requote_requested', $note);
    }

    /** ۸-۱ — شروع خدمت فقط با قیمت و شرایط تأییدشده‌ی کاربر. */
    public function startService(Order $order): void
    {
        $this->expectStage($order, [OrderWorkflow::STAGE_ONSITE_SERVICE]);
        if ($order->started_at) {
            throw new OrderWorkflowException('شروع خدمت قبلاً ثبت شده است.');
        }
        $this->ensureCustomerApprovedPrice($order);

        $this->moveTo($order, OrderWorkflow::STAGE_ONSITE_SERVICE, 'service_started', 'شروع انجام خدمت در محل', [], ['started_at' => now(), 'status' => 1]);
    }

    /** ۸-۲ / ۸-۴ / ۸-۵ — ثبت اقدامات، قطعات مصرفی و نتیجه؛ سفارش برای بررسی نتیجه به مرحله‌ی پایان می‌رود. */
    public function completeOnsiteService(Order $order, array $data, ?array $actor = null): void
    {
        $actor ??= $this->actor();
        $this->expectStage($order, [OrderWorkflow::STAGE_ONSITE_SERVICE]);
        $this->expectFilled($data['service_actions'] ?? null, 'اقدامات انجام‌شده');
        $this->expectFilled($data['service_result'] ?? null, 'نتیجه‌ی کار');
        $this->ensureCustomerApprovedPrice($order);

        $this->details($order)->update([
            'service_actions' => $data['service_actions'],
            'service_parts_used' => $data['service_parts_used'] ?? null,
            'service_result' => $data['service_result'],
            'field_result' => 'done_onsite',
            'field_closed_at' => now(),
        ]);

        $this->moveTo($order, OrderWorkflow::STAGE_CLOSED, 'service_completed', $data['service_result'], [], [
            'started_at' => $order->started_at ?? now(),
            'finished_at' => $order->finished_at ?? now(),
            'done_in_place' => $order->done_in_place ?? now(),
        ], $actor);

        $this->notify($order->user, 'انجام خدمت', self::USER_MESSAGE, $order, 'order_service_done');
    }

    // ───────────────────────────── ۹ — ارجاع به شرکت ─────────────────────────────

    /** ۹-۱ — انجام در محل ممکن نیست (تجهیزات، قطعه یا بررسی تکمیلی): ارجاع با ثبت دلیل. */
    public function sendToTransfer(Order $order, string $reason): void
    {
        $this->expectStage($order, [OrderWorkflow::STAGE_ONSITE_SERVICE, OrderWorkflow::STAGE_REQUOTE]);
        $this->expectFilled($reason, 'دلیل ارجاع');

        $this->transferFrom($order, 'transfer_requested', $reason);
    }

    /** ۹-۱ تا ۹-۳ / ۹-۶ — ثبت روش انتقال، مشخصات و وضعیت ظاهری دستگاه، لوازم همراه و علت ارجاع. */
    public function startTransfer(Order $order, array $data): void
    {
        $this->expectStage($order, [OrderWorkflow::STAGE_TRANSFER]);
        $workflow = $this->details($order);

        if ($workflow->transfer_started_at) {
            throw new OrderWorkflowException('انتقال دستگاه قبلاً ثبت شده است.');
        }
        $this->expectOption($data['transfer_method'] ?? null, OrderWorkflow::TRANSFER_METHODS, 'روش انتقال', required: true);
        $this->expectFilled($data['transfer_reason'] ?? null, 'علت ارجاع');
        $this->expectFilled($data['transfer_device_condition'] ?? null, 'مشخصات و وضعیت ظاهری دستگاه');
        if ($data['transfer_method'] === 'courier') {
            $this->expectFilled($data['transfer_courier_info'] ?? null, 'مشخصات مأموریت پیک و اطلاعات بسته');
        }

        $workflow->update(array_intersect_key($data, array_flip([
            'transfer_method', 'transfer_reason', 'transfer_device_condition', 'transfer_accessories', 'transfer_courier_info', 'transfer_custody_note',
        ])) + ['transfer_started_at' => now()]);

        $this->moveTo($order, OrderWorkflow::STAGE_TRANSFER, 'transfer_started', OrderWorkflow::TRANSFER_METHODS[$data['transfer_method']] . ' — ' . $data['transfer_reason'], [], [
            // زمان اعزام محصول به لوپ؛ فرآیند تعمیرات داخل شرکت (برآورد، پیش‌پرداخت) از همین فیلد شروع می‌شود
            'send_to_loop' => $order->send_to_loop ?? now(),
        ]);

        $this->notify($order->user, 'ارجاع دستگاه به لوپ', self::USER_MESSAGE, $order, 'order_transfer_started');
    }

    /** ۹-۴ / ۹-۵ / ۱۱-۳ — تحویل فیزیکی در شرکت و صدور رسید؛ سفارش میدانی خاتمه می‌یابد. */
    public function confirmTransferReceipt(Order $order, array $data): string
    {
        $this->expectStage($order, [OrderWorkflow::STAGE_TRANSFER]);
        $workflow = $this->details($order);

        if (!$workflow->transfer_started_at) {
            throw new OrderWorkflowException('ابتدا انتقال دستگاه را ثبت کنید.');
        }
        $this->expectFilled($data['transfer_received_by'] ?? null, 'مسئول دریافت');

        $receipt = filled($data['transfer_receipt_number'] ?? null)
            ? $data['transfer_receipt_number']
            : 'LP-' . $order->id . '-' . now()->format('ymdHi');

        $workflow->update([
            'transfer_received_by' => $data['transfer_received_by'],
            'transfer_received_at' => $data['transfer_received_at'] ?? now(),
            'transfer_receipt_number' => $receipt,
            'transfer_custody_note' => $data['transfer_custody_note'] ?? $workflow->transfer_custody_note,
            'field_result' => 'transferred_to_loop',
            'field_closed_at' => now(),
        ]);

        $this->moveTo($order, OrderWorkflow::STAGE_CLOSED, 'transfer_received', 'دریافت در شرکت توسط ' . $data['transfer_received_by'] . ' — رسید ' . $receipt, ['receipt' => $receipt]);
        $this->notify($order->user, 'دریافت دستگاه در لوپ', self::USER_MESSAGE, $order, 'order_transfer_received');

        return $receipt;
    }

    // ───────────────────────────── ۱۱ — پایان ─────────────────────────────

    /** ۱۲-۲ — قیمت نهایی جدا از قیمت اولیه و اصلاح‌شده ثبت می‌شود (مبلغی که پرداخت از روی آن محاسبه می‌شود). */
    public function recordFinalPrice(Order $order, int $price, ?string $note = null): void
    {
        $this->expectStage($order, [OrderWorkflow::STAGE_CLOSED]);
        if ((int) $order->status === 2) {
            throw new OrderWorkflowException('پرونده بسته شده است.');
        }
        if ($order->send_to_loop === null && $order->done_in_place === null) {
            throw new OrderWorkflowException('قیمت نهایی فقط پس از انجام در محل یا اعزام به لوپ ثبت می‌شود.');
        }
        if ($order->send_to_loop !== null && (float) $order->prepayment > 0 && (int) $order->prepayment_payment_status !== 1) {
            throw new OrderWorkflowException('تا پرداخت پیش‌پرداخت توسط کاربر، تعمیر شروع نمی‌شود و مبلغ نهایی قابل ثبت نیست.');
        }
        if ($price <= 0) {
            throw new OrderWorkflowException('مبلغ نهایی باید بیشتر از صفر باشد.');
        }

        $actor = $this->actor();
        $quote = $order->quotes()->create([
            'version' => (int) $order->quotes()->max('version') + 1,
            'kind' => OrderQuote::KIND_FINAL,
            'service_fee' => $price,
            'parts_fee' => (int) $order->extra_price,
            'total' => $price + (int) $order->extra_price,
            'is_estimate' => false,
            'description' => $note,
            'requires_user_approval' => false,
            'status' => OrderQuote::STATUS_RECORDED,
            'proposed_by_type' => 'admin',
            'proposed_by_id' => $actor['id'],
            'approved_by_admin_id' => $actor['id'],
            'approved_at' => now(),
        ]);

        $this->moveTo($order, OrderWorkflow::STAGE_CLOSED, 'final_price', 'قیمت نهایی ' . number_format($quote->total) . ' تومان' . ($note ? ' — ' . $note : ''), ['quote_id' => $quote->id], [
            'technician_price' => $price,
        ]);
    }

    /**
     * ۸-۶ / ۱۱-۳ / ۱۱-۵ — پرونده تنها پس از ثبت نتیجه، تسویه و (برای ارجاع) تحویل نهایی مختومه می‌شود.
     *
     * @return array نتیجه‌ی واریز سهم تکنسین
     */
    public function closeFile(Order $order, ?string $note = null): array
    {
        $this->expectStage($order, [OrderWorkflow::STAGE_CLOSED]);
        if ((int) $order->status === 2) {
            throw new OrderWorkflowException('پرونده قبلاً مختومه شده است.');
        }

        $blockers = $this->closeBlockers($order);
        if ($blockers) {
            throw new OrderWorkflowException('امکان مختومه کردن پرونده نیست: ' . implode('، ', $blockers));
        }

        $this->moveTo($order, OrderWorkflow::STAGE_CLOSED, 'file_closed', $note ?: 'پرونده مختومه شد', [], [
            'status' => 2,
            'finished_at' => $order->finished_at ?? now(),
        ]);

        return app(OrderService::class)->processTechnicianPayment($order->fresh());
    }

    /** شرط‌های مختومه شدن پرونده (برای نمایش چک‌لیست در پنل). */
    public function closeBlockers(Order $order): array
    {
        $workflow = $this->details($order);
        $blockers = [];

        if (!$workflow->field_result) {
            $blockers[] = 'نتیجه‌ی سفارش ثبت نشده';
        }
        if (!$order->technician_price) {
            $blockers[] = 'قیمت نهایی ثبت نشده';
        }
        if ((int) $order->payment_status !== 1) {
            $blockers[] = 'سفارش تسویه نشده';
        }
        if ($workflow->field_result === 'transferred_to_loop' && $order->delivery_status !== 'delivered') {
            $blockers[] = 'دستگاه هنوز به کاربر تحویل نهایی نشده';
        }

        return $blockers;
    }

    // ───────────────────────────── ۱۰ — لغو ─────────────────────────────

    /** ۱۰-۲ تا ۱۰-۶ — لغو با علت، تعیین تکلیف مالی و (در صورت در اختیار بودن دستگاه) عودت. */
    public function cancel(Order $order, array $data): void
    {
        if ($this->stage($order) === OrderWorkflow::STAGE_CANCELLED || in_array((int) $order->status, [3, 4, 5, 6], true)) {
            throw new OrderWorkflowException('سفارش قبلاً لغو شده است.');
        }
        if ((int) $order->status === 2) {
            throw new OrderWorkflowException('پرونده‌ی مختومه قابل لغو نیست.');
        }
        $this->expectOption($data['cancel_reason_type'] ?? null, OrderWorkflow::CANCEL_REASONS, 'علت لغو', required: true);
        $this->expectFilled($data['cancel_reason'] ?? null, 'شرح علت لغو');

        $workflow = $this->details($order);
        $deviceInCustody = $workflow->transfer_started_at !== null || $order->send_to_loop !== null;

        $workflow->update([
            'cancel_reason_type' => $data['cancel_reason_type'],
            'cancel_reason' => $data['cancel_reason'],
            'cancel_financial_note' => $data['cancel_financial_note'] ?? null,
            'device_return_required' => $deviceInCustody || (bool) ($data['device_return_required'] ?? false),
            'field_result' => in_array($data['cancel_reason_type'], ['user_request', 'price_rejected'], true) ? 'cancelled_by_user' : 'cancelled_not_feasible',
            'field_closed_at' => now(),
        ]);

        $order->quotes()
            ->whereIn('status', [OrderQuote::STATUS_PENDING_REVIEW, OrderQuote::STATUS_SENT])
            ->update(['status' => OrderQuote::STATUS_SUPERSEDED]);

        // لغو از پنل همیشه «لغو از سوی پنل» (۵)؛ پیامک و نوتیف کاربر / تکنسین را OrderObserver می‌فرستد
        $this->moveTo($order, OrderWorkflow::STAGE_CANCELLED, 'cancelled',
            OrderWorkflow::CANCEL_REASONS[$data['cancel_reason_type']] . ' — ' . $data['cancel_reason'],
            ['reason_type' => $data['cancel_reason_type']], ['status' => 5]);
    }

    /** ۱۰-۵ — عودت دستگاهی که در اختیار تکنسین / شرکت بوده. */
    public function confirmDeviceReturned(Order $order, ?string $note = null): void
    {
        $this->expectStage($order, [OrderWorkflow::STAGE_CANCELLED]);
        if (!$this->details($order)->device_return_required || $order->returned_at) {
            throw new OrderWorkflowException('عودت دستگاهی برای این سفارش در انتظار نیست.');
        }

        $this->moveTo($order, OrderWorkflow::STAGE_CANCELLED, 'device_returned', $note ?: 'دستگاه به کاربر عودت داده شد', [], ['returned_at' => now()]);
    }

    // ───────────────────────────── همگام‌سازی با اپ‌ها و فرم ویرایش ─────────────────────────────

    /**
     * تغییرات مستقیم وضعیت / تکنسین (فرم ویرایش پنل، اپ کاربر و تکنسین) را با مرحله همگام و ثبت می‌کند.
     * از OrderObserver::updating صدا زده می‌شود.
     */
    public function syncLegacyChanges(Order $order): void
    {
        if (self::isManaging($order) || $order->isDirty('workflow_stage')) {
            return;
        }

        $from = $order->workflow_stage;

        if ($order->isDirty('status')) {
            $status = (int) $order->status;
            $original = (int) $order->getOriginal('status');

            if (in_array($status, [3, 4, 5, 6], true)) {
                $order->workflow_stage = OrderWorkflow::STAGE_CANCELLED;
                $this->applyLegacyCancellation($order, $status);
            } elseif ($status === 2) {
                $order->workflow_stage = OrderWorkflow::STAGE_CLOSED;
                $workflow = $this->details($order);
                $workflow->update([
                    'field_result' => $workflow->field_result ?? ($order->send_to_loop ? 'transferred_to_loop' : 'done_onsite'),
                    'field_closed_at' => $workflow->field_closed_at ?? now(),
                ]);
            } elseif ($from === OrderWorkflow::STAGE_CANCELLED || $from === OrderWorkflow::STAGE_CLOSED) {
                // بازگشایی سفارش از فرم ویرایش
                $order->workflow_stage = OrderWorkflow::STAGE_REVIEW;
            }

            $this->log($order, $from, $order->workflow_stage, 'status_changed',
                'تغییر وضعیت از «' . (\App\Filament\Resources\OrderResource::STATUS_OPTIONS[$original] ?? $original) . '» به «'
                    . (\App\Filament\Resources\OrderResource::STATUS_OPTIONS[$status] ?? $status) . '»'
                    . ($status === 4 && $order->technician_cancel_reason ? ' — ' . $order->technician_cancel_reason : '')
                    . ($status === 3 && $order->user_cancellation_reason ? ' — ' . $order->user_cancellation_reason : ''),
                ['status_from' => $original, 'status_to' => $status]);
        }

        if ($order->isDirty('technician_id') && !in_array($order->workflow_stage, [OrderWorkflow::STAGE_CANCELLED, OrderWorkflow::STAGE_CLOSED], true)) {
            $workflow = $this->details($order);

            if ($order->technician_id) {
                $workflow->update(['assignment_status' => 'pending', 'assigned_at' => now(), 'assignment_responded_at' => null, 'assignment_reject_reason' => null]);
                if (in_array($order->workflow_stage, [null, OrderWorkflow::STAGE_REVIEW, OrderWorkflow::STAGE_QUOTING], true)) {
                    $workflow->update(['review_status' => 'approved', 'reviewed_at' => $workflow->reviewed_at ?? now()]);
                }
                $order->workflow_stage = OrderWorkflow::STAGE_ASSIGNMENT;
                $note = 'تخصیص به ' . (Technician::find($order->technician_id)?->name ?? '-');
            } else {
                $workflow->update(['assignment_status' => null]);
                $note = 'حذف تکنسین از سفارش';
            }

            $this->log($order, $from, $order->workflow_stage, 'technician_assigned', $note, ['technician_id' => $order->technician_id]);
        }
    }

    public function onTechnicianSetOff(Order $order): void
    {
        $order->refresh();
        $workflow = $this->details($order);

        // حرکت به سمت محل یعنی تکنسین مأموریت را پذیرفته است
        if ($this->stage($order) === OrderWorkflow::STAGE_ASSIGNMENT && $workflow->assignment_status === 'pending') {
            $workflow->update(['assignment_status' => 'accepted', 'assignment_responded_at' => now()]);
            $this->moveTo($order, OrderWorkflow::STAGE_VISIT, 'technician_accepted', 'تأیید ضمنی با ثبت حرکت از اپ تکنسین');
        }

        $this->log($order, null, null, 'set_off', 'حرکت تکنسین به سمت محل (اپ تکنسین)');
    }

    public function onTechnicianArrived(Order $order): void
    {
        $this->log($order, null, null, 'arrived', 'رسیدن تکنسین به محل (اپ تکنسین)');
    }

    public function onTechnicianSentToLoop(Order $order): void
    {
        $order->refresh();
        if (in_array($this->stage($order), [OrderWorkflow::STAGE_CLOSED, OrderWorkflow::STAGE_CANCELLED], true)) {
            return;
        }

        $workflow = $this->details($order);
        $report = $order->technician_order_report;
        $workflow->update([
            'transfer_method' => $workflow->transfer_method ?? 'technician',
            'transfer_reason' => $workflow->transfer_reason ?? 'اعزام به لوپ از اپ تکنسین',
            'transfer_device_condition' => $workflow->transfer_device_condition ?? ($report ? trim(implode(' / ', array_filter([
                $report->product_name, $report->product_brand, $report->product_model, $report->product_color, $report->product_serial_number,
            ]))) : null),
            'transfer_accessories' => $workflow->transfer_accessories ?? $report?->accessories,
            'transfer_started_at' => $workflow->transfer_started_at ?? now(),
        ]);

        $this->moveTo($order, OrderWorkflow::STAGE_TRANSFER, 'transfer_started', 'دریافت دستگاه توسط تکنسین و اعزام به لوپ (اپ تکنسین)');
    }

    public function onTechnicianDoneInPlace(Order $order): void
    {
        $order->refresh();
        if (in_array($this->stage($order), [OrderWorkflow::STAGE_CLOSED, OrderWorkflow::STAGE_CANCELLED, OrderWorkflow::STAGE_TRANSFER], true)) {
            return;
        }

        $workflow = $this->details($order);
        $workflow->update([
            'service_result' => $workflow->service_result ?? $order->technician_in_place_description,
            'field_result' => 'done_onsite',
            'field_closed_at' => now(),
        ]);

        $this->moveTo($order, OrderWorkflow::STAGE_CLOSED, 'service_completed', 'انجام در محل (اپ تکنسین)' . ($order->technician_in_place_description ? ' — ' . $order->technician_in_place_description : ''));
    }

    /** پذیرش اولیه از اپ کاربر = تأیید قیمت اولیه‌ی ارسال‌شده. */
    public function onUserInitialAccept(Order $order): void
    {
        $quote = $this->openQuote($order);
        if ($quote && $quote->kind === OrderQuote::KIND_INITIAL && $quote->status === OrderQuote::STATUS_SENT) {
            $this->decideQuote($quote, true, 'پذیرش اولیه از اپ کاربر');
        }
    }

    /** رد برآورد لوپ از اپ کاربر (آپدیت مستقیم، بدون Observer). */
    public function onUserRejectedLoopEstimate(Order $order): void
    {
        $order->refresh();
        if ($this->stage($order) === OrderWorkflow::STAGE_CANCELLED) {
            return;
        }

        $this->applyLegacyCancellation($order, 3, 'price_rejected');
        $this->moveTo($order, OrderWorkflow::STAGE_CANCELLED, 'cancelled', 'رد برآورد هزینه‌ی لوپ توسط کاربر' . ($order->user_cancellation_reason ? ' — ' . $order->user_cancellation_reason : ''));
    }

    // ───────────────────────────── خروجی API ─────────────────────────────

    public function customerPayload(Order $order): array
    {
        $workflow = $this->details($order);
        $quotes = $order->quotes()
            ->whereIn('status', [OrderQuote::STATUS_SENT, OrderQuote::STATUS_ACCEPTED, OrderQuote::STATUS_REJECTED, OrderQuote::STATUS_RECORDED])
            ->get();
        $current = $this->customerQuote($order);

        return [
            'stage' => $this->stage($order),
            'stage_label' => OrderWorkflow::stageLabel($this->stage($order)),
            'service_type' => $workflow->service_type,
            'service_type_label' => OrderWorkflow::SERVICE_TYPES[$workflow->service_type] ?? null,
            'review_status' => $workflow->review_status,
            'review_note' => $workflow->review_status === 'incomplete' ? $workflow->review_note : null,
            'current_quote' => $current?->toApiArray(),
            'awaiting_quote_decision' => $current?->status === OrderQuote::STATUS_SENT,
            'quotes' => $quotes->map->toApiArray()->values()->all(),
            'transfer' => $workflow->transfer_started_at ? [
                'method' => $workflow->transfer_method,
                'method_label' => OrderWorkflow::TRANSFER_METHODS[$workflow->transfer_method] ?? null,
                'started_at' => $workflow->transfer_started_at?->toIso8601String(),
                'received_at' => $workflow->transfer_received_at?->toIso8601String(),
                'receipt_number' => $workflow->transfer_receipt_number,
            ] : null,
            'field_result' => $workflow->field_result,
            'field_result_label' => OrderWorkflow::FIELD_RESULTS[$workflow->field_result] ?? null,
            'cancel_reason' => $this->stage($order) === OrderWorkflow::STAGE_CANCELLED ? $workflow->cancel_reason : null,
        ];
    }

    /** ۵-۳ — اطلاعات مأموریت برای تکنسین. */
    public function technicianPayload(Order $order): array
    {
        $workflow = $this->details($order);
        $quote = $this->acceptedQuote($order) ?? $this->customerQuote($order);

        return [
            'stage' => $this->stage($order),
            'stage_label' => OrderWorkflow::stageLabel($this->stage($order)),
            'service_type' => $workflow->service_type,
            'service_type_label' => OrderWorkflow::SERVICE_TYPES[$workflow->service_type] ?? null,
            'assignment_status' => $workflow->assignment_status,
            'assignment_note' => $workflow->assignment_note,
            'visit_outcome' => $workflow->visit_outcome,
            'quote' => $quote?->toApiArray(),
            'can_perform_service' => $this->stage($order) === OrderWorkflow::STAGE_ONSITE_SERVICE && $this->hasCustomerApprovedPrice($order),
            // ۷-۲ — سقف قیمتی که پیشنهاد تکنسین تا آن بدون بررسی پنل برای کاربر ارسال می‌شود (null = بدون اختیار)
            'requote_auto_send_max_total' => ($limit = AppSetting::int(AppSetting::TECHNICIAN_REQUOTE_LIMIT_PERCENT)) > 0 && ($accepted = $this->acceptedQuote($order))
                ? (int) floor($accepted->total * (100 + $limit) / 100)
                : null,
        ];
    }

    // ───────────────────────────── داخلی ─────────────────────────────

    private function transferFrom(Order $order, string $action, ?string $reason, ?array $actor = null): void
    {
        $this->details($order)->update(['transfer_reason' => $reason]);
        $this->moveTo($order, OrderWorkflow::STAGE_TRANSFER, $action, 'انتقال دستگاه به شرکت' . ($reason ? ' — ' . $reason : ''), [], [], $actor);
    }

    private function hasCustomerApprovedPrice(Order $order): bool
    {
        $latest = $this->customerQuote($order);

        // سفارش‌های قبل از فرآیند قیمت ندارند
        return $latest === null || $latest->status === OrderQuote::STATUS_ACCEPTED
            || ($latest->status === OrderQuote::STATUS_REJECTED && $latest->kind === OrderQuote::KIND_REVISED && $this->acceptedQuote($order) !== null);
    }

    private function ensureCustomerApprovedPrice(Order $order): void
    {
        if ($this->openQuote($order)) {
            throw new OrderWorkflowException('یک نسخه‌ی قیمت هنوز در انتظار تأیید است؛ تا تأیید کاربر، خدمات هزینه‌زا شروع نمی‌شود (بند ۸-۳ و ۱۲-۳).');
        }
        if (!$this->hasCustomerApprovedPrice($order)) {
            throw new OrderWorkflowException('قیمت و شرایط نهایی هنوز توسط کاربر تأیید نشده است (بند ۸-۱).');
        }
    }

    private function applyLegacyCancellation(Order $order, int $status, ?string $reasonType = null): void
    {
        $workflow = $this->details($order);
        if ($workflow->cancel_reason_type) {
            return;
        }

        $reasonType ??= match ($status) {
            3 => 'user_request',
            4 => 'not_feasible',
            default => 'other',
        };

        $workflow->update([
            'cancel_reason_type' => $reasonType,
            'cancel_reason' => match ($status) {
                3 => $order->user_cancellation_reason ?: 'لغو از اپ کاربر',
                4 => $order->technician_cancel_reason ?: 'لغو از اپ تکنسین',
                6 => 'پایان مهلت سفارش',
                default => 'لغو از فرم ویرایش پنل',
            },
            'device_return_required' => $order->send_to_loop !== null,
            'field_result' => in_array($reasonType, ['user_request', 'price_rejected'], true) ? 'cancelled_by_user' : 'cancelled_not_feasible',
            'field_closed_at' => now(),
        ]);
    }

    private function moveTo(Order $order, string $to, string $action, ?string $note = null, array $meta = [], array $orderAttributes = [], ?array $actor = null): void
    {
        $from = $order->workflow_stage;
        self::$managing[$order->getKey()] = true;

        try {
            $order->forceFill($orderAttributes + ['workflow_stage' => $to])->save();
        } finally {
            unset(self::$managing[$order->getKey()]);
        }

        $this->log($order, $from === $to ? null : $from, $from === $to ? null : $to, $action, $note, $meta, $actor);
    }

    public function log(Order $order, ?string $from, ?string $to, string $action, ?string $note = null, array $meta = [], ?array $actor = null): OrderStageLog
    {
        $actor ??= $this->actor();

        return OrderStageLog::create([
            'order_id' => $order->id,
            'from_stage' => $from,
            'to_stage' => $to,
            'action' => $action,
            'actor_type' => $actor['type'],
            'actor_id' => $actor['id'],
            'actor_name' => $actor['name'],
            'note' => $note,
            'meta' => $meta ?: null,
        ]);
    }

    /** @return array{type:string, id:?int, name:?string} */
    public function actor(): array
    {
        try {
            if ($admin = auth('admin')->user()) {
                return ['type' => 'admin', 'id' => $admin->id, 'name' => $admin->name];
            }
        } catch (\Throwable) {
            // درخواست API بدون نشست
        }

        $user = request()?->user();
        if ($user instanceof Technician) {
            return ['type' => 'technician', 'id' => $user->id, 'name' => $user->name];
        }
        if ($user instanceof User) {
            return ['type' => 'user', 'id' => $user->id, 'name' => trim($user->name . ' ' . $user->last_name)];
        }
        if ($user instanceof Admin) {
            return ['type' => 'admin', 'id' => $user->id, 'name' => $user->name];
        }

        return ['type' => 'system', 'id' => null, 'name' => null];
    }

    private function afterQuoteSent(Order $order, OrderQuote $quote): void
    {
        // ۴-۶ — اگر تأیید کاربر لازم نیست، سفارش مستقیم ادامه می‌یابد
        if (!$quote->requires_user_approval) {
            $quote->refresh();
            $this->decideQuote($quote, true, 'بدون نیاز به تأیید کاربر');

            return;
        }

        $this->notify($order->user, 'اعلام قیمت سفارش', self::USER_MESSAGE, $order, 'order_quote_sent');
    }

    private function notify(?object $notifiable, string $title, string $body, Order $order, string $type): void
    {
        if (!$notifiable || !config('firebase.enabled')) {
            return;
        }

        try {
            SendFirebaseNotificationJob::dispatch($notifiable, $title, $body, ['type' => $type, 'order_id' => $order->id, 'screen' => 'order-detail']);
        } catch (\Throwable $exception) {
            Log::warning('Order workflow push notification could not be sent.', ['order_id' => $order->id, 'error' => $exception->getMessage()]);
        }
    }

    private function expectStage(Order $order, array $stages): void
    {
        if (!in_array($this->stage($order), $stages, true)) {
            throw new OrderWorkflowException(
                'این اقدام در مرحله‌ی «' . OrderWorkflow::stageLabel($this->stage($order)) . '» مجاز نیست.',
                'INVALID_STAGE'
            );
        }
    }

    private function expectFilled(mixed $value, string $label): void
    {
        if (blank($value)) {
            throw new OrderWorkflowException("«{$label}» الزامی است.", 'VALIDATION');
        }
    }

    private function expectOption(?string $value, array $options, string $label, bool $required = false): void
    {
        if ($value === null || $value === '') {
            if ($required) {
                throw new OrderWorkflowException("«{$label}» الزامی است.", 'VALIDATION');
            }

            return;
        }
        if (!array_key_exists($value, $options)) {
            throw new OrderWorkflowException("«{$label}» نامعتبر است.", 'VALIDATION');
        }
    }
}
