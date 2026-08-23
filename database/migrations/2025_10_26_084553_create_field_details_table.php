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
        Schema::create('field_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('field_id');
            $table->foreign('field_id')->references('id')->on('fields')->onDelete('cascade');
            
            $table->string('title');
            $table->string('second_title')->nullable();

            $table->integer('is_required')->default(0)->comment('0 -> not required, 1 -> required'); // input
            $table->string('image_path')->nullable(); //description

            $table->bigInteger('price')->default(0)->nullable();
            $table->string('show_price')->nullable()->comment('0:not show - 1:show');
            $table->integer('is_checked')->default(0)->comment('0 -> is not checked, 1 -> is checked');
            $table->integer('has_counter')->default(0)->comment('0 -> is not checked, 1 -> is checked');
            
            // $table->string('min')->default(0); //input
            // $table->string('max')->default(0); //input

            $table->string('icon_name')->nullable();
            $table->string('guide')->nullable();
            $table->longText('des')->nullable();
            $table->bigInteger('sort')->default(0);
            
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('field_details');
    }
};
