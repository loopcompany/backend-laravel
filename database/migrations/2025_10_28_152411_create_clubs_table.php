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
        Schema::create('clubs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('category_id');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');

            $table->string('title');
            $table->integer('gems')->default(0);
            $table->integer('count')->default(1); /// تعداد دفعات قابل استفاده
            $table->integer('expire'); /// تعداد روزی که میخواد فعال باشه
            // $table->tinyInteger('is_weekly')->default(0)->comment('0:no - 1:yes');
            $table->bigInteger('discount_percent')->default(0);
            $table->bigInteger('max_price')->nullable();
            $table->string('image_path')->nullable();
            
            $table->longText('des')->nullable();
            $table->longText('long_des')->nullable();
            $table->longText('meta')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clubs');
    }
};
