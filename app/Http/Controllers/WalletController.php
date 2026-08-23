<?php

namespace App\Http\Controllers;

use App\DTOs\WalletPaymentDTO;
use App\Http\Requests\GetTransactionsRequest;
use App\Http\Requests\IncreaseWalletRequest;
use App\Http\Requests\WalletPaymentRequest;
use App\Models\Order;
use App\Repositories\WalletRepository;
use App\Services\OrderPaymentService;
use App\Services\OrderService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService,
        protected OrderPaymentService $orderPaymentService,
        protected WalletRepository $walletRepo,
        private OrderService $service,
    ) {
    }

    /**
     * شارژ کیف پول - هدایت به درگاه پرداخت
     */
    public function increaseWallet(IncreaseWalletRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();
        $amount = $validated['amount'];
        $linkingUrl = $validated['linking_url'] ?? null;

        $result = $this->walletService->initiateCharge($user->id, $amount, $linkingUrl);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        try {
            $invoice = new Invoice();
            $invoice->amount((int) $amount);

            $callbackUrl = route('wallet.callback');

            // استفاده از درگاه زیبال
            $payment = Payment::via('zibal')->callbackUrl($callbackUrl)->purchase(
                $invoice,
                function ($driver, $trackId) use ($result) {
                    // ذخیره trackId زیبال در ستون referenceId تراکنشی که در سرویس ساخته شده بود
                    $this->walletRepo->updateTransactionStatus($result['data']['transaction_id'], 0, $trackId);
                }
            );

            return response()->json([
                'success' => true,
                'message' => 'لینک پرداخت زیبال ایجاد شد.',
                'data' => [
                    'payment_url' => $payment->pay()->getAction(),
                    'transaction_id' => $result['data']['transaction_id'],
                    'amount' => $amount
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Zibal Purchase Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'خطا در اتصال به درگاه'], 500);
        }
    }


    /**
     * بازگشت از درگاه پرداخت (callback)    /**
     * بازگشت از درگاه پرداخت (callback)
     */
    public function walletCallback(Request $request)
    {
        try {
            $trackId = $request->query('trackId');
            $success = $request->query('success');

            if (!$trackId) {
                return redirect()->route('redirect', ['status' => 'NOK'])->with('message', 'کد پیگیری یافت نشد.');
            }

            // پیدا کردن تراکنش دقیق بر اساس trackId (به جای آخرین تراکنش)
            $transaction = $this->walletRepo->findPendingByReferenceId($trackId);

            if (!$transaction) {
                Log::error('Transaction not found for trackId: ' . $trackId);
                return redirect()->route('redirect', ['status' => 'NOK'])->with('message', 'تراکنش معتبر یافت نشد.');
            }

            // اگر پرداخت ناموفق بود
            if ($success != '1') {
                $this->walletService->recordFailedCharge($transaction->user_id, (float) $transaction->price, $trackId, $transaction->linking_url);
                return redirect()->route('redirect', ['linkingUri' => $transaction->linking_url, 'status' => 'NOK'])->with('message', 'پرداخت ناموفق بود.');
            }

            // عملیات تایید (Verify)
            $receipt = Payment::via('zibal')
                ->amount((int) $transaction->price)
                ->transactionId($trackId)
                ->verify();

            // شماره مرجع نهایی زیبال (RefNumber)
            $referenceId = $receipt->getReferenceId();

            // تکمیل شارژ (متد سرویس را در مرحله بعد اصلاح می‌کنیم)
            $result = $this->walletService->completeSuccessfulChargeWithId(
                $transaction->id,
                $referenceId
            );

            if ($result['success']) {
                return redirect()->route('redirect', ['linkingUri' => $transaction->linking_url, 'status' => 'OK'])->with([
                    'message' => $result['message'],
                    'balance' => $result['data']['wallet_balance'],
                ]);
            }

            return redirect()->route('redirect', ['linkingUri' => $transaction->linking_url, 'status' => 'NOK'])->with('message', $result['message']);

        } catch (InvalidPaymentException $e) {
            Log::error('Zibal Verify Exception: ' . $e->getMessage());
            return redirect()->route('redirect', ['status' => 'NOK'])->with('message', 'خطا در تایید پرداخت.');
        } catch (\Exception $e) {
            Log::error('Zibal Callback Error: ' . $e->getMessage());
            return redirect()->route('redirect', ['status' => 'NOK'])->with('message', 'خطای سیستمی.');
        }
    }

    /**
     * دریافت موجودی کیف پول
     */
    public function getBalance(Request $request): JsonResponse
    {
        $user = $request->user();
        $result = $this->walletService->getBalance($user->id);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data']
        ]);
    }

    /**
     * دریافت تاریخچه تراکنش‌ها
     */
    public function getTransactions(GetTransactionsRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $perPage = $validated['per_page'] ?? 20;
        $fromDate = $validated['from_date'] ?? null;
        $toDate = $validated['to_date'] ?? null;

        $result = $this->walletService->getTransactionHistory($user->id, $perPage, $fromDate, $toDate);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $result['data']
        ]);
    }

    /**
     * پرداخت سفارش از طریق کیف پول
     */
    public function payOrder(WalletPaymentRequest $request): JsonResponse
    {
        $user = $request->user();
        $validatedData = $request->validated();

        // ایجاد DTO
        $dto = WalletPaymentDTO::fromRequest($validatedData, $user->id);

        // پرداخت سفارش
        $result = $this->orderPaymentService->payOrderWithWallet($dto);

        if (!$result['success']) {
            $statusCode = match ($result['error_code']) {
                'ORDER_NOT_FOUND' => 404,
                'ALREADY_PAID' => 409,
                'INSUFFICIENT_BALANCE' => 402,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code']
            ], $statusCode);
        }
        $order = Order::findOrFail($request->orderId);
        $technicianPayResult = $this->service->processTechnicianPayment($order);

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ], 201);
    }
}

