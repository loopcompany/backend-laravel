<?php

namespace Tests\Feature;

use App\Events\RealtimeChanged;
use App\Models\Order;
use App\Models\OrderQuote;
use App\Services\OrderWorkflowService;
use App\Models\Admin;
use App\Providers\RealtimeServiceProvider;
use App\Services\RealtimePublisher;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\MakesPeople;
use Tests\Fakes\FakeSmsService;
use Tests\TestCase;
use Spatie\Permission\Models\Permission;

class RealtimeTest extends TestCase
{
    use RefreshDatabase, MakesPeople;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'realtime.enabled' => true,
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test',
        ]);
        Broadcast::purge('reverb');
        $this->app->getProvider(RealtimeServiceProvider::class)->boot();
        $this->app->instance(SmsService::class, new FakeSmsService());
        Event::fake([RealtimeChanged::class]);
    }

    public function test_config_requires_auth_and_never_discloses_the_signing_secret(): void
    {
        $this->getJson('/api/realtime/config')->assertUnauthorized();
        $user = $this->makeUser();
        Sanctum::actingAs($user);
        $this->getJson('/api/realtime/config')->assertOk()
            ->assertJsonPath('channels.0', 'private-users.' . $user->id)
            ->assertJsonMissing(['secret' => 'test-secret']);
    }

    public function test_channel_auth_rejects_other_users_and_cross_account_types(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();
        $technician = $this->makeTechnician();
        Sanctum::actingAs($user);
        $payload = ['socket_id' => '123.456', 'channel_name' => 'private-users.' . $user->id];
        $this->postJson('/api/realtime/auth', $payload)->assertOk()->assertJsonStructure(['auth']);
        $this->postJson('/api/realtime/auth', array_replace($payload, ['channel_name' => 'private-users.' . $other->id]))->assertForbidden();
        $this->postJson('/api/realtime/auth', array_replace($payload, ['channel_name' => 'private-technicians.' . $technician->id]))->assertForbidden();
        Sanctum::actingAs($technician);
        $this->postJson('/api/realtime/auth', $payload)->assertForbidden();
    }

    public function test_admin_order_channel_requires_the_order_permission(): void
    {
        $admin = Admin::create(['name' => 'WS admin', 'email' => 'ws-admin@test.local', 'password' => 'test-password', 'is_active' => true]);
        $this->actingAs($admin, 'admin');
        $payload = ['socket_id' => '123.456', 'channel_name' => 'private-admin.orders'];
        $this->postJson('/realtime/admin/auth', $payload)->assertForbidden();
        Permission::findOrCreate('view-orders', 'admin');
        $admin->givePermissionTo('view-orders');
        $this->postJson('/realtime/admin/auth', $payload)->assertOk()->assertJsonStructure(['auth']);
        $this->postJson('/realtime/admin/auth', array_replace($payload, ['channel_name' => 'private-admin.wallet']))->assertForbidden();
    }

    public function test_disabled_broadcasting_cannot_authorize_private_channels(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);
        config(['broadcasting.default' => 'null']);
        $this->postJson('/api/realtime/auth', [
            'socket_id' => '123.456', 'channel_name' => 'private-users.' . $user->id,
        ])->assertStatus(503);
    }

    public function test_post_based_detail_reads_do_not_create_a_refresh_loop(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);
        Route::post('/api/realtime-test/orders/detail', fn () => response()->json(['ok' => true]))
            ->middleware('auth:sanctum');
        Event::fake([RealtimeChanged::class]);
        $this->postJson('/api/realtime-test/orders/detail')->assertOk();
        Event::assertNotDispatched(RealtimeChanged::class);
    }

    public function test_rollback_does_not_send_an_event_but_commit_does(): void
    {
        $publisher = app(RealtimePublisher::class);
        Event::fake([RealtimeChanged::class]);
        DB::beginTransaction();
        $publisher->changed('users.123', ['orders']);
        Event::assertNotDispatched(RealtimeChanged::class);
        DB::rollBack();
        Event::assertNotDispatched(RealtimeChanged::class);

        DB::transaction(fn () => $publisher->changed('users.123', ['orders']));
        Event::assertDispatchedTimes(RealtimeChanged::class, 1);
        Event::assertDispatched(RealtimeChanged::class, fn ($event) =>
            (string) $event->broadcastOn()[0] === 'private-users.123'
            && $event->broadcastWith() === ['topics' => ['orders']]);
    }

    public function test_order_reassignment_notifies_both_technicians_and_only_its_customer(): void
    {
        $user = $this->makeUser();
        $first = $this->makeTechnician();
        $second = $this->makeTechnician();
        $order = $this->makeOrder($user, ['technician_id' => $first->id]);
        Event::fake([RealtimeChanged::class]);
        $order->update(['technician_id' => $second->id]);
        foreach (['users.' . $user->id, 'technicians.' . $first->id, 'technicians.' . $second->id] as $channel) {
            Event::assertDispatched(RealtimeChanged::class, fn ($event) =>
                (string) $event->broadcastOn()[0] === 'private-' . $channel
                && array_keys($event->broadcastWith()) === ['topics']);
        }
    }

    public function test_model_updates_inside_a_rolled_back_transaction_are_not_published(): void
    {
        $user = $this->makeUser();
        $order = $this->makeOrder($user);
        Event::fake([RealtimeChanged::class]);
        DB::beginTransaction();
        $order->update(['des' => 'private description']);
        Event::assertNotDispatched(RealtimeChanged::class);
        DB::rollBack();
        Event::assertNotDispatched(RealtimeChanged::class);
        $this->assertNotSame('private description', Order::find($order->id)->des);
    }

    public function test_scheduled_quote_expiry_publishes_once_without_a_customer_request(): void
    {
        $user = $this->makeUser();
        $order = $this->makeOrder($user);
        $workflow = app(OrderWorkflowService::class);
        $workflow->classify($order, ['service_type' => 'onsite_repair', 'customer_type' => 'individual']);
        $workflow->approveReview($order);
        $quote = $workflow->createQuote($order, OrderQuote::KIND_INITIAL, ['service_fee' => 500000]);
        Event::fake([RealtimeChanged::class]);
        $this->travelTo($quote->sent_at->copy()->addHours(48));
        $workflow->expirePendingInitialQuotes();
        $this->assertSame(OrderQuote::STATUS_EXPIRED, $quote->fresh()->status);
        Event::assertDispatched(RealtimeChanged::class, fn ($event) =>
            (string) $event->broadcastOn()[0] === 'private-users.' . $user->id);
        $count = Event::dispatched(RealtimeChanged::class)->count();
        $workflow->expirePendingInitialQuotes();
        Event::assertDispatchedTimes(RealtimeChanged::class, $count);
        $this->travelBack();
    }
}
