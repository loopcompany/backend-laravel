<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // No backfill: previously registered users retain their codes and unknown origin.
        Schema::table('users',fn (Blueprint $table)=>$table->unsignedTinyInteger('registration_method')->nullable());
    }
    public function down(): void
    {
        Schema::table('users',fn (Blueprint $table)=>$table->dropColumn('registration_method'));
    }
};
