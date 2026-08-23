<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('edit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('شناسه کاربر مرتبط');
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade')->comment('شناسه کاربر مرتبط');
            $table->string('profile_image')->nullable();
            
            $table->string('organization_name', 200)->comment('نام رسمی سازمان');
            $table->string('business_name')->nullable();
            $table->string('manager_full_name', 120)->comment('نام و نام خانوادگی مدیر');
            $table->date('birth_date')->nullable();
            
            $table->string('agent_name')->nullable();
            $table->string('agent_phone')->nullable();
            
            $table->string('history')->nullable();
            $table->string('email')->nullable();
            $table->string('organization_phone', 15)->comment('شماره تلفن ثابت سازمان');
            $table->string('region')->nullable();
            $table->foreignId('region_id')->constrained('regions')->onDelete('cascade');
            $table->text('organization_address')->nullable();
            $table->text('postal_code')->nullable();
            $table->integer('status')->default(0);
            



            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('edit_requests');
    }
};
