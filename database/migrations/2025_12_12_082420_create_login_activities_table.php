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
        Schema::create('login_activities', function (Blueprint $table) {
            $table->id();
            $table->enum('user_type', ['user', 'technician', 'organization'])->comment('نوع کاربر');
            $table->unsignedBigInteger('user_id')->comment('شناسه کاربر');
            $table->enum('action', ['login', 'logout', 'logout_all'])->comment('نوع عملیات');
            $table->string('ip_address', 45)->nullable()->comment('آدرس IP');
            $table->text('user_agent')->nullable()->comment('اطلاعات مرورگر');
            $table->string('device_info')->nullable()->comment('اطلاعات دستگاه');
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index(['user_type', 'user_id']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_activities');
    }
};
