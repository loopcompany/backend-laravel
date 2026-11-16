<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['organization_terms', 'terms'] as $table) {
            if (!Schema::hasTable($table)) continue;
            foreach (['title', 'description'] as $column) {
                if (!Schema::hasColumn($table, $column)) continue;
                DB::table($table)->where($column, 'like', '%اصطالحات%')->orderBy('id')->chunkById(100, function ($rows) use ($table, $column) {
                    foreach ($rows as $row) DB::table($table)->where('id', $row->id)->update([$column => str_replace('اصطالحات', 'اصطلاحات', $row->{$column})]);
                });
            }
        }
    }

    public function down(): void
    {
        // Correct spelling remains valid after rolling back application code.
    }
};
