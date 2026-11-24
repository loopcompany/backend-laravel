<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\AppSetting;
use App\Models\Order;
use App\Models\ReferralCommission;
use App\Models\ReferralWithdrawal;
use App\Models\User;
use App\Models\UserTransaction;
use Illuminate\Support\Facades\DB;

/**
 * کیف پول مأمور خرید (سند اپ کاربر، بخش ۱۳).
 *
 * موجودی به‌صورت دفتر حساب محاسبه می‌شود:
 *   قابل برداشت = پورسانت‌های available − برداشت‌های شبای در جریان/واریزشده − تهاترها
 * برداشت ردشده از محاسبه خارج می‌شود، پس مبلغش خودبه‌خود به موجودی برمی‌گردد.
 */
class ReferralAgentService
{
    /** نوع تراکنش کیف پول برای «تهاتر پورسانت» (۴ قبلاً برای پرداخت خارج از برنامه استفاده شده است). */
    public const TRANSACTION_TYPE_OFFSET = 5;

    private const CANCELED_ORDER_STATUSES = [3, 4, 5, 6];

    /**
     * پورسانت سفارش را برای مأمور خرید هم‌گام می‌کند و اعتبار قطعی قبلی را حفظ می‌کند.
     */
    public function syncForOrder(Order $order): ?ReferralCommission
    {
        return DB::transaction(function () use ($order) {
            $commission = ReferralCommission::where('order_id', $order->id)->first();
            $owner = User::lockForUpdate()->find($commission?->user_id ?? $order->user_id);
            $commission = ReferralCommission::where('order_id', $order->id)->first();
            // Historical credited money is immutable, including credits earned under the retired policy.
            if ($commission && ($commission->settled_at || in_array($commission->status, [
                ReferralCommission::STATUS_AVAILABLE, ReferralCommission::STATUS_WITHDRAWN, ReferralCommission::STATUS_OFFSET,
            ], true))) return $commission;
            if (!$owner || (!$owner->is_referral_agent && !$commission)) return $commission;
            if ($commission?->referral_code_id) {
                if (in_array((int) $order->status, self::CANCELED_ORDER_STATUSES, true)) {
                    $commission->update(['status' => ReferralCommission::STATUS_CANCELED]);
                }
                return $commission;
            }
            $commission ??= new ReferralCommission(['order_id' => $order->id]);
            $service = max(0, (int) ($order->technician_price ?? $order->pakar_price ?? 0));
            $goods = max(0, (int) $order->extra_price) + (int) ($order->procurement?->total_amount ?? 0);
            $amount = $commission->manual_override_amount ?? app(ReferralCommissionTable::class)->calculate($service, $goods);
            $canceled = in_array((int) $order->status, self::CANCELED_ORDER_STATUSES, true);
            $commission->fill(['user_id' => $owner->id, 'referral_code_id' => null,
                'order_amount' => $service + $goods, 'commission_percent' => 0,
                'commission_amount' => $amount ?? 0, 'status' => $canceled ? ReferralCommission::STATUS_CANCELED : ReferralCommission::STATUS_PENDING,
                'calculation_note' => $amount === null ? 'مبلغ در جدول نیست؛ تعیین پورسانت دستی توسط مدیریت لازم است.' : ($commission->manual_override_amount !== null ? 'مبلغ دستی مدیریت' : 'ردیف دقیق جدول کارفرما'),
            ]);
            if ($commission->isDirty()) $commission->save();
            return $commission;
        }, 3);
    }

    public function approveForWithdrawal(ReferralCommission $commission, \App\Models\Admin $admin): void
    {
        abort_unless($admin->is_active && ($admin->isSuperAdmin() || $admin->can('edit-referral-withdrawals')), 403);

        DB::transaction(function () use ($commission, $admin) {
            $order = Order::query()->lockForUpdate()->findOrFail($commission->order_id);
            $current = $this->syncForOrder($order);
            if ($current?->settled_at) return;

            if (! $order->settled_at || $current?->status !== ReferralCommission::STATUS_PENDING) {
                throw \Illuminate\Validation\ValidationException::withMessages(['commission' => 'ابتدا سفارش باید به‌طور کامل تسویه شود.']);
            }
            if ($current->commission_amount <= 0 && $current->manual_override_amount === null) {
                throw \Illuminate\Validation\ValidationException::withMessages(['commission' => 'ابتدا پورسانت دقیق را تعیین کنید.']);
            }

            $current->forceFill(['status' => ReferralCommission::STATUS_AVAILABLE, 'settled_at' => now(), 'approved_by' => $admin->id])->save();
        }, 3);
    }

