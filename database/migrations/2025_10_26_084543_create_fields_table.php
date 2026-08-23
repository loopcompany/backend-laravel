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
        Schema::create('fields', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->bigInteger('sort')->default(0);

            $table->string('type'); //counter - input - checkbox - radioButton - image - description 

            $table->integer('is_required')->nullable()->comment('0 -> not required, 1 -> required'); // counter - checkbox - radioButton
            
            $table->string('image_path')->nullable();

            $table->string('icon_name')->nullable();
            $table->string('guide')->nullable();
            $table->string('des')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fields');
    }
};
