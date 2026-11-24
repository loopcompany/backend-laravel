<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        foreach (['users', 'admins', 'technicians', 'employment_files'] as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                $table->string('registration_city_code', 2)->nullable();
                $table->string('registration_region_code', 2)->nullable();
                if ($name !== 'users') $table->unsignedTinyInteger('registration_method')->nullable();
            });
        }
    }
    public function down(): void {
        foreach (['users', 'admins', 'technicians', 'employment_files'] as $name) Schema::table($name, fn (Blueprint $t) => $t->dropColumn(['registration_city_code', 'registration_region_code', ...($name !== 'users' ? ['registration_method'] : [])]));
    }
};
