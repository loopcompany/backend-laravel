<?php
use Illuminate\Database\Migrations\Migration;
return new class extends Migration {
    public function up(): void { app(\App\Services\AdminPermissionCatalog::class)->sync(); }
    // Granted permissions are retained during code rollback.
    public function down(): void {}
};