    public function settleManual(ReferralCommission $commission, int $amount): void
    {
        DB::transaction(function () use ($commission,$amount) {
            User::lockForUpdate()->findOrFail($commission->user_id);
            $commission=ReferralCommission::lockForUpdate()->findOrFail($commission->id);
            if ($commission->settled_at || $amount < 0) abort(422);
            $commission->update(['manual_override_amount'=>$amount,'commission_amount'=>$amount,'calculation_note'=>'مبلغ دستی مدیریت']);
            $this->syncForOrder($commission->order);
        });
    }

    /** هم‌گام‌سازی پورسانت‌های در انتظار یک مأمور (برای تغییر وضعیت‌هایی که از query builder ثبت شده‌اند). */
    public function refreshPending(User $agent): void
    {
        ReferralCommission::query()
            ->where('user_id', $agent->id)
            ->where('status', ReferralCommission::STATUS_PENDING)
            ->with('order')
            ->get()
            ->each(fn (ReferralCommission $c) => $c->order && $this->syncForOrder($c->order));
    }

    public function summary(User $user): array
    {
        if (!$this->canAccessWallet($user)) {
            return ['is_agent' => false];
        }

        $this->refreshPending($user);
        $balances = $this->balances($user);

        return [
            'is_agent' => true,
            'commission_mode' => 'table',
            'available_balance' => $balances['available'],
            'pending_balance' => $balances['pending'],
            'total_earned' => $balances['earned'],
            'total_withdrawn' => $balances['withdrawn'],
            'total_offset' => $balances['offset'],
            'min_withdraw_amount' => AppSetting::int(AppSetting::REFERRAL_MIN_WITHDRAW_AMOUNT),
            'sheba_number' => $user->sheba_number,
        ];
    }

    /** @return array{available: int, pending: int, earned: int, withdrawn: int, offset: int, reserved: int} */
    public function balances(User $user): array
    {
        $commissions = ReferralCommission::query()->where('user_id', $user->id)
            ->selectRaw('status, SUM(commission_amount) as total')->groupBy('status')->pluck('total', 'status');

        $withdrawals = ReferralWithdrawal::query()->where('user_id', $user->id);

        $earned = (int) ($commissions[ReferralCommission::STATUS_AVAILABLE] ?? 0)
            + (int) ($commissions[ReferralCommission::STATUS_WITHDRAWN] ?? 0)
            + (int) ($commissions[ReferralCommission::STATUS_OFFSET] ?? 0);
        $reserved = (int) (clone $withdrawals)->where('method', ReferralWithdrawal::METHOD_SHEBA)
            ->whereIn('status', [ReferralWithdrawal::STATUS_PENDING, ReferralWithdrawal::STATUS_APPROVED])->sum('amount');
        $withdrawn = (int) (clone $withdrawals)->where('method', ReferralWithdrawal::METHOD_SHEBA)
            ->whereIn('status', [ReferralWithdrawal::STATUS_PAID, ReferralWithdrawal::STATUS_DONE])->sum('amount');
        $offset = (int) (clone $withdrawals)->where('method', ReferralWithdrawal::METHOD_OFFSET)
            ->where('status', ReferralWithdrawal::STATUS_DONE)->sum('amount');

        return [
            'available' => max(0, $earned - $reserved - $withdrawn - $offset),
            'pending' => (int) ($commissions[ReferralCommission::STATUS_PENDING] ?? 0),
            'earned' => $earned,
            'withdrawn' => $withdrawn,
            'offset' => $offset,
            'reserved' => $reserved,
        ];
    }

