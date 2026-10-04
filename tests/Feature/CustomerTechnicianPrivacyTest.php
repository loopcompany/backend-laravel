<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\TechnicianReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesPeople;
use Tests\TestCase;

/**
 * اطلاعات تکنسین که به مشتری برمی‌گردد (بند ۶ نیازمندی‌ها) + نشت‌های مرتبط.
 */
class CustomerTechnicianPrivacyTest extends TestCase
{
    use RefreshDatabase, MakesPeople;

    private const SENSITIVE = ['melicode', 'bank_shaba_number', 'bank_card_number', 'home_address', 'wallet', 'commission', 'password'];

    private function sensitiveTechnician()
    {
        return $this->makeTechnician([
            'phone' => '09121112233',
            'melicode' => '0012345678',
            'bank_shaba_number' => 'IR000000000000000000000000',
            'bank_card_number' => '6037990000000000',
            'technician_type' => 'تکنسین جامع میدانی',
            'profile_photo_path' => 'technicians/p.jpg',
        ]);
    }

    public function test_order_list_hides_phone_after_order_ends_and_never_leaks_private_fields(): void
    {
        $user = $this->makeUser();
        $tech = $this->sensitiveTechnician();
        $active = $this->makeOrder($user, ['technician_id' => $tech->id, 'status' => 1]);
        $done = $this->makeOrder($user, ['technician_id' => $tech->id, 'status' => 2]);
        $canceled = $this->makeOrder($user, ['technician_id' => $tech->id, 'status' => 3]);
        $canceledByTech = $this->makeOrder($user, ['technician_id' => $tech->id, 'status' => 4]);
        Sanctum::actingAs($user);

        $orders = collect($this->getJson('/api/orders')->assertOk()->json('data'))->keyBy('id');

        $this->assertSame('09121112233', $orders[$active->id]['technician']['phone']);
        foreach ([$done, $canceled, $canceledByTech] as $order) {
            $this->assertArrayNotHasKey('phone', $orders[$order->id]['technician']);
        }

        $card = $orders[$done->id]['technician'];
        foreach (['name', 'technician_type', 'referral_code', 'created_at', 'profile_photo_path', 'average_rating', 'completed_orders_count'] as $key) {
            $this->assertArrayHasKey($key, $card);
        }
        foreach ($orders as $order) {
            foreach (self::SENSITIVE as $key) {
                $this->assertArrayNotHasKey($key, $order['technician'], $key);
            }
        }

        // مسیر فیلتردار (صفحه‌بندی) هم همین رفتار را دارد
        $paged = collect($this->getJson('/api/orders?per_page=10')->assertOk()->json('data'))->keyBy('id');
        $this->assertArrayHasKey('phone', $paged[$active->id]['technician']);
        $this->assertArrayNotHasKey('phone', $paged[$done->id]['technician']);
        $this->assertArrayNotHasKey('melicode', $paged[$done->id]['technician']);
    }

    public function test_order_detail_hides_phone_for_finished_order(): void
    {
        $user = $this->makeUser();
        $tech = $this->sensitiveTechnician();
        $active = $this->makeOrder($user, ['technician_id' => $tech->id, 'status' => 0]);
        $done = $this->makeOrder($user, ['technician_id' => $tech->id, 'status' => 2]);
        Sanctum::actingAs($user);

        $activeTech = $this->postJson('/api/orders/detail', ['orderId' => $active->id])->assertOk()->json('technician');
        $this->assertSame('09121112233', $activeTech['phone']);
        $this->assertArrayNotHasKey('bank_shaba_number', $activeTech);

        $doneTech = $this->postJson('/api/orders/detail', ['orderId' => $done->id])->assertOk()->json('technician');
        $this->assertArrayNotHasKey('phone', $doneTech);
        $this->assertArrayNotHasKey('melicode', $doneTech);
        $this->assertSame($tech->referral_code, $doneTech['referral_code']);
    }

    public function test_chat_list_shows_phone_only_with_an_active_order(): void
    {
        $user = $this->makeUser();
        $busy = $this->sensitiveTechnician();
        $finished = $this->makeTechnician(['phone' => '09124445566', 'melicode' => '1111111111']);
        $this->makeOrder($user, ['technician_id' => $busy->id, 'status' => 1]);
        $this->makeOrder($user, ['technician_id' => $finished->id, 'status' => 2]);
        foreach ([$busy, $finished] as $tech) {
            Chat::create(['user_id' => $user->id, 'technician_id' => $tech->id, 'msg' => 'سلام', 'is_user' => 1, 'is_closed' => 0, 'is_read' => 0]);
        }
        Sanctum::actingAs($user);

        $chats = collect($this->getJson('/api/chats')->assertOk()->json('chats'))->keyBy('technician_id');

        $this->assertSame('09121112233', $chats[$busy->id]['technician']['phone']);
        $this->assertArrayNotHasKey('phone', $chats[$finished->id]['technician']);
        $this->assertArrayNotHasKey('melicode', $chats[$finished->id]['technician']);
    }

