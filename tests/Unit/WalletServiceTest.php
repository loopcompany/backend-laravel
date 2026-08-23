<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserTransaction;
use App\Repositories\WalletRepository;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WalletService $walletService;
    protected WalletRepository $walletRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->walletRepo = new WalletRepository();
        $this->walletService = new WalletService($this->walletRepo);
    }

    /**
     * تست رفع باگ: باید تراکنش pending را به‌روزرسانی کند نه ایجاد تراکنش جدید
     */
    public function test_complete_successful_charge_updates_pending_transaction(): void
    {
        // ایجاد کاربر
        $user = User::factory()->create([
            'phone' => '09123456789',
            'wallet' => 0
        ]);

        $amount = 50000.0;

        // شروع فرآیند شارژ (ایجاد تراکنش pending)
        $initiateResult = $this->walletService->initiateCharge($user->id, $amount);
        
        $this->assertTrue($initiateResult['success']);
        $pendingTransactionId = $initiateResult['data']['transaction_id'];

        // بررسی ایجاد تراکنش pending
        $pendingTransaction = UserTransaction::find($pendingTransactionId);
        $this->assertNotNull($pendingTransaction);
        $this->assertEquals(0, $pendingTransaction->status); // در انتظار
        $this->assertEquals($amount, $pendingTransaction->price);

        // شمارش تراکنش‌های قبل از تکمیل
        $transactionCountBefore = UserTransaction::where('user_id', $user->id)->count();
        $this->assertEquals(1, $transactionCountBefore);

        // تکمیل شارژ موفق
        $referenceId = 'TEST_REF_' . time();
        $completeResult = $this->walletService->completeSuccessfulCharge(
            $user->id,
            $amount,
            $referenceId
        );

        $this->assertTrue($completeResult['success']);
        $this->assertEquals('کیف پول با موفقیت شارژ شد.', $completeResult['message']);

        // بررسی: نباید تراکنش جدید ایجاد شده باشد
        $transactionCountAfter = UserTransaction::where('user_id', $user->id)->count();
        $this->assertEquals(1, $transactionCountAfter, 'باید فقط یک تراکنش وجود داشته باشد (به‌روزرسانی همان pending)');

        // بررسی به‌روزرسانی تراکنش pending
        $updatedTransaction = UserTransaction::find($pendingTransactionId);
        $this->assertNotNull($updatedTransaction);
        $this->assertEquals(100, $updatedTransaction->status, 'وضعیت باید موفق (100) باشد');
        $this->assertEquals($referenceId, $updatedTransaction->referenceId);

        // بررسی شارژ کیف پول
        $user->refresh();
        $this->assertEquals($amount, $user->wallet);
    }

    /**
     * تست رفع باگ: تراکنش ناموفق هم باید pending را به‌روزرسانی کند
     */
    public function test_record_failed_charge_updates_pending_transaction(): void
    {
        // ایجاد کاربر
        $user = User::factory()->create([
            'phone' => '09123456788',
            'wallet' => 0
        ]);

        $amount = 30000.0;

        // شروع فرآیند شارژ (ایجاد تراکنش pending)
        $initiateResult = $this->walletService->initiateCharge($user->id, $amount);
        
        $this->assertTrue($initiateResult['success']);
        $pendingTransactionId = $initiateResult['data']['transaction_id'];

        // شمارش تراکنش‌های قبل
        $transactionCountBefore = UserTransaction::where('user_id', $user->id)->count();
        $this->assertEquals(1, $transactionCountBefore);

        // ثبت تراکنش ناموفق
        $referenceId = 'FAILED_' . time();
        $failedResult = $this->walletService->recordFailedCharge(
            $user->id,
            $amount,
            $referenceId
        );

        $this->assertFalse($failedResult['success']);
        $this->assertEquals('تراکنش ناموفق بود.', $failedResult['message']);

        // بررسی: نباید تراکنش جدید ایجاد شده باشد
        $transactionCountAfter = UserTransaction::where('user_id', $user->id)->count();
        $this->assertEquals(1, $transactionCountAfter, 'باید فقط یک تراکنش وجود داشته باشد');

        // بررسی به‌روزرسانی تراکنش pending
        $updatedTransaction = UserTransaction::find($pendingTransactionId);
        $this->assertNotNull($updatedTransaction);
        $this->assertEquals(-200, $updatedTransaction->status, 'وضعیت باید ناموفق (-200) باشد');
        $this->assertEquals($referenceId, $updatedTransaction->referenceId);

        // بررسی کیف پول شارژ نشده
        $user->refresh();
        $this->assertEquals(0, $user->wallet);
    }

    /**
     * تست سناریو واقعی: شارژ موفق کامل
     */
    public function test_full_successful_wallet_charge_flow(): void
    {
        $user = User::factory()->create([
            'phone' => '09123456787',
            'wallet' => 10000
        ]);
        $chargeAmount = 50000.0;

        // مرحله 1: شروع شارژ
        $initiateResult = $this->walletService->initiateCharge($user->id, $chargeAmount);
        $this->assertTrue($initiateResult['success']);

        // مرحله 2: تکمیل شارژ موفق
        $referenceId = 'ZARINPAL_' . time();
        $completeResult = $this->walletService->completeSuccessfulCharge(
            $user->id,
            $chargeAmount,
            $referenceId
        );

        $this->assertTrue($completeResult['success']);
        $this->assertEquals(60000, $completeResult['data']['wallet_balance']);

        // بررسی نهایی
        $transactions = UserTransaction::where('user_id', $user->id)->get();
        $this->assertCount(1, $transactions, 'باید فقط یک تراکنش وجود داشته باشد');
        $this->assertEquals(100, $transactions[0]->status);
        $this->assertEquals($referenceId, $transactions[0]->referenceId);
    }
}
