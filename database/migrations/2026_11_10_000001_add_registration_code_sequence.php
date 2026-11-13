<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('code', 30)->nullable()->comment('کد ثبت‌نام کاربر')->change();
        });
        Schema::create('registration_code_sequence', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedInteger('last_number');
        });
        DB::table('registration_code_sequence')->insert(['id' => 1, 'last_number' => 0]);
    }

    public function down(): void
    {
        // Keep the wider column so issued codes remain valid on rollback.
        Schema::dropIfExists('registration_code_sequence');
    }
};
