<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * این میگریشن تراکنش‌های pending قدیمی که بعدا یک تراکنش موفق مشابه برای آن‌ها ثبت شده
     * را به وضعیت ناموفق (-200) تغییر می‌دهد تا تاریخچه تراکنش‌ها صحیح نمایش داده شود.
     */
    public function up(): void
    {
        // پیدا کردن تراکنش‌های pending که یک تراکنش موفق با همان مبلغ و user_id دارند
        $pendingTransactions = DB::table('user_transactions')
            ->where('status', 0)
            ->where('type', 1) // شارژ کیف پول
            ->get();

        $fixedCount = 0;

        foreach ($pendingTransactions as $pending) {
            // بررسی وجود تراکنش موفق مشابه (با همان user_id و مبلغ نزدیک به زمان pending)
            $successfulTransaction = DB::table('user_transactions')
                ->where('user_id', $pending->user_id)
                ->where('type', 1)
                ->where('status', 100)
                ->where('price', $pending->price)
                ->where('created_at', '>=', $pending->created_at)
                ->where('created_at', '<=', date('Y-m-d H:i:s', strtotime($pending->created_at . ' +1 hour')))
                ->first();

            if ($successfulTransaction) {
                // تراکنش pending را به ناموفق تغییر می‌دهیم
                DB::table('user_transactions')
                    ->where('id', $pending->id)
                    ->update([
                        'status' => -200,
                        'referenceId' => 'FIXED_PENDING_' . $pending->id,
                        'description' => 'تراکنش pending قدیمی - اصلاح شده',
                        'updated_at' => now()
                    ]);

                $fixedCount++;
                
                Log::info('تراکنش pending اصلاح شد', [
                    'pending_id' => $pending->id,
                    'successful_id' => $successfulTransaction->id,
                    'user_id' => $pending->user_id,
                    'amount' => $pending->price
                ]);
            }
        }

        Log::info("میگریشن رفع باگ تراکنش‌های pending تکمیل شد", [
            'fixed_count' => $fixedCount,
            'total_pending_checked' => $pendingTransactions->count()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // بازگردانی تراکنش‌های اصلاح شده
        DB::table('user_transactions')
            ->where('referenceId', 'like', 'FIXED_PENDING_%')
            ->update([
                'status' => 0,
                'description' => 'شارژ کیف پول',
                'updated_at' => now()
            ]);

        Log::info("بازگردانی تراکنش‌های اصلاح شده انجام شد");
    }
};
