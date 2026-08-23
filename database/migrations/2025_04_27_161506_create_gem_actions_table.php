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
        Schema::create('gem_actions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('action_key')->unique(); // مثلا: 'complete_profile' یا 'invite_friend'

            $table->bigInteger('gems')->default(0);  // تعداد جم تعلق گرفته
            $table->boolean('is_active')->default(true); // آیا این پاداش فعال است یا خیر

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gem_actions');
    }
};