    public function test_public_technician_reviews_do_not_expose_customer_private_data(): void
    {
        $user = $this->makeUser(['melicode' => '0099887766', 'card_number' => '6219861000000000', 'home_address' => 'سری']);
        $tech = $this->makeTechnician();
        $order = $this->makeOrder($user, ['technician_id' => $tech->id, 'status' => 2]);
        TechnicianReview::create([
            'user_id' => $user->id, 'technician_id' => $tech->id, 'order_id' => $order->id,
            'application_rate' => 5, 'technician_rate' => 5, 'support_rate' => 5, 'description' => 'عالی',
        ]);

        $response = $this->getJson("/api/reviews/technician/{$tech->id}")->assertOk();
        $body = json_encode($response->json(), JSON_UNESCAPED_UNICODE);

        $this->assertSame($user->name, $response->json('data.reviews.0.user.name'));
        foreach (['0099887766', '6219861000000000', $user->phone, 'سری'] as $secret) {
            $this->assertStringNotContainsString($secret, $body);
        }
    }

    public function test_my_reviews_do_not_expose_technician_phone_or_private_data(): void
    {
        $user = $this->makeUser();
        $tech = $this->sensitiveTechnician();
        $order = $this->makeOrder($user, ['technician_id' => $tech->id, 'status' => 2]);
        TechnicianReview::create([
            'user_id' => $user->id, 'technician_id' => $tech->id, 'order_id' => $order->id,
            'application_rate' => 4, 'technician_rate' => 4, 'support_rate' => 4,
        ]);
        Sanctum::actingAs($user);

        $technician = $this->getJson('/api/reviews/my-reviews')->assertOk()->json('data.reviews.0.technician');

        $this->assertSame($tech->name, $technician['name']);
        $this->assertArrayNotHasKey('phone', $technician);
        $this->assertArrayNotHasKey('bank_card_number', $technician);
    }

    public function test_order_report_by_order_is_only_visible_to_the_order_owner(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $tech = $this->makeTechnician();
        $order = $this->makeOrder($owner, ['technician_id' => $tech->id, 'status' => 1]);
        DB::table('technician_order_reports')->insert([
            'order_id' => $order->id, 'technician_id' => $tech->id, 'product_name' => 'لپ‌تاپ', 'name' => 'x', 'melicode' => '0000000000',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Sanctum::actingAs($stranger);
        $this->getJson("/api/order-reports/by-order/{$order->id}")->assertNotFound();

        Sanctum::actingAs($owner);
        $this->getJson("/api/order-reports/by-order/{$order->id}")->assertOk()->assertJsonPath('data.report.product_name', 'لپ‌تاپ');

        Sanctum::actingAs($tech);
        $this->getJson("/api/order-reports/by-order/{$order->id}")->assertOk();
    }

    public function test_orders_summary_falls_back_to_category_title_for_empty_product_name(): void
    {
        $user = $this->makeUser();
        $tech = $this->makeTechnician();
        $categoryId = $this->makeCategory('نصب ویندوز');
        $withProduct = $this->makeOrder($user, ['technician_id' => $tech->id, 'status' => 2, 'finished_at' => now(), 'category_id' => $categoryId]);
        $emptyProduct = $this->makeOrder($user, ['technician_id' => $tech->id, 'status' => 2, 'finished_at' => now(), 'category_id' => $categoryId]);
        $noReport = $this->makeOrder($user, ['technician_id' => $tech->id, 'status' => 2, 'finished_at' => now(), 'category_id' => $categoryId]);
        foreach ([[$withProduct, 'ASUS X515'], [$emptyProduct, '']] as [$order, $name]) {
            DB::table('technician_order_reports')->insert([
                'order_id' => $order->id, 'technician_id' => $tech->id, 'product_name' => $name, 'name' => 'x', 'melicode' => '0000000000',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        Sanctum::actingAs($user);

        $rows = collect($this->getJson('/api/orders/summary')->assertOk()->json('data.orders'))->keyBy('order_id');

        $this->assertSame('ASUS X515', $rows[$withProduct->id]['product_name']);
        $this->assertSame('نصب ویندوز', $rows[$emptyProduct->id]['product_name']);
        $this->assertSame('نصب ویندوز', $rows[$noReport->id]['product_name']);
        $this->assertSame('نصب ویندوز', $rows[$noReport->id]['category_title']);
    }

    public function test_fault_report_only_accepts_the_users_own_order(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $order = $this->makeOrder($owner, ['status' => 2]);

        Sanctum::actingAs($stranger);
        $this->postJson('/api/fault-reports', ['order_id' => $order->id, 'description' => 'x'])
            ->assertStatus(422);

        Sanctum::actingAs($owner);
        $this->postJson('/api/fault-reports', [
            'order_id' => $order->id, 'product_name' => 'پرینتر', 'technician_code' => '123',
            'paid_price' => '500000', 'description' => 'صدای غیرعادی',
        ])->assertSuccessful();
        $this->assertDatabaseHas('fault_reports', ['order_id' => $order->id, 'description' => 'صدای غیرعادی']);
    }
}
