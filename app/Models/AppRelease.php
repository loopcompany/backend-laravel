<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AppRelease extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['is_published' => 'boolean', 'bytes' => 'integer', 'version_code' => 'integer', 'published_at' => 'datetime'];

    protected static function booted(): void
    {
        static::saving(function (self $record) {
            if ($record->exists && $record->getOriginal('is_published') && ! $record->is_published && ! array_diff(array_keys($record->getDirty()), ['is_published', 'updated_at'])) return;
            $metadata = app(\App\Services\AppReleaseService::class)->inspect($record->artifact);
            if ($record->exists && $record->getOriginal('is_published') && ($record->isDirty(['artifact', 'version', 'version_code', 'sha256', 'bytes', 'app', 'platform', 'variant']) || $metadata['sha256'] !== $record->getOriginal('sha256'))) {
                throw \Illuminate\Validation\ValidationException::withMessages(['artifact' => 'نسخهٔ منتشرشده تغییر نمی‌کند؛ نسخهٔ جدید ثبت کنید.']);
            }
            if ($metadata['platform'] === 'android' && (! $record->version_code || $record->version_code > 2147483647)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['version_code' => 'شمارهٔ ساخت واقعی اندروید الزامی است.']);
            }
            $record->forceFill($metadata);
            if ($record->is_published && ! $record->published_at) $record->published_at = now();
        });
        static::saved(function (self $record) {
            if ($record->is_published || $record->wasChanged('is_published')) DB::afterCommit(function () {
                try { event(new \App\Events\AppReleasesChanged); } catch (\Throwable $error) { logger()->warning('App release announcement unavailable', ['type' => $error::class]); }
            });
        });
    }
}
