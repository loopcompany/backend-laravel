<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('digital_business_cards', function (Blueprint $table) {
            $table->string('title_font_family', 50)->nullable()->after('header_text_color');
            $table->string('description_font_family', 50)->nullable()->after('title_font_family');
        });
    }

    public function down(): void
    {
        // Intentionally left blank to keep the migration strictly additive.
    }
};
