<?php
namespace App\Http\Controllers;

use App\Models\{Order, Technician, User, UserAddress};
use App\Services\{OrderSettlementService, RegistrationCodeService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InPersonOrderController extends Controller
{
    public function store(Request $request, OrderSettlementService $settlements)
    {
        $tech = $request->user();
        abort_unless($tech instanceof Technician && $tech->isApproved() && $tech->has_access
            && (str_contains($tech->technician_type ?? '', 'داخلی') || str_contains(mb_strtolower($tech->technician_type ?? ''), 'internal')), 403);
        $data = $request->validate([
            'request_uuid' => 'required|uuid', 'name' => 'required|string|max:100', 'last_name' => 'nullable|string|max:100',
            'phone' => ['required', 'regex:/^09[0-9]{9}$/D'], 'city_code' => ['required', 'regex:/^[0-9]{2}$/D'], 'region_code' => ['required', 'regex:/^[0-9]{2}$/D'],
            'category_id' => 'required|integer|exists:categories,id', 'items' => 'required|array|min:1|max:50',
            'items.*.kind' => 'required|in:service,goods', 'items.*.description' => 'required|string|max:500',
            'items.*.unit_price' => 'required|integer|min:1|max:1000000000000', 'items.*.quantity' => 'required|integer|min:1|max:1000',
            'receipts' => 'nullable|array|max:20', 'receipts.*.request_uuid' => 'required|uuid',
            'receipts.*.method' => 'required|in:cash,card_to_card,barter', 'receipts.*.amount' => 'required|integer|min:1|max:1000000000000',
            'receipts.*.collection_target' => 'required|in:company,technician', 'receipts.*.item_description' => 'nullable|string|max:500',
            'receipts.*.reference' => 'nullable|string|max:191',
        ]);
        $fingerprint = hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE));
        $order = DB::transaction(function () use ($tech, $data, $fingerprint, $settlements) {
            // Serializes repeated requests by this employee before the unique request UUID is inserted.
            Technician::lockForUpdate()->findOrFail($tech->id);
            $previous = Order::where('in_person_request_uuid', $data['request_uuid'])->first();
            if ($previous) {
                abort_unless((int) $previous->registered_by_technician_id === (int) $tech->id, 403);
                if (! hash_equals($previous->in_person_payload_hash, $fingerprint)) throw ValidationException::withMessages(['request_uuid' => 'این شناسه برای سفارش دیگری استفاده شده است.']);
                return $previous;
            }
            $user = User::firstOrCreate(['phone' => $data['phone']], [
                'name' => $data['name'], 'last_name' => $data['last_name'] ?? null, 'account_type' => 'individual',
                'registration_method' => 4, 'by_who' => 'admin', 'password' => Hash::make(Str::random(32)),
            ]);
            if ($user->wasRecentlyCreated) {
                app(RegistrationCodeService::class)->issue($user, ['city_code' => $data['city_code'], 'region_code' => $data['region_code'], 'method' => 4]);
                DB::afterCommit(fn () => \App\Jobs\SendProfileCompletionCode::dispatch($user->fresh()));
            }
            // A walk-in order has a service location, not an invented home address.
            $address = UserAddress::firstOrCreate(['user_id' => $user->id, 'title' => 'مراجعه حضوری به شرکت لوپ', 'address' => 'محل ارائهٔ خدمات: داخل شرکت لوپ'],
                ['fname' => $user->name, 'lname' => $user->last_name, 'mobile' => $user->phone]);
            $service = 0; $goods = 0;
            foreach ($data['items'] as $item) {
                $amount = (int) $item['unit_price'] * (int) $item['quantity'];
                if ($item['kind'] === 'service') $service += $amount; else $goods += $amount;
            }
            if ($service + $goods > 9007199254740990) throw ValidationException::withMessages(['items' => 'مبلغ کل خارج از محدوده است.']);
            $order = new Order;
            $order->forceFill(['user_id' => $user->id, 'technician_id' => $tech->id, 'category_id' => $data['category_id'],
                'user_address_id' => $address->id, 'status' => 0, 'technician_price' => $service, 'extra_price' => $goods,
                'payment_channel' => 'in_person', 'registered_by_technician_id' => $tech->id,
                'in_person_request_uuid' => $data['request_uuid'], 'in_person_payload_hash' => $fingerprint, 'in_person_items' => $data['items'],
                'des' => collect($data['items'])->map(fn ($item) => $item['description'].' × '.$item['quantity'])->implode("\n"),
            ])->save();
            \App\Models\OrderStageLog::create(['order_id' => $order->id, 'action' => 'walk_in_registered', 'actor_type' => 'technician', 'actor_id' => $tech->id, 'actor_name' => $tech->name, 'note' => 'ثبت مراجعه‌کنندهٔ داخل شرکت']);
            foreach ($data['receipts'] ?? [] as $receipt) $settlements->record($order, $tech, $receipt);
            return $order->fresh();
        }, 3);
        return response()->json(['order_id' => $order->id, 'customer_id' => $order->user_id, 'customer_code' => $order->user->code, 'settlement' => $settlements->summary($order)], 201);
    }
}
