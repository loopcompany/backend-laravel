<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->string('app', 20)->default('user');      // user (کاربر/سازمانی) | technician
            $table->string('platform', 20);                  // android | ios | web
            $table->string('latest_version', 20);
            $table->string('min_supported_version', 20)->nullable();
            $table->string('update_url')->nullable();
            $table->text('release_notes')->nullable();
            $table->timestamps();

            $table->unique(['app', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_versions');
    }
};
