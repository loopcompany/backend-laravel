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
        Schema::create('organization_contract_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->integer('status')->default(0)->comment('0 pending, 1 sent contract, 2 reject request, 3 accept uploaded contract');
            $table->longText('need_docs')->nullable();
            $table->string('title')->nullable();
            $table->string('reject_reason')->nullable();
            $table->string('contract_file_path')->nullable();
            $table->string('signed_contract_file_path')->nullable();
            $table->dateTime('uploaded_by_admin_at')->nullable();
            $table->dateTime('uploaded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_contract_requests');
    }
};
