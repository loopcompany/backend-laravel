<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('technicians', function (Blueprint $table) {
            $table->uuid('identity_badge_uuid')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('technicians', fn (Blueprint $table) => $table->dropColumn('identity_badge_uuid'));
    }
};
