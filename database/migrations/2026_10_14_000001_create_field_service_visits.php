<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_service_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technician_id')->constrained()->restrictOnDelete();
            $table->string('status', 24)->default('in_progress');
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('site_condition', 24)->nullable();
            $table->text('problem_description')->nullable();
            $table->json('actions')->nullable();
            $table->boolean('has_remaining_work')->nullable();
            $table->text('remaining_action')->nullable();
            $table->string('continuation_owner', 24)->nullable();
            $table->text('continuation_note')->nullable();
            $table->string('outcome', 32)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status']);
        });

        Schema::create('field_service_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_service_visit_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('kind', 24);
            $table->string('original_name')->nullable();
            $table->timestamps();
        });

        Schema::create('field_service_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_service_visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('decision', 24);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_service_reviews');
        Schema::dropIfExists('field_service_photos');
        Schema::dropIfExists('field_service_visits');
    }
};
