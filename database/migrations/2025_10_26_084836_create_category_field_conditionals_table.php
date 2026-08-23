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
        Schema::create('category_field_conditionals', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('category_field_id');
            $table->foreign('category_field_id')->references('id')->on('category_fields')->onDelete('cascade');

            $table->bigInteger('step');
            $table->bigInteger('sort');

            $table->string('type');
            
            
            $table->unsignedBigInteger('field_detail_id'); /// ایدی گزینه منتخب
            $table->foreign('field_detail_id')->references('id')->on('field_details')->onDelete('cascade');

            
            $table->unsignedBigInteger('field_id');
            $table->foreign('field_id')->references('id')->on('fields')->onDelete('cascade');

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_field_conditionals');
    }
};
