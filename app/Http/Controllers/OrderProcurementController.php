<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\User;
use App\Models\UserTransaction;
use App\Services\ProcurementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Payment\Facade\Payment;
use Throwable;

/**
 * اقدامات کاربر روی استعلام تامین کالا و مشخصات تحویل (سند اپ کاربر، بخش ۹-۴ و ۱۰-۲).
 */
class OrderProcurementController extends Controller
{
    public function __construct(private readonly ProcurementService $procurements)
    {
    }

    /** POST /api/orders/{id}/procurement/accept */
    public function accept(Request $request, int $orderId): JsonResponse
    {
        $procurement = $this->procurements->accept($this->ownedOrder($request, $orderId));

        return $this->ok('قیمت‌ها تأیید شد. لطفاً پیش‌پرداخت را انجام دهید.', $procurement->toApiArray());
    }

    /** POST /api/orders/{id}/procurement/reject */
    public function reject(Request $request, int $orderId): JsonResponse
    {
        $data = $request->validate(['reason' => 'nullable|string|max:500'], ['reason.max' => 'توضیح رد حداکثر ۵۰۰ کاراکتر است.']);

        $procurement = $this->procurements->reject($this->ownedOrder($request, $orderId), $data['reason'] ?? null);

        return $this->ok('استعلام رد شد.', $procurement->toApiArray());
    }

    /** POST /api/orders/{id}/procurement/requote */
    public function requote(Request $request, int $orderId): JsonResponse
    {
        $procurement = $this->procurements->requote($this->ownedOrder($request, $orderId));

        return $this->ok('درخواست استعلام مجدد ثبت شد.', $procurement->toApiArray());
    }

    /** POST /api/orders/{id}/procurement/pay { stage, method, linking_url? } */
    public function pay(Request $request, int $orderId): JsonResponse
    {
        $data = $request->validate([
            'stage' => 'required|string|in:prepayment,remaining',
            'method' => 'required|string|in:wallet,gateway',
            'linking_url' => 'nullable|string|max:500',
        ], [
            'stage.required' => 'مرحله‌ی پرداخت الزامی است.',
            'stage.in' => 'مرحله‌ی پرداخت نامعتبر است.',
            'method.required' => 'روش پرداخت الزامی است.',
            'method.in' => 'روش پرداخت نامعتبر است.',
        ]);

        $order = $this->ownedOrder($request, $orderId);

        try {
            $result = $this->procurements->pay($order, $request->user(), $data['stage'], $data['method'], $data['linking_url'] ?? null);
        } catch (BusinessRuleException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('procurement gateway payment failed', ['order_id' => $orderId, 'error' => $e->getMessage()]);

            throw new BusinessRuleException('خطا در اتصال به درگاه پرداخت', 'PAYMENT_GATEWAY_ERROR', 500);
        }

        return $this->ok(
            $data['method'] === 'wallet' ? 'پرداخت با موفقیت انجام شد.' : 'لینک پرداخت با موفقیت ایجاد شد.',
            $result
        );
    }

    /** PUT /api/orders/{id}/delivery */
    public function updateDelivery(Request $request, int $orderId): JsonResponse
    {
        $data = $request->validate([
            'receiver_type' => 'required|string|in:self,representative',
            'method' => 'required|string|in:loop_technician,user,courier',
            'receiver_name' => 'required_if:receiver_type,representative|nullable|string|max:191',
            'receiver_mobile' => ['required_if:receiver_type,representative', 'nullable', 'regex:/^09[0-9]{9}$/'],
            'receiver_national_id' => ['nullable', 'regex:/^[0-9]{10}$/'],
        ], [
            'receiver_type.required' => 'تحویل‌گیرنده را مشخص کنید.',
            'receiver_type.in' => 'نوع تحویل‌گیرنده نامعتبر است.',
            'method.required' => 'روش ارسال را مشخص کنید.',
            'method.in' => 'روش ارسال نامعتبر است.',
            'receiver_name.required_if' => 'نام تحویل‌گیرنده الزامی است.',
            'receiver_mobile.required_if' => 'شماره موبایل تحویل‌گیرنده الزامی است.',
            'receiver_mobile.regex' => 'شماره موبایل باید با ۰۹ شروع شود و ۱۱ رقم باشد.',
            'receiver_national_id.regex' => 'کد ملی باید ۱۰ رقم باشد.',
        ]);

        $order = $this->ownedOrder($request, $orderId);

        if (!$order->returnsGoodsToUser()) {
            throw new BusinessRuleException('این سفارش کالا یا محصولی برای تحویل ندارد.', 'DELIVERY_NOT_APPLICABLE');
        }

        if (in_array($order->delivery_status, ['shipped', 'delivered'], true)) {
            throw new BusinessRuleException('پس از ارسال، مشخصات تحویل قابل ویرایش نیست.', 'DELIVERY_LOCKED');
        }

        $isSelf = $data['receiver_type'] === 'self';
        $order->forceFill([
            'delivery_receiver_type' => $data['receiver_type'],
            'delivery_method' => $data['method'],
            'delivery_receiver_name' => $isSelf ? null : $data['receiver_name'],
            'delivery_receiver_mobile' => $isSelf ? null : $data['receiver_mobile'],
            'delivery_receiver_national_id' => $isSelf ? null : ($data['receiver_national_id'] ?? null),
            'delivery_status' => $order->delivery_status ?? 'pending',
        ])->save();

        return $this->ok('مشخصات تحویل ثبت شد.', $order->deliveryPayload());
    }

