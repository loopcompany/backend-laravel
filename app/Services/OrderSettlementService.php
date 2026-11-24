<?php

namespace App\Services;

use App\Models\{Admin, Order, OrderReceipt, OrderStageLog, Technician, UserTransaction};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** All monetary values are integer tomans, matching the existing order/payment ledger. */
class OrderSettlementService
{
    public function total(Order $order): int
    {
        $copy = clone $order;
        $copy->prepayment = 0;
        $copy->prepayment_payment_status = 0;
        $copy->technician_price = $order->technician_price ?? $order->pakar_price ?? 0;
        $goods = (int) ($order->procurement?->total_amount ?? 0);
        if ((int) $copy->technician_price + (int) $copy->extra_price <= 0) return $goods;
        return (int) round($copy->payment_price(true, false)) + $goods;
    }

    public function paid(Order $order): int
    {
        $transactions = UserTransaction::where('order_id', $order->id)->where('status', 100);
        $received = (int) (clone $transactions)->whereIn('type', [2, 3, 4, ProcurementService::TRANSACTION_TYPE_GATEWAY])->sum('price');
        $reimbursed = (int) $transactions->where('type', 1)->where(function ($query) {
            $query->where('referenceId', 'like', 'PROC_REFUND_%')->orWhere('referenceId', 'like', 'ORDER_REFUND_%');
        })->sum('price');
        return max(0, $received - $reimbursed);
    }

    public function summary(Order $order): array
    {
        $total = $this->total($order);
        $paid = $this->paid($order);
        $pending = (int) OrderReceipt::where('order_id', $order->id)->where('status', 'pending')->sum('amount');
        return ['order_id' => $order->id, 'currency' => 'IRT', 'total' => $total, 'paid' => $paid, 'pending' => $pending,
            'remaining' => max(0, $total - $paid), 'recordable' => max(0, $total - $paid - $pending), 'settled_at' => $order->settled_at,
            'receipts' => OrderReceipt::where('order_id', $order->id)->orderByDesc('id')->get()];
    }

    public function assertOnlinePaymentAllowed(Order $order): void
    {
        if (OrderReceipt::where('order_id', $order->id)->where('status', 'pending')->exists()) {
            $this->invalid('دریافت تکنسین در انتظار تأیید ادمین است؛ پس از بررسی، ماندهٔ قطعی قابل پرداخت می‌شود.');
        }
    }

