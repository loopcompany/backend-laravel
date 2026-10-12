<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\AppSetting;
use App\Models\Order;
use App\Models\ReferralCode;
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
     * پورسانت سفارش را با وضعیت سفارش هم‌گام می‌کند. فقط کد معرفِ متعلق به مأمور خرید پورسانت دارد.
     */
    public function syncForOrder(Order $order): ?ReferralCommission
    {
        if (!$order->referral_code_id) {
            return null;
        }

        $code = ReferralCode::with('user')->find($order->referral_code_id);
        $agent = $code?->user;

        if (!$agent || !$agent->is_referral_agent || (int) $agent->id === (int) $order->user_id) {
            return null;
        }

        $commission = ReferralCommission::firstOrNew(['order_id' => $order->id]);

        if (in_array($commission->status, [ReferralCommission::STATUS_WITHDRAWN, ReferralCommission::STATUS_OFFSET], true)) {
            return $commission;
        }

        $percent = (int) ($order->referral_commission_percent ?: $code->commission_percent);
        $orderAmount = $this->commissionBase($order);
        $status = $this->statusFor($order);

        $commission->fill([
            'user_id' => $agent->id,
            'referral_code_id' => $code->id,
            'order_amount' => $orderAmount,
            'commission_percent' => $percent,
            'commission_amount' => (int) round($orderAmount * $percent / 100),
            'status' => $status,
            'settled_at' => $status === ReferralCommission::STATUS_AVAILABLE ? ($commission->settled_at ?? now()) : null,
        ])->save();

        return $commission;
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
        if (!$user->is_referral_agent) {
            return ['is_agent' => false];
        }

        $this->refreshPending($user);
        $code = $this->agentCode($user);
        $balances = $this->balances($user);

        return [
            'is_agent' => true,
            'referral_code' => $code?->code,
            'commission_percent' => (int) ($code?->commission_percent ?? 0),
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
        if (!$user->is_referral_agent) {
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
    public function assignAgent(User $user, int $commissionPercent, int $discountPercent): ReferralCode
    {
        return DB::transaction(function () use ($user, $commissionPercent, $discountPercent) {
            $user->forceFill(['is_referral_agent' => true])->save();

            $code = $this->agentCode($user) ?? new ReferralCode(['user_id' => $user->id]);
            $code->fill([
                'multi_use' => true,
                'status' => ReferralCode::STATUS_ACTIVE,
                'commission_percent' => $commissionPercent,
                'discount_percent' => $discountPercent,
            ])->save();

            return $code;
        });
    }

    public function agentCode(User $user): ?ReferralCode
    {
        return ReferralCode::query()->where('user_id', $user->id)->where('multi_use', true)->orderBy('id')->first();
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
