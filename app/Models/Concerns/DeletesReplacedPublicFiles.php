<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * وقتی فایل یک ستون (مثل عکس پروفایل) عوض یا حذف می‌شود، فایل قبلی از دیسک public پاک می‌شود.
 * مدل باید ثابت $publicFileAttributes را تعریف کند.
 */
trait DeletesReplacedPublicFiles
{
    public static function bootDeletesReplacedPublicFiles(): void
    {
        static::updated(function ($model) {
            foreach (static::$publicFileAttributes as $attribute) {
                if (!$model->wasChanged($attribute)) {
                    continue;
                }

                $old = $model->getOriginal($attribute);
                if (is_string($old) && $old !== '' && !str_starts_with($old, 'http') && !str_starts_with($old, 'assets/')) {
                    try {
                        Storage::disk('public')->delete($old);
                    } catch (Throwable) {
                        // فایل قبلی نبود؛ مهم نیست
                    }
                }
            }
        });
    }
}
