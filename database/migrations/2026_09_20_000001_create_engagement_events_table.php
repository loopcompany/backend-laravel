<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('engagement_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_type', 40);
            $table->string('user_type', 30)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('route_name')->nullable();
            $table->string('path')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('referrer')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'created_at']);
            $table->index(['user_type', 'user_id']);
            $table->index('route_name');
            $table->index('path');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('engagement_events');
    }
};
