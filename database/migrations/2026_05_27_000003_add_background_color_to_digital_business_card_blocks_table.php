<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('digital_business_card_blocks', function (Blueprint $table) {
            $table->string('background_color')->nullable()->after('color');
        });
    }

    public function down(): void
    {
        // Intentionally left blank to keep this migration strictly additive.
    }
};