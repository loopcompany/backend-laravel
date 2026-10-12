<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\AppSetting;
use App\Models\Order;
use App\Models\OrderProcurement;
use App\Models\User;
use App\Models\UserTransaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment;

/**
 * روند «تامین تجهیزات / کالا» سفارش سازمانی (سند اپ کاربر، بخش ۹):
 * پیش‌رسید → اعلام قیمت پنل (معتبر تا پایان ساعات کاری) → تأیید کاربر → پیش‌پرداخت →
 * آماده‌سازی → پرداخت باقی‌مانده → ارسال → تحویل.
 *
 * خطاهای کسب‌وکاری با BusinessRuleException (success:false + message فارسی + error_code) برمی‌گردند.
 */
class ProcurementService
{
    /** نوع تراکنش پرداخت تامین کالا از درگاه؛ callback آن جدا از پرداخت عادی سفارش است. */
    public const TRANSACTION_TYPE_GATEWAY = 6;

    /**
     * اقلام تامین کالای ارسالی اپ را ذخیره می‌کند و برای حساب سازمانی/شرکتی استعلام می‌سازد.
     *
     * @param  array<int, array{item_id?: string, title?: string, new_count?: int, used_count?: int, description?: ?string}>  $items
     */
    public function createForOrder(Order $order, array $items, User $user): ?OrderProcurement
    {
        if ($items === [] || !$user->isOrganization()) {
            return null;
        }

        $procurement = $order->procurement()->create([
            'status' => OrderProcurement::STATUS_AWAITING_QUOTE,
            'prepayment_percent' => AppSetting::int(AppSetting::PROCUREMENT_PREPAYMENT_PERCENT),
        ]);

        foreach ($items as $item) {
            foreach (['new' => 'new_count', 'used' => 'used_count'] as $condition => $countKey) {
                $quantity = (int) ($item[$countKey] ?? 0);
                if ($quantity < 1) {
                    continue;
                }

                $procurement->items()->create([
                    'item_id' => $item['item_id'] ?? null,
                    'title' => $item['title'] ?? ($item['item_id'] ?? 'کالا'),
                    'condition' => $condition,
                    'quantity' => $quantity,
                    'description' => $item['description'] ?? null,
                ]);
            }
        }

        return $procurement;
    }

    // ----------------------------------------------------------------- پنل

    /** اعلام قیمت: همه‌ی اقلام باید قیمت واحد داشته باشند. */
    public function quote(OrderProcurement $procurement, ?string $note = null): void
    {
        $procurement->load('items');

        if (!in_array($procurement->effectiveStatus(), [OrderProcurement::STATUS_AWAITING_QUOTE, OrderProcurement::STATUS_QUOTED, OrderProcurement::STATUS_EXPIRED], true)) {
            throw new BusinessRuleException('اعلام قیمت فقط پیش از تأیید کاربر ممکن است.', 'INVALID_STATUS');
        }

        if ($procurement->items->isEmpty() || $procurement->items->contains(fn ($item) => $item->unit_price === null)) {
            throw new BusinessRuleException('برای همه‌ی اقلام قیمت واحد وارد کنید.', 'PRICE_REQUIRED');
        }

        $procurement->recalculate();
        $procurement->forceFill([
            'status' => OrderProcurement::STATUS_QUOTED,
            'quoted_at' => now(),
            'valid_until' => self::quoteValidUntil(),
            'note' => $note ?? $procurement->note,
        ])->save();
    }

    /**
     * پایان ساعات کاری همان روز (ساعت در تنظیمات پنل). اگر اعلام قیمت بعد از ساعت کاری باشد،
     * اعتبار تا پایان ساعات کاری روز بعد است تا استعلام همان لحظه منقضی نشود.
     */
    public static function quoteValidUntil(?CarbonInterface $from = null): CarbonInterface
    {
        $from ??= now();
        [$hour, $minute] = array_map('intval', explode(':', (string) AppSetting::get(AppSetting::BUSINESS_END_TIME)) + [1 => 0]);

        $end = $from->copy()->setTime($hour, $minute);

        return $end->lessThanOrEqualTo($from) ? $end->addDay() : $end;
    }

