<?php

namespace App\Services;

use App\DTOs\WalletPaymentDTO;
use App\Models\MinPrice;
use App\Repositories\OrderRepository;
use App\Repositories\WalletRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderPaymentService
{
    public function __construct(
        protected OrderRepository $orderRepo,
        protected WalletRepository $walletRepo
    ) {
    }

    /**
     * پرداخت سفارش از طریق کیف پول
     */
    public function payOrderWithWallet(WalletPaymentDTO $dto): array
    {
        $min_price = MinPrice::orderByDesc('id')->first();

        try {
            DB::beginTransaction();

            // 1. پیدا کردن سفارش
            $order = $this->orderRepo->findUserOrder($dto->userId, $dto->orderId);

            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'سفارش یافت نشد!',
                    'error_code' => 'ORDER_NOT_FOUND'
                ];
            }



            // بارگذاری discountUse برای محاسبه تخفیف
            $order->load('discountUse.discount_code');

            // 2. بررسی اینکه سفارش قبلاً پرداخت نشده باشد
            if ($order->payment_status == 1) {
                return [
                    'success' => false,
                    'message' => 'این سفارش قبلاً پرداخت شده است.',
                    'error_code' => 'ALREADY_PAID'
                ];
            }
            $pay_type = 'order';
            $discountAmount = 0;

            if ((($order?->status == 4 && $order?->technician_cancel_reason != 'اعلام حضور / لغو از سوی تکنسین') || ($order?->status == 3 && $order?->arrived_at))) {

                $totalPrice = 200000;

            } else if (!is_null($order->prepayment) && $order->prepayment != 0 && $order->prepayment_payment_status == 0 && !is_null($order->loop_cost_estimate) && $order->loop_cost_estimate > 0) {

                $totalPrice = $order->loop_cost_estimate * $order->prepayment / 100;

                $pay_type = 'prepay';
            } else {

                $basePrice = $order->technician_price ?? $order->pakar_price ?? 0;
                $extraPrice = $order->extra_price ?? 0;
                $totalBeforeDiscount = $basePrice + $extraPrice;
                if (!is_null($order->prepayment) && $order->prepayment != 0 && $order->prepayment_payment_status == 1 && !is_null($order->loop_cost_estimate) && $order->loop_cost_estimate > 0) {
                    $totalBeforeDiscount -= ($order->loop_cost_estimate * $order->prepayment / 100);
                }
                $discountAmount = $this->orderRepo->calculateDiscountFromUse($order);

                $totalPrice = max(0, $totalBeforeDiscount - $discountAmount);

                if ($totalPrice < $min_price?->price) {
                    $totalPrice += 200000;
                }
            }

            // 3. محاسبه قیمت کل و مبلغ تخفیف

            // محاسبه تخفیف از discount_uses

            if ($totalPrice <= 0) {
                return [
                    'success' => false,
                    'message' => 'قیمت سفارش نامعتبر است.',
                    'error_code' => 'INVALID_PRICE'
                ];
            }

            // 4. بررسی موجودی کافی
            if (!$this->walletRepo->hasEnoughBalance($dto->userId, $totalPrice)) {
                return [
                    'success' => false,
                    'message' => 'موجودی کیف پول کافی نیست.',
                    'error_code' => 'INSUFFICIENT_BALANCE'
                ];
            }

            // 5. کسر از کیف پول کاربر و ثبت تراکنش
            $deductResult = $this->walletRepo->deductForPayment(
                $dto->userId,
                $dto->orderId,
                $totalPrice,
                $pay_type
            );

            if (!$deductResult['success']) {
                DB::rollBack();
                return $deductResult;
            }

            // 6. به‌روزرسانی وضعیت پرداخت سفارش و ثبت مبلغ تخفیف
            $this->orderRepo->updatePaymentStatus($dto->orderId, 1, $pay_type);

            // ثبت مبلغ تخفیف در صورت وجود
            if ($discountAmount > 0) {
                $order->discount_price = $discountAmount;
                $order->save();
            }

            DB::commit();

            Log::info(($pay_type) == 'prepay' ? 'پیش پرداخت سفارش از کیف پول' : 'پرداخت سفارش از کیف پول موفق', [
                'user_id' => $dto->userId,
                'order_id' => $dto->orderId,
                'amount' => $totalPrice,
                'transaction_id' => $deductResult['transaction']->id
            ]);

            return [
                'success' => true,
                'message' => ($pay_type) == 'prepay' ? 'پیش پرداخت سفارش با موفقیت پرداخت شد.' : 'سفارش شما با موفقیت پرداخت شد.',
                'data' => [
                    'order_id' => $dto->orderId,
                    'paid_amount' => $totalPrice,
                    'remaining_balance' => $deductResult['remaining_balance'],
                    'transaction_id' => $deductResult['transaction']->id,
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('خطا در پرداخت سفارش از کیف پول', [
                'user_id' => $dto->userId,
                'order_id' => $dto->orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در پرداخت سفارش. لطفاً دوباره تلاش کنید.',
                'error_code' => 'PAYMENT_ERROR'
            ];
        }
    }
}
