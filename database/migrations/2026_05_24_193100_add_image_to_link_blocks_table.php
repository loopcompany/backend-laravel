<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('link_blocks', function (Blueprint $table) {
            $table->string('image')->nullable()->after('link');
        });
    }

    public function down(): void
    {
        Schema::table('link_blocks', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
};