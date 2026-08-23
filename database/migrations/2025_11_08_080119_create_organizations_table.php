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
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade')->comment('شناسه کاربر مرتبط');
            
            // Organization Information
            $table->string('organization_name', 200)->index()->comment('نام رسمی سازمان');
            $table->string('organization_code')->unique()->comment('کد 6 رقمی یونیک سازمان برای ورود');
            $table->string('organization_phone', 15)->comment('شماره تلفن ثابت سازمان');
            $table->text('organization_address')->comment('آدرس سازمان');
            
            // Manager Information
            $table->string('manager_full_name', 120)->comment('نام و نام خانوادگی مدیر');
            $table->string('manager_national_code', 10)->unique()->comment('کد ملی مدیر (منحصر به فرد)');
            
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('organization_code');
            $table->index('manager_national_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