    public function markReady(OrderProcurement $procurement): void
    {
        $this->requireStatus($procurement, [OrderProcurement::STATUS_PREPARING], 'کالا فقط پس از پیش‌پرداخت آماده می‌شود.');

        $procurement->forceFill(['status' => OrderProcurement::STATUS_READY, 'ready_at' => now()])->save();
        $this->syncDelivery($procurement->order, 'ready');
    }

    public function markShipped(OrderProcurement $procurement): void
    {
        $this->requireStatus($procurement, [OrderProcurement::STATUS_READY], 'فقط کالای آماده ارسال می‌شود.');

        if ($procurement->remaining_amount > 0 && $procurement->remaining_paid_at === null) {
            throw new BusinessRuleException('پیش از ارسال، باقی‌مانده‌ی مبلغ باید پرداخت شود.', 'REMAINING_NOT_PAID');
        }

        $procurement->forceFill(['status' => OrderProcurement::STATUS_SHIPPED, 'shipped_at' => now()])->save();
        $this->syncDelivery($procurement->order, 'shipped');
    }

    public function markDelivered(OrderProcurement $procurement): void
    {
        $this->requireStatus($procurement, [OrderProcurement::STATUS_SHIPPED], 'فقط کالای ارسال‌شده تحویل می‌شود.');

        $procurement->forceFill(['status' => OrderProcurement::STATUS_DELIVERED, 'delivered_at' => now()])->save();

        $order = $procurement->order;
        if ($order && $order->delivered_at === null) {
            $order->forceFill(['delivered_at' => now(), 'delivery_status' => 'delivered'])->save();
        }
    }

    // ----------------------------------------------------------------- کاربر

    public function accept(Order $order): OrderProcurement
    {
        return DB::transaction(function () use ($order) {
            $procurement = $this->lockedFor($order);

            if ($procurement->effectiveStatus() === OrderProcurement::STATUS_EXPIRED) {
                throw new BusinessRuleException('مهلت اعتبار قیمت به پایان رسیده است. درخواست استعلام مجدد بدهید.', 'QUOTE_EXPIRED');
            }
            $this->requireStatus($procurement, [OrderProcurement::STATUS_QUOTED], 'این استعلام در وضعیت قابل تأیید نیست.');

            $procurement->forceFill(['status' => OrderProcurement::STATUS_ACCEPTED, 'accepted_at' => now()])->save();

            return $procurement;
        });
    }

    public function reject(Order $order, ?string $reason): OrderProcurement
    {
        return DB::transaction(function () use ($order, $reason) {
            $procurement = $this->lockedFor($order);
            $this->requireStatus(
                $procurement,
                [OrderProcurement::STATUS_AWAITING_QUOTE, OrderProcurement::STATUS_QUOTED, OrderProcurement::STATUS_EXPIRED, OrderProcurement::STATUS_ACCEPTED],
                'پس از پیش‌پرداخت امکان رد استعلام نیست.'
            );

            $procurement->forceFill([
                'status' => OrderProcurement::STATUS_REJECTED,
                'rejected_at' => now(),
                'reject_reason' => $reason,
            ])->save();

            return $procurement;
        });
    }

    public function requote(Order $order): OrderProcurement
    {
        return DB::transaction(function () use ($order) {
            $procurement = $this->lockedFor($order);
            $this->requireStatus($procurement, [OrderProcurement::STATUS_EXPIRED], 'درخواست استعلام مجدد فقط برای قیمت منقضی‌شده ممکن است.');

            // قیمت‌های قبلی به‌عنوان پیش‌نویس برای پنل می‌مانند
            $procurement->forceFill([
                'status' => OrderProcurement::STATUS_AWAITING_QUOTE,
                'quoted_at' => null,
                'valid_until' => null,
                'accepted_at' => null,
            ])->save();

            return $procurement;
        });
    }

