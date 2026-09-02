<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // ENUM MODIFY فقط روی MySQL معنی دارد؛ روی SQLite (تست) ستون از قبل string است.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE `users`
            MODIFY COLUMN `account_type`
            ENUM('individual', 'organization', 'company', 'g_organization','s_g_organization')
            NOT NULL DEFAULT 'individual'
            COMMENT 'نوع حساب کاربری: individual (کاربر عادی), organization (سازمان), company (شرکت), g_organization (سازمانی دولتی), s_g_organization (سازمانی نیمه دولتی)';
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE `users`
            MODIFY COLUMN `account_type`
            ENUM('individual', 'organization', 'company')
            NOT NULL DEFAULT 'individual'
            COMMENT 'نوع حساب کاربری: individual (کاربر عادی), organization (سازمان), company (شرکت)';
        ");
    }
};
