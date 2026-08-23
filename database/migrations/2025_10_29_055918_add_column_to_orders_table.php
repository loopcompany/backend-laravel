<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->tinyInteger('is_urgent')->default(0)->after('discount_price');
            $table->string('image_path')->nullable()->after('is_urgent');
            $table->longText('des')->nullable()->after('image_path');
            $table->date('date')->nullable()->after('des');
            $table->string('time')->nullable()->after('date');
            $table->integer('female_count')->default(0)->after('technician_id');
            $table->integer('male_count')->default(0)->after('female_count');
            $table->integer('unspecified_count')->default(0)->after('male_count');
            $table->integer('is_fixed')->default(0)->after('unspecified_count');
            $table->string('technician_des')->nullable()->after('des');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('is_urgent');
            $table->dropColumn('image_path');
            $table->dropColumn('des');
            $table->dropColumn('date');
            $table->dropColumn('time');
            $table->dropColumn('female_count');
            $table->dropColumn('male_count');
            $table->dropColumn('unspecified_count');
            $table->dropColumn('is_fixed');
        });
    }
};
