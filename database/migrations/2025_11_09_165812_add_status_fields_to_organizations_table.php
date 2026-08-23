<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // Profile Status Fields
            $table->enum('profile_status', ['pending', 'approved', 'rejected'])
                  ->default('pending')
                  ->after('manager_national_code')
                  ->comment('وضعیت تایید پروفایل سازمان');
            
            $table->timestamp('profile_approved_at')
                  ->nullable()
                  ->after('profile_status')
                  ->comment('تاریخ تایید پروفایل');
            
            $table->text('profile_rejection_reason')
                  ->nullable()
                  ->after('profile_approved_at')
                  ->comment('دلیل رد پروفایل');
            
            // Contract Status Fields
            $table->enum('contract_status', ['not_uploaded', 'pending', 'approved', 'rejected'])
                  ->default('not_uploaded')
                  ->after('profile_rejection_reason')
                  ->comment('وضعیت تایید قرارداد');
            
            $table->timestamp('contract_approved_at')
                  ->nullable()
                  ->after('contract_status')
                  ->comment('تاریخ تایید قرارداد');
            
            $table->text('contract_rejection_reason')
                  ->nullable()
                  ->after('contract_approved_at')
                  ->comment('دلیل رد قرارداد');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'profile_status',
                'profile_approved_at',
                'profile_rejection_reason',
                'contract_status',
                'contract_approved_at',
                'contract_rejection_reason',
            ]);
        });
    }
};
