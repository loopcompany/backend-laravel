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
        Schema::table('digital_business_cards', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('id');
            $table->longText('des')->nullable()->after('title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('digital_business_cards', function (Blueprint $table) {
            $table->dropColumn(['logo','des']);
        });
    }
};
