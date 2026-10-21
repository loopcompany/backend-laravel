<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * شرکت یا سازمان همکار در بخش «سوابق همکاری» صفحه اصلی.
 *
 * @property int $id
 * @property string $name
 * @property string $logo
 * @property string $website_url
 * @property int $sort_order
 * @property bool $is_active
 */
class Partner extends Model
{
    protected $fillable = [
        'name',
        'logo',
        'website_url',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }
}