    /**
     * پرداخت یک مرحله. stage: prepayment (فقط در accepted) | remaining (فقط در ready)؛ method: wallet | gateway
     *
     * @return array<string, mixed> data پاسخ
     */
    public function pay(Order $order, User $user, string $stage, string $method, ?string $linkingUrl = null): array
    {
        if ($method === 'wallet') {
            return DB::transaction(function () use ($order, $user, $stage) {
                $procurement = $this->lockedFor($order);
                $amount = $this->payableAmount($procurement, $stage);

                $user = User::query()->lockForUpdate()->findOrFail($user->id);
                if ((int) $user->wallet < $amount) {
                    throw new BusinessRuleException('موجودی کیف پول کافی نیست.', 'INSUFFICIENT_BALANCE');
                }

                $user->wallet -= $amount;
                $user->save();

                $transaction = UserTransaction::create([
                    'user_id' => $user->id,
                    'price' => $amount,
                    'referenceId' => 'PROC_' . $order->id . '_' . $stage . '_' . time(),
                    'type' => 3, // پرداخت از کیف پول
                    'status' => 100,
                    'description' => $this->transactionDescription($order, $stage),
                    'order_id' => $order->id,
                    'payment_method' => 'in_app',
                    'payment_channel' => $this->channel($order),
                ]);

                $this->completePayment($procurement, $stage, $transaction);

                return ['remaining_balance' => (int) $user->wallet];
            });
        }

        $procurement = $order->procurement()->firstOrFail();
        $amount = $this->payableAmount($procurement, $stage);

        if ($amount > 99000000) {
            throw new BusinessRuleException('مبلغ بیش از سقف درگاه (۹۹,۰۰۰,۰۰۰ تومان) است. لطفاً از کیف پول استفاده کنید.', 'AMOUNT_TOO_HIGH');
        }

        $transaction = UserTransaction::create([
            'user_id' => $user->id,
            'price' => $amount,
            'type' => self::TRANSACTION_TYPE_GATEWAY,
            'status' => 0,
            'description' => $this->transactionDescription($order, $stage) . ' از طریق درگاه',
            'linking_url' => $linkingUrl,
            'order_id' => $order->id,
            'payment_method' => 'in_app',
            'payment_channel' => $this->channel($order),
            // مرحله‌ی پرداخت برای callback
            'referenceId' => 'PROC_PENDING_' . $stage . '_' . uniqid(),
        ]);

        $invoice = (new Invoice())->amount($amount)->detail([
            'transactionId' => $transaction->id,
            'orderId' => $order->id,
            'stage' => $stage,
        ]);

        $payment = Payment::via('zibal')
            ->callbackUrl(route('order.procurement.payment.callback', ['stage' => $stage]))
            ->purchase($invoice, function ($driver, $trackId) use ($transaction) {
                $transaction->forceFill(['referenceId' => (string) $trackId])->save();
            });

        return ['payment_url' => $payment->pay()->getAction(), 'transaction_id' => $transaction->id, 'amount' => $amount];
    }

    /** بازگشت موفق از درگاه؛ مبلغ تراکنش باید با مبلغ همان مرحله یکی باشد. */
    public function completeGatewayPayment(UserTransaction $transaction, string $stage, string $referenceId): void
    {
        DB::transaction(function () use ($transaction, $stage, $referenceId) {
            $order = Order::findOrFail($transaction->order_id);
            $procurement = $this->lockedFor($order);

            if ((int) round((float) $transaction->price) !== $this->payableAmount($procurement, $stage)) {
                throw new BusinessRuleException('مبلغ پرداخت با مبلغ این مرحله یکسان نیست.', 'AMOUNT_MISMATCH');
            }

            $transaction->forceFill(['status' => 100, 'referenceId' => $referenceId])->save();
            $this->completePayment($procurement, $stage, $transaction);
        });
    }

    /** پرداخت موفق درگاهی که دیگر به مرحله‌ای تعلق ندارد، به کیف پول کاربر برمی‌گردد. */
    public function refundToWallet(UserTransaction $transaction, string $referenceId): void
    {
        DB::transaction(function () use ($transaction, $referenceId) {
            $user = User::query()->lockForUpdate()->findOrFail($transaction->user_id);
            $amount = (int) round((float) $transaction->price);

            $transaction->forceFill(['status' => 100, 'referenceId' => $referenceId])->save();
            $user->wallet += $amount;
            $user->save();

            UserTransaction::create([
                'user_id' => $user->id,
                'price' => $amount,
                'referenceId' => 'PROC_REFUND_' . $transaction->id,
                'type' => 1, // واریز به کیف پول
                'status' => 100,
                'description' => 'بازگشت مبلغ پرداخت تکراری تامین کالای سفارش شماره ' . $transaction->order_id,
                'order_id' => $transaction->order_id,
                'payment_method' => 'in_app',
                'payment_channel' => $transaction->payment_channel,
            ]);
        });
    }