    public function record(Order $order, Technician|Admin $actor, array $data, ?string $receiptPath = null): OrderReceipt
    {
        return DB::transaction(function () use ($order, $actor, $data, $receiptPath) {
            $order = Order::lockForUpdate()->findOrFail($order->id);
            $this->authorizeActor($order, $actor);
            $channel = $actor instanceof Admin ? ($data['payment_channel'] ?? 'in_person') : 'in_person';
            $existing = OrderReceipt::where('request_uuid', $data['request_uuid'])->first();
            $hash = hash('sha256', json_encode([$order->id, $actor::class, $actor->id, (int) $data['amount'], $data['method'],
                $data['collection_target'], $channel, $data['item_description'] ?? null, $data['reference'] ?? null, $data['receipt_sha256'] ?? null], JSON_UNESCAPED_UNICODE));
            if ($existing) {
                if (! hash_equals($existing->payload_hash, $hash)) $this->invalid('کلید این درخواست قبلاً برای دریافت دیگری استفاده شده است.');
                return $existing;
            }
            if ($order->settled_at || in_array((int) $order->status, [3, 4, 5, 6], true)) $this->invalid('این سفارش دریافت تازه نمی‌پذیرد.');
            if ((int) $order->payment_status === 1 && $this->paid($order) === 0) {
                $this->invalid('سفارش قبلاً پرداخت‌شده ثبت شده است. سوابق پرداخت باید در حسابداری بررسی شود.');
            }
            if (UserTransaction::where('order_id', $order->id)->whereIn('type', [2, ProcurementService::TRANSACTION_TYPE_GATEWAY])->where('status', 0)->where('created_at', '>=', now()->subMinutes(30))->exists()) {
                $this->invalid('پرداخت درگاه در جریان است. پس از مشخص‌شدن نتیجهٔ آن، دریافت را ثبت کنید.');
            }
            $methods = $actor instanceof Admin ? OrderReceipt::COMPANY_METHODS : OrderReceipt::METHODS;
            if (! isset($methods[$data['method']]) || ! in_array($channel, ['in_person', 'site'], true) || ! in_array($data['collection_target'], ['company', 'technician'], true)) $this->invalid('روش یا مقصد دریافت معتبر نیست.');
            $amount = (int) $data['amount'];
            if ($amount < 1 || $amount > $this->summary($order)['recordable']) $this->invalid('مبلغ از ماندهٔ قابل دریافت سفارش بیشتر است.');
            if ($data['method'] === 'barter' && ! filled($data['item_description'] ?? null)) $this->invalid('شرح و ارزش کالای تهاتری الزامی است.');
            if ($actor instanceof Technician && in_array($data['method'], ['cash', 'barter'], true) && $data['collection_target'] !== 'technician') $this->invalid('وجه نقد و کالای تهاتری در امانت دریافت‌کننده ثبت می‌شوند.');
            $internal = $actor instanceof Admin || str_contains($actor->technician_type ?? '', 'داخلی') || str_contains(mb_strtolower($actor->technician_type ?? ''), 'internal');
            $receipt = OrderReceipt::create([
                'order_id' => $order->id, 'technician_id' => $actor instanceof Technician ? $actor->id : null,
                'created_by_admin_id' => $actor instanceof Admin ? $actor->id : null, 'request_uuid' => $data['request_uuid'], 'payload_hash' => $hash,
                'method' => $data['method'], 'amount' => $amount, 'collection_target' => $data['collection_target'], 'payment_channel' => $channel,
                'item_description' => $data['item_description'] ?? null, 'reference' => $data['reference'] ?? null, 'receipt_path' => $receiptPath,
                'status' => $internal ? 'approved' : 'pending', 'reviewed_by' => $actor instanceof Admin ? $actor->id : null,
                'reviewed_at' => $internal ? now() : null,
                'custody_status' => $actor instanceof Technician && $data['collection_target'] === 'technician' ? 'held' : 'none',
            ]);
            $this->audit($order, $actor, 'receipt_recorded', 'ثبت دریافت '.$amount.' تومان', ['receipt_id' => $receipt->id]);
            if ($internal) $this->credit($order, $receipt);
            return $receipt;
        }, 3);
    }

    public function review(OrderReceipt $receipt, bool $approved, ?string $note, Admin $admin): OrderReceipt
    {
        $this->assertPermission($admin, 'review-order-settlements');
        return DB::transaction(function () use ($receipt, $approved, $note, $admin) {
            $order = Order::lockForUpdate()->findOrFail($receipt->order_id);
            $receipt = OrderReceipt::lockForUpdate()->findOrFail($receipt->id);
            if ($receipt->status !== 'pending') $this->invalid('این دریافت قبلاً بررسی شده است.');
            if (! $approved && ! filled($note)) $this->invalid('علت رد دریافت الزامی است.');
            if ($approved && $receipt->amount > max(0, $this->total($order) - $this->paid($order))) $this->invalid('مبلغ دریافت از بدهی فعلی بیشتر است؛ ابتدا مغایرت پرداخت بررسی شود.');
            $receipt->update(['status' => $approved ? 'approved' : 'rejected', 'reviewed_by' => $admin->id, 'reviewed_at' => now(), 'review_note' => $note]);
            $this->audit($order, $admin, $approved ? 'receipt_approved' : 'receipt_rejected', $note, ['receipt_id' => $receipt->id]);
            if ($approved) $this->credit($order, $receipt);
            return $receipt;
        }, 3);
    }

    public function returnCustody(OrderReceipt $receipt, Admin $admin): void
    {
        $this->assertPermission($admin, 'receive-technician-custody');
        DB::transaction(function () use ($receipt, $admin) {
            $receipt = OrderReceipt::lockForUpdate()->findOrFail($receipt->id);
            if ($receipt->custody_status !== 'held') $this->invalid('این امانت قبلاً تحویل شده یا در اختیار تکنسین نیست.');
            $receipt->update(['custody_status' => 'returned', 'returned_by' => $admin->id, 'returned_at' => now()]);
            $this->audit($receipt->order, $admin, 'custody_returned', 'تحویل امانت تکنسین به شرکت', ['receipt_id' => $receipt->id]);
        }, 3);
    }

