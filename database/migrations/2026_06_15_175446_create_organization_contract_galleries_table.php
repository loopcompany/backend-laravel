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
        Schema::create('organization_contract_galleries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_contract_id');
            $table->foreign('organization_contract_id')->references('id')->on('organization_contract_requests')->onDelete('cascade');
            $table->string('file_path');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_contract_galleries');
    }
};
