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
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->string('title');

            $table->unsignedBigInteger('category_id')->nullable();
            // Foreign key را بعداً اضافه می‌کنیم

            $table->string('audio_path')->nullable();
            $table->string('video_path')->nullable();
            $table->string('image_path')->nullable();
            $table->string('document_path')->nullable();

            $table->longText('short_des')->nullable();
            $table->longText('des');
            $table->longText('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