    /** Called on committed successful gateway/wallet and procurement payments as well. */
    public function reconcile(Order $order, ?Admin $admin = null): void
    {
        DB::transaction(function () use ($order, $admin) {
            $locked = Order::lockForUpdate()->findOrFail($order->id);
            if ($locked->settled_at || in_array((int) $locked->status, [3, 4, 5, 6], true)) return;
            $total = $this->total($locked);
            if ($total < 1 || $this->paid($locked) < $total || OrderReceipt::where('order_id', $locked->id)->where('status', 'pending')->exists()) return;
            // This is a financial transition, never a reopening/editing of the closed service file.
            $methods = UserTransaction::where('order_id', $locked->id)->where('status', 100)->whereIn('type', [2, 3, 4, ProcurementService::TRANSACTION_TYPE_GATEWAY])->get()->pluck('payment_method')->unique();
            $settlementData = ['settled_at' => now(), 'settled_by_admin_id' => $admin?->id, 'payment_status' => 1, 'remaining_amount' => null, 'payment_method' => $methods->count() === 1 ? $methods->first() : 'mixed'];
            $channels = $locked->receipts()->where('status', 'approved')->distinct()->pluck('payment_channel');
            if ($channels->isNotEmpty()) $settlementData['payment_channel'] = $channels->contains('in_person') ? 'in_person' : 'site';
            $locked->forceFill($settlementData)->saveQuietly();
            if ($procurement = $locked->procurement) {
                $procurement->forceFill(['prepayment_paid_at' => $procurement->prepayment_paid_at ?? now(), 'remaining_paid_at' => $procurement->remaining_paid_at ?? now()])->saveQuietly();
            }
            $this->audit($locked, $admin, 'order_settled', 'تسویهٔ کامل سفارش', ['amount' => $total]);
            app(ReferralAgentService::class)->syncForOrder($locked);
        }, 3);
    }

    private function credit(Order $order, OrderReceipt $receipt): void
    {
        UserTransaction::firstOrCreate(['order_receipt_id' => $receipt->id], [
            'user_id' => $order->user_id, 'order_id' => $order->id, 'price' => $receipt->amount, 'type' => 4, 'status' => 100,
            'payment_method' => $receipt->method, 'payment_channel' => $receipt->payment_channel, 'order_payment_kind' => 'order',
            'referenceId' => 'RECEIPT_'.$receipt->id, 'description' => 'دریافت تأییدشدهٔ سفارش شماره '.$order->id,
        ]);
        $remaining = max(0, $this->total($order) - $this->paid($order));
        if ($remaining > 0) $order->forceFill(['remaining_amount' => $remaining, 'payment_method' => 'remaining', 'payment_channel' => $receipt->payment_channel])->saveQuietly();
        $this->reconcile($order, $receipt->reviewed_by ? Admin::find($receipt->reviewed_by) : null);
    }

    private function authorizeActor(Order $order, Technician|Admin $actor): void
    {
        if ($actor instanceof Admin) { $this->assertPermission($actor, 'review-order-settlements'); return; }
        abort_unless($actor->has_access && $actor->isApproved() && (int) $order->technician_id === (int) $actor->id, 403);
    }

    public function assertPermission(Admin $admin, string $permission): void
    {
        abort_unless($admin->is_active && ($admin->isSuperAdmin() || $admin->can($permission)), 403);
    }

    private function audit(Order $order, Technician|Admin|null $actor, string $action, ?string $note, array $meta): void
    {
        OrderStageLog::create(['order_id' => $order->id, 'action' => $action, 'note' => $note, 'meta' => $meta,
            'actor_type' => $actor instanceof Admin ? 'admin' : ($actor instanceof Technician ? 'technician' : 'system'),
            'actor_id' => $actor?->id, 'actor_name' => $actor?->name ?? 'سامانه']);
    }

    private function invalid(string $message): never { throw ValidationException::withMessages(['settlement' => $message]); }
}
