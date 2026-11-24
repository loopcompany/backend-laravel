<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('orders', function (Blueprint $t) {
        $t->uuid('in_person_request_uuid')->nullable()->unique();
        $t->char('in_person_payload_hash', 64)->nullable();
        $t->json('in_person_items')->nullable();
        $t->foreignId('registered_by_technician_id')->nullable()->constrained('technicians')->restrictOnDelete();
    }); }
    public function down(): void { Schema::table('orders', function (Blueprint $t) {
        $t->dropConstrainedForeignId('registered_by_technician_id');
        $t->dropColumn(['in_person_request_uuid', 'in_person_payload_hash', 'in_person_items']);
    }); }
};
