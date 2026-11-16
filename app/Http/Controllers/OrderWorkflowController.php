<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderQuote;
use App\Models\OrderWorkflow;
use App\Services\OrderWorkflowException;
use App\Services\OrderWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * API فرآیند سفارش برای اپ کاربر و اپ تکنسین (تأیید قیمت، پاسخ به مأموریت، نتیجه‌ی مراجعه، قیمت‌گذاری مجدد).
 */
class OrderWorkflowController extends Controller
{
    public function __construct(private OrderWorkflowService $workflow)
    {
    }

    public function pendingEstimate(Request $request): JsonResponse
    {
        $quote = OrderQuote::query()
            ->where('kind', OrderQuote::KIND_INITIAL)
            ->awaitingDecision()
            ->whereHas('order', fn ($query) => $query->where('user_id', $request->user()->id)
                ->whereIn('status', [0, 1]))
            ->latest('sent_at')
            ->orderByDesc('id')
            ->first(['id', 'order_id']);

        return response()->json(['success' => true, 'data' => $quote ? [
            'quote_id' => $quote->id,
            'order_id' => $quote->order_id,
        ] : null]);
    }

    /** وضعیت فرآیند و نسخه‌های قیمت سفارش کاربر. */
    public function userShow(Request $request, int $orderId): JsonResponse
    {
        $order = Order::where('user_id', $request->user()->id)->findOrFail($orderId);

        return response()->json(['success' => true, 'data' => $this->workflow->customerPayload($order)]);
    }

    /** ۴-۶ / ۷-۴ — تأیید یا رد قیمت ارسال‌شده توسط کاربر (در صورت رد، علت الزامی است). */
    public function userQuoteDecision(Request $request, int $orderId): JsonResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['accept', 'reject'])],
            'note' => ['nullable', 'string', 'max:1000', 'required_if:decision,reject'],
            'quote_id' => ['sometimes', 'required', 'integer'],
        ]);

        $order = Order::where('user_id', $request->user()->id)->findOrFail($orderId);
        $quote = $this->workflow->openQuote($order);

        if (!$quote || $quote->status !== OrderQuote::STATUS_SENT || (isset($data['quote_id']) && (int) $data['quote_id'] !== $quote->id)) {
            return $this->error('قیمتی در انتظار تأیید شما نیست.', 'QUOTE_NOT_PENDING', 409);
        }

        return $this->run(function () use ($quote, $data, $order) {
            $this->workflow->decideQuote($quote, $data['decision'] === 'accept', $data['note'] ?? null);

            return $this->workflow->customerPayload($order->fresh());
        }, $data['decision'] === 'accept' ? 'قیمت تأیید شد.' : 'رد قیمت ثبت شد.');
    }

    public function userVisitDecision(Request $request, int $orderId): JsonResponse
    {
        $data=$request->validate(['decision'=>['required',Rule::in(['accept','recoordinate','cancel'])],
            'note'=>['nullable','string','max:1000','required_unless:decision,accept']]);
        $order=Order::where('user_id',$request->user()->id)->findOrFail($orderId);
        return $this->run(function () use ($order,$data) {
            $this->workflow->decideCustomerVisit($order,$data['decision'],$data['note']??null);
            return $this->workflow->customerPayload($order->fresh());
        },'تصمیم مراجعه ثبت شد.');
    }

    /** مأموریت ارجاع‌شده برای تکنسین اجباری است. */
    public function technicianAssignment(Request $request, int $orderId): JsonResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['accept', 'reject'])],
            'reason' => ['nullable', 'string', 'max:1000', 'required_if:decision,reject'],
        ]);

        $order = $this->technicianOrder($request, $orderId);

        return $this->run(function () use ($order, $data) {
            $this->workflow->technicianResponse($order, $data['decision'] === 'accept', $data['reason'] ?? null);

            return $this->workflow->technicianPayload($order->fresh());
        }, $data['decision'] === 'accept' ? 'مأموریت تأیید شد.' : 'عدم امکان انجام مأموریت ثبت شد.');
    }

    /** ۶-۴ / ۶-۵ — ثبت نتیجه‌ی بررسی اولیه در محل. */
    public function technicianVisitOutcome(Request $request, int $orderId): JsonResponse
    {
        $data = $request->validate([
            'outcome' => ['required', Rule::in(array_keys(OrderWorkflow::VISIT_OUTCOMES))],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $order = $this->technicianOrder($request, $orderId);

        return $this->run(function () use ($order, $data) {
            $this->workflow->recordVisitOutcome($order, $data['outcome'], $data['note'] ?? null);

            return $this->workflow->technicianPayload($order->fresh());
        }, 'نتیجه‌ی بررسی ثبت شد.');
    }

    /** ۷-۱ / ۷-۳ — پیشنهاد قیمت جدید تفکیک‌شده؛ پس از تأیید پنل برای کاربر ارسال می‌شود. */
    public function technicianRequote(Request $request, int $orderId): JsonResponse
    {
        $data = $request->validate([
            'service_fee' => ['required', 'integer', 'min:0'],
            'parts_fee' => ['nullable', 'integer', 'min:0'],
            'extra_fee' => ['nullable', 'integer', 'min:0'],
            'estimated_duration' => ['required', 'string', 'max:191'],
            'change_reason' => ['required', 'string', 'max:2000'],
            'parts_description' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $order = $this->technicianOrder($request, $orderId);

        try {
            $quote = $this->workflow->createQuote($order, OrderQuote::KIND_REVISED, $data);
        } catch (OrderWorkflowException $e) {
            return $this->error($e->getMessage(), $e->errorCode, 422);
        }

        return response()->json([
            'success' => true,
            'message' => $quote->status === OrderQuote::STATUS_SENT
                ? 'قیمت جدید در محدوده‌ی اختیار شما بود و برای تأیید کاربر ارسال شد.'
                : 'پیشنهاد قیمت جدید برای بررسی پنل ثبت شد.',
            'data' => $quote->toApiArray(),
        ]);
    }

    private function technicianOrder(Request $request, int $orderId): Order
    {
        return Order::where('technician_id', $request->user()->id)->findOrFail($orderId);
    }

    private function run(callable $callback, string $message): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'message' => $message, 'data' => $callback()]);
        } catch (OrderWorkflowException $e) {
            return $this->error($e->getMessage(), $e->errorCode, 422);
        }
    }

    private function error(string $message, string $code, int $status): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message, 'error_code' => $code], $status);
    }
}
