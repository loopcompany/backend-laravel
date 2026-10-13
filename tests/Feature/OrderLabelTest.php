<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\MakesPeople;
use Tests\TestCase;

class OrderLabelTest extends TestCase
{
    use MakesPeople, RefreshDatabase;

    public function test_only_an_admin_with_order_view_permission_can_print_a_65_by_30_mm_label(): void
    {
        $user = $this->makeUser(['code' => '2118400528']);
        $order = Order::withoutEvents(fn () => $this->makeOrder($user, ['status' => 2]));
        $url = route('admin.orders.label', ['order' => $order->id]);

        $this->get($url)->assertRedirect();

        $admin = Admin::create([
            'name' => 'Label operator',
            'email' => 'labels@test.local',
            'password' => 'password',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')->get($url)->assertForbidden();

        $admin->givePermissionTo(Permission::findOrCreate('view-orders', 'admin'));
        $response = $this->get($url);

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertMatchesRegularExpression('/\/MediaBox\s*\[[^\]]*184\.25[^\]]*85\.03[^\]]*\]/', $response->getContent());
    }
}