    /**
     * method: sheba (درخواست در صف پنل؛ مبلغ رزرو می‌شود) | offset (فوری به کیف پول لوپ)
     */
    public function withdraw(User $user, int $amount, string $method, ?string $sheba): ReferralWithdrawal
    {
        if (!$this->canAccessWallet($user)) {
            throw new BusinessRuleException('این بخش فقط برای مأمور خرید فعال است.', 'NOT_AGENT', 403);
        }

        return DB::transaction(function () use ($user, $amount, $method, $sheba) {
            // قفل ردیف کاربر: دو درخواست هم‌زمان نمی‌توانند یک موجودی را دو بار برداشت کنند
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $available = $this->balances($user)['available'];

            if ($amount < 1) {
                throw new BusinessRuleException('مبلغ برداشت نامعتبر است.', 'INVALID_AMOUNT');
            }
            if ($amount > $available) {
                throw new BusinessRuleException('مبلغ درخواستی بیشتر از موجودی قابل برداشت است.', 'INSUFFICIENT_BALANCE');
            }

            if ($method === ReferralWithdrawal::METHOD_OFFSET) {
                $user->wallet += $amount;
                $user->save();

                $transaction = UserTransaction::create([
                    'user_id' => $user->id,
                    'price' => $amount,
                    'referenceId' => 'COMMISSION_OFFSET_' . $user->id . '_' . time(),
                    'type' => self::TRANSACTION_TYPE_OFFSET,
                    'status' => 100,
                    'description' => 'تهاتر پورسانت',
                    'payment_method' => 'in_app',
                    'payment_channel' => 'app',
                ]);

                return ReferralWithdrawal::create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'method' => ReferralWithdrawal::METHOD_OFFSET,
                    'status' => ReferralWithdrawal::STATUS_DONE,
                    'user_transaction_id' => $transaction->id,
                    'processed_at' => now(),
                ]);
            }

            $minimum = AppSetting::int(AppSetting::REFERRAL_MIN_WITHDRAW_AMOUNT);
            if ($amount < $minimum) {
                throw new BusinessRuleException('حداقل مبلغ برداشت به شبا ' . number_format($minimum) . ' تومان است.', 'BELOW_MINIMUM');
            }

            $sheba = self::normalizeSheba($sheba ?: $user->sheba_number);
            if (!$sheba || !self::isValidSheba($sheba)) {
                throw new BusinessRuleException('شماره شبا نامعتبر است.', 'INVALID_SHEBA');
            }

            return ReferralWithdrawal::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'method' => ReferralWithdrawal::METHOD_SHEBA,
                'status' => ReferralWithdrawal::STATUS_PENDING,
                'sheba_number' => $sheba,
            ]);
        });
    }

    /** کد معرف چندبارمصرف مأمور؛ اگر نبود ساخته می‌شود. */
    public function setAgent(User $user, bool $enabled): void
    {
        DB::transaction(function () use ($user, $enabled) {
            $user = User::lockForUpdate()->findOrFail($user->id);
            $user->forceFill(['is_referral_agent' => $enabled])->save();
        }, 3);
    }

    public function canAccessWallet(User $user): bool
    {
        return $user->is_referral_agent || ReferralCommission::where('user_id', $user->id)->exists();
    }

    public function agentCode(User $user): ?ReferralCode
    {
        return ReferralCode::query()->where('user_id', $user->id)->where(fn ($q)=>$q->where('multi_use',true)->orWhere('policy_version',ReferralCode::CURRENT_POLICY))->orderByDesc('id')->first();
    }

    public static function normalizeSheba(?string $sheba): ?string
    {
        if ($sheba === null) {
            return null;
        }

        $sheba = strtoupper(preg_replace('/[\s-]+/', '', strtr($sheba, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ])));

        if (preg_match('/^[0-9]{24}$/', $sheba)) {
            $sheba = 'IR' . $sheba;
        }

        return $sheba ?: null;
    }

    /** IR + ۲۴ رقم با کنترل checksum (ISO 13616، mod 97). */
    public static function isValidSheba(string $sheba): bool
    {
        if (!preg_match('/^IR[0-9]{24}$/', $sheba)) {
            return false;
        }

        // IR → 1827، چهار کاراکتر اول به انتها منتقل می‌شود
        $numeric = substr($sheba, 4) . '1827' . substr($sheba, 2, 2);

        // mod 97 تکه‌تکه، بدون نیاز به افزونه‌ی bcmath
        $remainder = 0;
        foreach (str_split($numeric, 7) as $chunk) {
            $remainder = (int) ($remainder . $chunk) % 97;
        }

        return $remainder === 1;
    }

    // -----------------------------------------------------------------

    /** مبلغ مبنای پورسانت: خدمات پس از کسر تخفیف همان کد + کالای تامین‌شده‌ی پیش‌پرداخت‌شده. */
    private function commissionBase(Order $order): int
    {
        $base = ($order->technician_price ?? $order->pakar_price ?? 0) + ($order->extra_price ?? 0);
        $base -= $order->referralDiscountAmount($base);

        $procurement = $order->procurement()->first();
        if ($procurement && $procurement->prepayment_paid_at !== null) {
            $base += $procurement->total_amount;
        }

        return (int) round(max(0, $base));
    }

    private function statusFor(Order $order): string
    {
        if (in_array((int) $order->status, self::CANCELED_ORDER_STATUSES, true)) {
            return ReferralCommission::STATUS_CANCELED;
        }

        $settled = (int) $order->payment_status === 1
            && ((int) $order->status === 2 || $order->procurement()->whereNotNull('remaining_paid_at')->exists());

        return $settled ? ReferralCommission::STATUS_AVAILABLE : ReferralCommission::STATUS_PENDING;
    }
}
