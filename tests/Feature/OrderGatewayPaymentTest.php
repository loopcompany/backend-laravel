<?php

namespace Tests\Feature;

use App\Models\UserTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Shetabit\Payment\Facade\Payment;
use Tests\Concerns\MakesPeople;
use Tests\TestCase;

class OrderGatewayPaymentTest extends TestCase
{
    use RefreshDatabase, MakesPeople;

    public function test_callback_keeps_the_original_prepayment_kind_after_order_state_changes(): void
    {
        $user = $this->makeUser();
        $walletBefore = (int) $user->wallet;
        $order = $this->makeOrder($user, [
            'prepayment' => 30,
            'loop_cost_estimate' => 500000,
            'prepayment_payment_status' => 1,
            'payment_status' => 0,
        ]);
        $transaction = UserTransaction::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'price' => 150000,
            'type' => 2,
            'status' => 0,
            'referenceId' => '111222',
            'order_payment_kind' => 'prepay',
            'description' => 'Order gateway payment',
        ]);
        $gateway = new class {
            public int $verified = 0;
            public int $amount;
            public string $trackId;

            public function via(string $driver): self
            {
                return $this;
            }

            public function amount(int $amount): self
            {
                $this->amount = $amount;
                return $this;
            }

            public function transactionId(string $trackId): self
            {
                $this->trackId = $trackId;
                return $this;
            }

            public function verify(): self
            {
                $this->verified++;
                return $this;
            }

            public function getReferenceId(): string
            {
                return 'settled-reference';
            }
        };
        Payment::swap($gateway);

        $callback = route('order.payment.callback', ['trackId' => '111222', 'success' => '1']);
        $this->get($callback)->assertRedirect(route('redirect', ['status' => 'OK']));
        $this->assertEquals(1, $order->fresh()->prepayment_payment_status);
        $this->assertEquals(0, $order->fresh()->payment_status);
        $this->assertEquals(100, $transaction->fresh()->status);

        $this->get($callback)->assertRedirect();
        $this->assertEquals(0, $order->fresh()->payment_status);
        $this->assertSame(1, $gateway->verified);
        $this->assertSame(150000, $gateway->amount);
        $this->assertSame('111222', $gateway->trackId);
        $this->assertSame($walletBefore + 150000, (int) $user->fresh()->wallet);
        $this->assertSame(1, UserTransaction::where('referenceId', 'ORDER_REFUND_'.$transaction->id)->count());
    }
}
