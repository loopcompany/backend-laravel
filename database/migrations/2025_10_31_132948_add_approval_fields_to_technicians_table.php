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
        Schema::table('technicians', function (Blueprint $table) {
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])
                ->default('pending')
                ->after('has_access')
                ->comment('وضعیت تایید تکنسین: pending=در انتظار, approved=تایید شده, rejected=رد شده');
            
            $table->text('rejection_reason')
                ->nullable()
                ->after('approval_status')
                ->comment('علت رد شدن تکنسین (اجباری در صورت رد)');
            
            $table->timestamp('approved_at')
                ->nullable()
                ->after('rejection_reason')
                ->comment('زمان تایید تکنسین');
            
            $table->unsignedBigInteger('approved_by')
                ->nullable()
                ->after('approved_at')
                ->comment('شناسه ادمینی که تکنسین را تایید/رد کرده');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('technicians', function (Blueprint $table) {
            $table->dropColumn([
                'approval_status',
                'rejection_reason',
                'approved_at',
                'approved_by'
            ]);
        });
    }
};
