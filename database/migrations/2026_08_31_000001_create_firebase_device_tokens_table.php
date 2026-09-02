<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firebase_device_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->text('token');
            $table->char('token_hash', 64)->unique();
            $table->string('platform', 20);
            $table->string('device_id')->nullable();
            $table->string('app_version', 50)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            // Explicit short name: the auto-generated one exceeds MySQL's 64-char identifier limit.
            $table->index(['tokenable_type', 'tokenable_id', 'platform'], 'fdt_tokenable_platform_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firebase_device_tokens');
    }
};