    // -----------------------------------------------------------------

    private function payableAmount(OrderProcurement $procurement, string $stage): int
    {
        $status = $procurement->effectiveStatus();

        if ($stage === 'prepayment') {
            if ($status !== OrderProcurement::STATUS_ACCEPTED) {
                throw new BusinessRuleException('پیش‌پرداخت فقط پس از تأیید قیمت ممکن است.', 'INVALID_STAGE');
            }

            return (int) $procurement->prepayment_amount;
        }

        if ($stage === 'remaining') {
            if ($status !== OrderProcurement::STATUS_READY || $procurement->remaining_paid_at !== null) {
                throw new BusinessRuleException('پرداخت باقی‌مانده فقط وقتی کالا آماده است ممکن است.', 'INVALID_STAGE');
            }

            return (int) $procurement->remaining_amount;
        }

        throw new BusinessRuleException('مرحله‌ی پرداخت نامعتبر است.', 'INVALID_STAGE');
    }

    private function completePayment(OrderProcurement $procurement, string $stage, UserTransaction $transaction): void
    {
        $order = $procurement->order;

        if ($stage === 'prepayment') {
            $procurement->forceFill(['status' => OrderProcurement::STATUS_PREPARING, 'prepayment_paid_at' => now()])->save();
            $this->syncDelivery($order, 'pending');
        } else {
            $procurement->forceFill(['remaining_paid_at' => now()])->save();

            // سفارشی که فقط تامین کالاست با پرداخت باقی‌مانده تسویه می‌شود تا مراحل «وضعیت ارسال»
            // و «دریافت محصول» در اپ باز شوند. سفارشی که خدمات هم دارد، پرداخت خدماتش جداست.
            if ($this->isProcurementOnly($order)) {
                $order->payment_status = 1;
                $order->markPaidInApp();
                $order->save();
            }
        }

        // مبلغ کالای تامین‌شده در پورسانت مأمور خرید حساب می‌شود
        if ($order->referral_code_id) {
            app(ReferralAgentService::class)->syncForOrder($order);
        }

        Log::info('procurement payment completed', [
            'order_id' => $order->id,
            'stage' => $stage,
            'transaction_id' => $transaction->id,
        ]);
    }

    public function isProcurementOnly(Order $order): bool
    {
        return (float) ($order->pakar_price ?? 0) <= 0 && (float) ($order->technician_price ?? 0) <= 0;
    }

    private function syncDelivery(?Order $order, string $status): void
    {
        if (!$order) {
            return;
        }

        if ($order->delivery_status !== 'delivered') {
            $order->forceFill(['delivery_status' => $status])->save();
        }
    }

    private function lockedFor(Order $order): OrderProcurement
    {
        $procurement = OrderProcurement::query()->where('order_id', $order->id)->lockForUpdate()->first();

        if (!$procurement) {
            throw new BusinessRuleException('این سفارش استعلام تامین کالا ندارد.', 'PROCUREMENT_NOT_FOUND', 404);
        }

        return $procurement;
    }

    private function requireStatus(OrderProcurement $procurement, array $allowed, string $message): void
    {
        if (!in_array($procurement->effectiveStatus(), $allowed, true)) {
            throw new BusinessRuleException($message, 'INVALID_STATUS');
        }
    }

    private function transactionDescription(Order $order, string $stage): string
    {
        return ($stage === 'prepayment' ? 'پیش‌پرداخت' : 'پرداخت باقی‌مانده‌ی') . ' تامین کالای سفارش شماره ' . $order->id;
    }

    private function channel(Order $order): string
    {
        return in_array($order->platform, ['web', 'site'], true) ? 'site' : 'app';
    }
}
