<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('app_releases', function (Blueprint $t) {
            $t->id(); $t->string('app', 24); $t->string('platform', 16); $t->string('variant', 32);
            $t->string('version', 32); $t->unsignedInteger('version_code')->nullable();
            $t->string('artifact'); $t->char('sha256', 64); $t->unsignedBigInteger('bytes');
            $t->text('notes')->nullable(); $t->boolean('is_published')->default(false);
            $t->timestamp('published_at')->nullable(); $t->timestamps();
            $t->unique(['app', 'platform', 'variant', 'version']);
        });
    }
    public function down(): void { Schema::dropIfExists('app_releases'); }
};
