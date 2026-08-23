<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('digital_business_card_blocks', function (Blueprint $table) {
            $table->string('type')->nullable()->after('digital_business_card_id');
        });
    }

    public function down(): void
    {
        Schema::table('digital_business_card_blocks', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};