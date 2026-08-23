<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderUploadRequest;
use App\Http\Requests\SetUserInPlaceDescriptionRequest;
use App\Http\Requests\SubmitOrderRequest;
use App\Http\Requests\CheckDiscountRequest;
use App\Http\Requests\StartOrderRequest;
use App\Http\Requests\EndOrderRequest;
use App\Http\Requests\CancelOrderRequest;
use App\Http\Requests\VerifyTechnicianRequest;
use App\Http\Requests\FetchOrderExtraServicesRequest;
use App\Http\Requests\SetUserFinalDescriptionRequest;
use App\Http\Requests\OrderGatewayPaymentRequest;
use App\Models\Order;
use App\Models\Technician;
use App\Models\UserTransaction;
use App\Repositories\WalletRepository;
use App\Repositories\TechnicianTransactionRepository;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $service,
        private WalletRepository $walletRepo,
        private TechnicianTransactionRepository $technicianTransactionRepo
    ) {
    }

    public function orderUpload(OrderUploadRequest $request): JsonResponse
    {
        $files = $request->file('file');
        $uploadedPaths = $this->service->uploadMultipleFiles($files);

        if (empty($uploadedPaths)) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در آپلود فایل‌ها',
                'error_code' => 'FILE_UPLOAD_FAILED'
            ], 400);
        }

        // اگر فقط یک فایل آپلود شده، مسیر آن را برمی‌گردانیم
        if (count($uploadedPaths) == 1) {
            return response()->json([
                'success' => true,
                'message' => __("File uploaded successfully"),
                'data' => $uploadedPaths[0]
            ]);
        }

        // اگر چند فایل آپلود شده، آرایه مسیرها را برمی‌گردانیم
        return response()->json([
            'success' => true,
            'message' => __("File uploaded successfully"),
            'data' => $uploadedPaths
        ]);
    }
    public function uploadMultiple(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['error' => 'Unauthorized!'], 401);
        }

        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No files uploaded'], 400);
        }

        $files = $request->file('file');

        // اگر فقط یک فایل ارسال شد، تبدیلش می‌کنیم به آرایه
        if (!is_array($files)) {
            $files = [$files];
        }

        $filePaths = [];

        foreach ($files as $file) {
            if ($file->isValid()) {
                $filePaths[] = $file->store('profile', 'public');
            }
        }

        return response()->json($filePaths);
    }

    public function submitOrder(SubmitOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است.',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }

        $data = $request->validated();
        $data['user'] = $user;

        $result = $this->service->submitOrder($data, $user->id);

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'INVALID_DISCOUNT_CODE' => 409,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'ORDER_SUBMISSION_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ], 201);
    }

    public function checkDiscount(CheckDiscountRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است.',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }

        $result = $this->service->checkDiscount(
            $request->discount_code,
            $request->category_id,
            $user->id
        );

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'DISCOUNT_NOT_FOUND' => 404,
                'DISCOUNT_INVALID' => 409,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'DISCOUNT_CHECK_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ]);
    }

    public function getUserOrders(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است.',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }

        // اگر فیلتری ارسال نشده، از متد قدیمی استفاده کن (Backward Compatible)
        if (!$request->hasAny(['from_date', 'to_date', 'status', 'per_page'])) {
            $result = $this->service->getUserOrders($user->id);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error_code' => 'FETCH_ORDERS_ERROR'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => __("Order list retrieved successfully."),
                'data' => $result['data']
            ]);
        }

        // استفاده از متد جدید با فیلترها
        $validated = $request->validate([
            'from_date' => 'nullable|date_format:Y-m-d',
            'to_date' => 'nullable|date_format:Y-m-d|after_or_equal:from_date',
            'status' => 'nullable|integer',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $result = $this->service->getUserOrdersWithFilters(
            $user->id,
            $validated['from_date'] ?? null,
            $validated['to_date'] ?? null,
            $validated['status'] ?? null,
            $validated['per_page'] ?? null
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => 'FETCH_ORDERS_ERROR'
            ], 400);
        }

        $response = [
            'success' => true,
            'message' => __("Order list retrieved successfully."),
            'data' => $result['data'],
            'filters' => $result['filters']
        ];

        // اضافه کردن pagination اگر وجود داشته باشد
        if (isset($result['pagination'])) {
            $response['pagination'] = $result['pagination'];
        }

        return response()->json($response);
    }

    public function getOrderDetail(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است.',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }

        $orderId = $request->input('orderId');

        if (!$orderId) {
            return response()->json([
                'success' => false,
                'message' => 'شناسه سفارش الزامی است.',
                'error_code' => 'ORDER_ID_REQUIRED'
            ], 400);
        }

        $result = $this->service->getOrderDetail($user->id, $orderId);

        if (!$result['success']) {
            $statusCode = ($result['error_code'] ?? '') == 'ORDER_NOT_FOUND' ? 404 : 400;

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'FETCH_ORDER_DETAIL_ERROR'
            ], $statusCode);
        }

        return response()->json($result['data'], 200);
    }

    public function startOrder(StartOrderRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است.',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }

        $result = $this->service->startOrder($user->id, $request->input('orderId'));

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'ORDER_NOT_FOUND' => 404,
                'TECHNICIAN_NOT_ARRIVED' => 409,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'START_ORDER_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message']
        ], 200);
    }

    public function endOrder(EndOrderRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است.',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }

        $result = $this->service->endOrder($user->id, $request->input('orderId'));

        if (!$result['success']) {
            $statusCode = ($result['error_code'] ?? '') == 'ORDER_NOT_FOUND' ? 404 : 400;

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'END_ORDER_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message']
        ], 200);
    }

    public function cancelOrder(CancelOrderRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است.',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }

        $result = $this->service->cancelOrder($user->id, $request->input('orderId'));

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'ORDER_NOT_FOUND' => 404,
                'FORBIDDEN' => 403,
                'TECHNICIAN_DISPATCHED' => 409,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'CANCEL_ORDER_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message']
        ], 200);
    }

    public function verifyTechnician(VerifyTechnicianRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است.',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }

        $result = $this->service->verifyTechnician(
            $user->id,
            $request->input('orderId'),
            $request->input('verification_status')
        );

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'ORDER_NOT_FOUND' => 404,
                'NO_TECHNICIAN_ASSIGNED' => 400,
                'ALREADY_VERIFIED' => 409,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'VERIFY_TECHNICIAN_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ], 200);
    }

    public function fetchOrderExtraServices(FetchOrderExtraServicesRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است.',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }

        $result = $this->service->getOrderExtraServices(
            $user->id,
            $request->input('order_id')
        );

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'FORBIDDEN' => 403,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'FETCH_EXTRA_SERVICES_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => 'هزینه‌های اضافی با موفقیت دریافت شد.',
            'data' => $result['data']
        ], 200);
    }

    /**
     * ثبت پذیرش اولیه کاربر برای سفارش
     * POST /api/orders/{orderId}/initial-accept
     */
    public function userInitialAccept(Request $request, int $orderId): JsonResponse
    {
        $user = $request->user();
        $result = $this->service->userInitialAccept($user->id, $orderId);

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'ORDER_NOT_FOUND' => 404,
                'ORDER_CANCELLED' => 400,
                'ALREADY_ACCEPTED' => 400,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'INITIAL_ACCEPT_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ], 200);
    }

    /**
     * ثبت تصمیم نهایی کاربر درباره سفارش (تایید یا رد)
     * POST /api/orders/{orderId}/decision
     */
    public function userOrderDecision(\App\Http\Requests\UserOrderDecisionRequest $request, string $orderId): JsonResponse
    {
        $user = $request->user();
        $dto = \App\DTOs\UserOrderDecisionDTO::fromArray($request->validated());

        $result = $this->service->userOrderDecision($user->id, (int) $orderId, $dto);

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'ORDER_NOT_FOUND' => 404,
                'UNAUTHORIZED' => 403,
                'ALREADY_DECIDED' => 400,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'DECISION_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ], 200);
    }

    /**
     * ثبت توضیحات پیگیری بازگشت محصول توسط کاربر
     * POST /api/orders/{orderId}/return-followup
     */
    public function userReturnFollowup(\App\Http\Requests\UserReturnFollowupRequest $request, string $orderId): JsonResponse
    {
        $user = $request->user();
        $description = $request->validated()['description'];

        $result = $this->service->setReturnFollowupDescription($user->id, (int) $orderId, $description);

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'ORDER_NOT_FOUND' => 404,
                'UNAUTHORIZED' => 403,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'FOLLOWUP_ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ], 200);
    }

    /**
     * ثبت توضیحات نهایی کاربر
     * POST /api/orders/{orderId}/final-description
     */
    public function setUserFinalDescription(int $orderId, SetUserFinalDescriptionRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است.',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }

        $result = $this->service->setUserFinalDescription(
            $user->id,
            $orderId,
            $request->input('description')
        );

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'ORDER_NOT_FOUND' => 404,
                'UNAUTHORIZED' => 403,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ], 200);
    }
    public function setUserInPlaceDescription(int $orderId, SetUserInPlaceDescriptionRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است.',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }

        $result = $this->service->setUserInPlaceDescription(
            $user->id,
            $orderId,
            $request->input('user_in_place_description')
        );

        if (!$result['success']) {
            $statusCode = match ($result['error_code'] ?? '') {
                'ORDER_NOT_FOUND' => 404,
                'UNAUTHORIZED' => 403,
                default => 400
            };

            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? 'ERROR'
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data']
        ], 200);
    }

    /**
     * پرداخت سفارش از طریق درگاه زرین‌پال
     */
    public function gatewayPayment(OrderGatewayPaymentRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $orderId = $validated['order_id'];
        $linkingUrl = $validated['linking_url'] ?? null;

        // بررسی وجود سفارش و تعلق آن به کاربر
        $order = Order::where(['user_id' => $user->id, 'id' => $orderId])->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'سفارش یافت نشد یا متعلق به شما نیست.',
                'error_code' => 'ORDER_NOT_FOUND'
            ], 404);
        }

        // بارگذاری discountUse برای محاسبه تخفیف
        $order->load('discountUse.discount_code');

        $pay_type = 'order';

        // بررسی وضعیت پرداخت
        if ($order->payment_status == 1) {
            return response()->json([
                'success' => false,
                'message' => 'این سفارش قبلاً پرداخت شده است.',
                'error_code' => 'ALREADY_PAID'
            ], 409);
        }

        $amount = $order->payment_price(true);

        if ((($order?->status == 4 && $order?->technician_cancel_reason != 'اعلام حضور / لغو از سوی تکنسین') || ($order?->status == 3 && $order?->arrived_at))) {
            $pay_type = 'order';
            $amount = 200000;
        } elseif (!is_null($order->prepayment) && $order->prepayment != 0 && $order->prepayment_payment_status == 0 && !is_null($order->loop_cost_estimate) && $order->loop_cost_estimate > 0) {
            $pay_type = 'prepay';
        }

        // بررسی حداقل مبلغ
        if ($amount < 1000) {
            return response()->json([
                'success' => false,
                'message' => 'مبلغ سفارش کمتر از میزان مجاز می‌باشد (حداقل ۱۰۰۰ تومان).',
                'error_code' => 'AMOUNT_TOO_LOW'
            ], 400);
        }

        // بررسی حداکثر مبلغ
        if ($amount > 99000000) {
            return response()->json([
                'success' => false,
                'message' => 'مبلغ سفارش بیش از حد مجاز است (حداکثر ۹۹,۰۰۰,۰۰۰ تومان). لطفاً از کیف‌پول استفاده کنید.',
                'error_code' => 'AMOUNT_TOO_HIGH'
            ], 400);
        }

        try {
            // ثبت تراکنش pending
            $transaction = $this->walletRepo->createTransaction([
                'user_id' => $user->id,
                'price' => $amount,
                'type' => 2, // پرداخت سفارش از طریق درگاه
                'status' => 0, // در انتظار
                'description' => $pay_type === 'prepay'
                    ? 'پیش پرداخت سفارش شماره ' . $orderId . ' از طریق درگاه'
                    : 'پرداخت سفارش شماره ' . $orderId . ' از طریق درگاه',
                'linking_url' => $linkingUrl,
                'order_id' => $orderId
            ]);

            // ساخت invoice برای زیبال
            $invoice = new Invoice();
            $invoice->amount((int) $amount);
            $invoice->detail([
                'transactionId' => $transaction->id,
                'orderId' => $orderId,
                'userId' => $user->id,
                'amount' => $amount,
            ]);

            $callbackUrl = route('order.payment.callback');

            // ارسال به زیبال
            $payment = Payment::via('zibal')->callbackUrl($callbackUrl)->purchase(
                $invoice,
                function ($driver, $trackId) use ($transaction) {
                    // ذخیره trackId زیبال در referenceId
                    $this->walletRepo->updateTransactionStatus(
                        $transaction->id,
                        0,
                        (string) $trackId
                    );
                }
            );

            $paymentUrl = $payment->pay()->getAction();

            Log::info('لینک پرداخت سفارش ایجاد شد', [
                'user_id' => $user->id,
                'order_id' => $orderId,
                'amount' => $amount,
                'transaction_id' => $transaction->id,
                'payment_url' => $paymentUrl,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'لینک پرداخت با موفقیت ایجاد شد.',
                'data' => [
                    'payment_url' => $paymentUrl,
                    'transaction_id' => $transaction->id,
                    'order_id' => $orderId,
                    'amount' => $amount
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('خطا در ایجاد لینک پرداخت سفارش زیبال', [
                'user_id' => $user->id,
                'order_id' => $orderId,
                'amount' => $amount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در اتصال به درگاه پرداخت',
                'error' => $e->getMessage(),
                'error_code' => 'PAYMENT_GATEWAY_ERROR'
            ], 500);
        }
    }


    /**
     * بازگشت از درگاه پرداخت سفارش (callback)
     */
    public function paymentCallback(Request $request)
    {
        try {
            $trackId = $request->query('trackId');
            $success = $request->query('success');

            if (!$trackId) {
                Log::error('trackId یافت نشد در callback سفارش');
                return redirect()->route('redirect', ['status' => 'NOK'])->with('message', 'شماره پیگیری یافت نشد.');
            }

            // پیدا کردن تراکنش بر اساس trackId زیبال
            $transaction = UserTransaction::where('referenceId', $trackId)
                ->where('status', 0)
                ->where('type', 2)
                ->first();

            if (!$transaction) {
                Log::error('تراکنش سفارش با trackId یافت نشد', [
                    'trackId' => $trackId
                ]);

                return redirect()->route('redirect', ['status' => 'NOK'])->with('message', 'تراکنش یافت نشد.');
            }

            $orderId = $transaction->order_id;

            if (!$orderId) {
                Log::error('order_id برای تراکنش یافت نشد', [
                    'transaction_id' => $transaction->id,
                    'trackId' => $trackId
                ]);

                return redirect()->route('redirect', ['status' => 'NOK'])->with('message', 'شناسه سفارش یافت نشد.');
            }

            $order = Order::find($orderId);

            if (!$order) {
                Log::error('سفارش یافت نشد', [
                    'order_id' => $orderId,
                    'transaction_id' => $transaction->id,
                    'trackId' => $trackId
                ]);

                return redirect()->route('redirect', ['status' => 'NOK'])->with('message', 'سفارش یافت نشد.');
            }

            // پرداخت ناموفق از سمت درگاه
            if ($success != '1' && $success !== 'true') {
                $this->walletRepo->updateTransactionStatus($transaction->id, -200, $trackId);

                Log::warning('پرداخت سفارش ناموفق', [
                    'order_id' => $orderId,
                    'transaction_id' => $transaction->id,
                    'trackId' => $trackId,
                    'success' => $success
                ]);

                return redirect()->route('redirect', [
                    'linkingUri' => $transaction->linking_url,
                    'status' => 'NOK'
                ])->with('message', 'تراکنش ناموفق بود.');
            }

            // Verify با زیبال
            $receipt = Payment::via('zibal')
                ->amount((int) $transaction->price)
                ->transactionId($trackId)
                ->verify();

            $referenceId = $receipt->getReferenceId();

            Log::info('پرداخت سفارش Verify شد', [
                'order_id' => $orderId,
                'transaction_id' => $transaction->id,
                'trackId' => $trackId,
                'reference_id' => $referenceId,
                'amount' => $transaction->price
            ]);

            DB::beginTransaction();

            try {
                $pay_type = 'order';

                // Load discountUse برای محاسبه تخفیف
                $order->load('discountUse.discount_code.club');

                // محاسبه مبلغ قبل از تخفیف
                if (
                    !is_null($order->loop_cost_estimate) && $order->loop_cost_estimate > 0 &&
                    !is_null($order->prepayment) && $order->prepayment > 0 &&
                    $order->prepayment_payment_status == 0
                ) {
                    $totalBeforeDiscount = $order->loop_cost_estimate * $order->prepayment / 100;
                    $pay_type = 'prepay';
                } else {
                    $basePrice = $order->technician_price ?? $order->pakar_price ?? 0;
                    $totalBeforeDiscount = $basePrice + ($order->extra_price ?? 0);
                }

                $discountAmount = 0;

                if ($order->discountUse && $order->discountUse->discount_code && $pay_type == 'order') {
                    $discountCode = $order->discountUse->discount_code;
                    $discountAmount = ($totalBeforeDiscount * $discountCode->discount_percent) / 100;

                    // اعمال سقف تخفیف
                    if ($discountCode->club && $discountCode->club->max_price && $discountAmount > $discountCode->club->max_price) {
                        $discountAmount = $discountCode->club->max_price;
                    }
                }

                // به‌روزرسانی وضعیت پرداخت سفارش
                if ($pay_type == 'prepay') {
                    $order->prepayment_payment_status = 1;
                    $order->user_accept_date = Carbon::now();
                } else {
                    $order->payment_status = 1;
                }

                if ($discountAmount > 0 && $pay_type == 'order') {
                    $order->discount_price = $discountAmount;
                }

                $order->save();

                // به‌روزرسانی همان تراکنش به موفق
                $this->walletRepo->updateTransactionStatus(
                    $transaction->id,
                    100,
                    $referenceId
                );

                // واریز سهم تکنسین در زمان پایان سفارش
                $this->service->processTechnicianPayment($order);

                DB::commit();

                return redirect()->route('redirect', [
                    'linkingUri' => $transaction->linking_url,
                    'status' => 'OK'
                ])->with([
                            'message' => __('Payment successful.'),
                            'order_id' => $orderId,
                            'reference_id' => $referenceId
                        ]);

            } catch (\Exception $e) {
                DB::rollBack();

                Log::error('خطا در پردازش موفق پرداخت سفارش', [
                    'order_id' => $orderId,
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return redirect()->route('redirect', ['status' => 'NOK'])->with('message', 'خطا در ثبت پرداخت.');
            }

        } catch (InvalidPaymentException $e) {
            Log::error('پرداخت سفارش ناموفق در زیبال', [
                'error' => $e->getMessage(),
                'trackId' => $request->query('trackId')
            ]);

            return redirect()->route('redirect', ['status' => 'NOK'])->with('message', 'تراکنش ناموفق بود.');

        } catch (\Exception $e) {
            Log::error('خطا در callback پرداخت سفارش', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'trackId' => $request->query('trackId')
            ]);

            return redirect()->route('redirect', ['status' => 'NOK'])->with('message', 'خطا در پردازش پرداخت.');
        }
    }


    /**
     * دریافت لیست خلاصه سفارشات کاربر
     * فقط شامل: created_at, finished_at, referral_code تکنسین, مبلغ پرداخت شده نهایی, نام محصول
     */
    public function getUserOrdersSummary(): JsonResponse
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربر احراز هویت نشده است.',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }

        $result = $this->service->getUserOrdersSummary($user->id);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => 'FETCH_ORDERS_SUMMARY_ERROR'
            ], 400);
        }

        return response()->json($result);
    }
}

