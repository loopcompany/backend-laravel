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
        Schema::create('faq_blocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('digital_business_card_id');
            $table->foreign('digital_business_card_id')->references('id')->on('digital_business_cards')->onDelete('cascade');
            $table->unsignedBigInteger('digital_business_card_block_id');
            $table->foreign('digital_business_card_block_id')->references('id')->on('digital_business_card_blocks')->onDelete('cascade');
            $table->string('title');
            $table->longText('descriptions');
            $table->integer('sort')->default(0);
            $table->string('title_color')->default('#000');
            $table->string('descriptions_color')->default('#000');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faq_blocks');
    }
};