    /** GET /api/order/procurement/payment/callback/{stage} — بازگشت از درگاه زیبال */
    public function paymentCallback(Request $request, string $stage)
    {
        $trackId = $request->query('trackId');
        $transaction = $trackId ? UserTransaction::where('referenceId', $trackId)
            ->where('status', 0)
            ->where('type', ProcurementService::TRANSACTION_TYPE_GATEWAY)
            ->first() : null;

        if (!$transaction || !in_array($stage, ['prepayment', 'remaining'], true)) {
            return redirect()->route('redirect', ['status' => 'NOK'])->with('message', 'تراکنش یافت نشد.');
        }

        $redirect = fn (string $status) => redirect()->route('redirect', ['linkingUri' => $transaction->linking_url, 'status' => $status]);

        if ($request->query('success') != '1' && $request->query('success') !== 'true') {
            $transaction->forceFill(['status' => -200])->save();

            return $redirect('NOK')->with('message', 'تراکنش ناموفق بود.');
        }

        try {
            $receipt = Payment::via('zibal')->amount((int) $transaction->price)->transactionId($trackId)->verify();
        } catch (InvalidPaymentException $e) {
            Log::warning('procurement gateway verify failed', ['transaction_id' => $transaction->id, 'error' => $e->getMessage()]);
            $transaction->forceFill(['status' => -200])->save();

            return $redirect('NOK')->with('message', 'تراکنش ناموفق بود.');
        } catch (Throwable $e) {
            Log::error('procurement gateway verify error', ['transaction_id' => $transaction->id, 'error' => $e->getMessage()]);

            return $redirect('NOK')->with('message', 'خطا در پردازش پرداخت.');
        }

        try {
            $this->procurements->completeGatewayPayment($transaction, $stage, (string) $receipt->getReferenceId());
        } catch (BusinessRuleException $e) {
            // پول از درگاه کسر شده ولی این مرحله دیگر قابل پرداخت نیست (مثلاً هم‌زمان از کیف پول پرداخت شده)
            $this->procurements->refundToWallet($transaction, (string) $receipt->getReferenceId());
            Log::error('procurement gateway payment refunded to wallet', ['transaction_id' => $transaction->id, 'error' => $e->getMessage()]);

            return $redirect('NOK')->with('message', 'این مرحله قبلاً پرداخت شده بود؛ مبلغ به کیف پول شما برگشت.');
        }

        return $redirect('OK')->with('message', __('Payment successful.'));
    }

    // -----------------------------------------------------------------

    private function ownedOrder(Request $request, int $orderId): Order
    {
        // توکن تکنسین هم از این گروه مسیر عبور می‌کند؛ شناسه‌ی تکنسین نباید با user_id سفارش مقایسه شود
        if (!$request->user() instanceof User) {
            throw new BusinessRuleException('این بخش فقط برای حساب کاربری و سازمانی در دسترس است.', 'USER_ACCOUNT_ONLY', 403);
        }

        $order = Order::where('id', $orderId)->where('user_id', $request->user()->id)->first();

        if (!$order) {
            throw new BusinessRuleException('سفارش یافت نشد.', 'ORDER_NOT_FOUND', 404);
        }

        return $order;
    }

    private function ok(string $message, mixed $data): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data]);
    }
}
