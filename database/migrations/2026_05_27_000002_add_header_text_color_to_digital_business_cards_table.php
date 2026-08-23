<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('digital_business_cards', function (Blueprint $table) {
            $table->string('header_text_color', 20)->nullable()->after('page_background_image');
        });
    }

    public function down(): void
    {
        // Intentionally left blank to keep the migration strictly additive.
    }
};