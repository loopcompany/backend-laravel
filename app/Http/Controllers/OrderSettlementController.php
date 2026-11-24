<?php

namespace App\Http\Controllers;

use App\Models\{Order, OrderReceipt, Technician};
use App\Services\OrderSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrderSettlementController extends Controller
{
    private function technician(Request $request, Order $order): Technician
    {
        $actor = $request->user();
        abort_unless($actor instanceof Technician && $actor->has_access && $actor->isApproved() && (int) $order->technician_id === (int) $actor->id, 403);
        return $actor;
    }

    public function show(Request $request, Order $order, OrderSettlementService $service)
    {
        $this->technician($request, $order);
        return response()->json(['data' => $service->summary($order)]);
    }

    public function store(Request $request, Order $order, OrderSettlementService $service)
    {
        $actor = $this->technician($request, $order);
        $data = $request->validate([
            'request_uuid' => 'required|uuid', 'method' => 'required|in:cash,card_to_card,barter',
            'amount' => 'required|integer|min:1|max:9007199254740990', 'collection_target' => 'required|in:company,technician',
            'item_description' => 'nullable|required_if:method,barter|string|max:500', 'reference' => 'nullable|string|max:191',
            'receipt' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);
        $path = null;
        try {
            if ($file = $request->file('receipt')) {
                $data['receipt_sha256'] = hash_file('sha256', $file->getRealPath());
                $path = $file->store('order-receipts', 'local');
            }
            $receipt = $service->record($order, $actor, $data, $path);
            if ($path && $receipt->receipt_path !== $path) Storage::disk('local')->delete($path);
            return response()->json(['data' => $service->summary($order->fresh()), 'receipt_id' => $receipt->id], 201);
        } catch (\Throwable $error) {
            if ($path) Storage::disk('local')->delete($path);
            throw $error;
        }
    }

    public function adminImage(OrderReceipt $receipt)
    {
        $admin = auth('admin')->user();
        abort_unless($admin?->is_active && ($admin->can('view-technician-custody') || $admin->can('review-order-settlements') || $admin->isSuperAdmin()), 403);
        abort_unless($receipt->receipt_path && Storage::disk('local')->exists($receipt->receipt_path), 404);
        return Storage::disk('local')->response($receipt->receipt_path, null, ['Cache-Control' => 'no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
