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
        Schema::create('debt_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_id')->constrained('technicians')->onDelete('cascade');
            $table->string('amount')->nullable();
            $table->enum('type',['sponsor', 'free'])->comment('ضامن/ ضمانت نامه - وام بدون  بهره');
            $table->longText('description');
            $table->string('sponsor')->nullable();
            $table->integer('month')->nullable();
            $table->string('urgent_description')->nullable();
            $table->integer('status')->default(0)->comment('0 pending, 1 approved, 2 rejected');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debt_requests');
    }
};
