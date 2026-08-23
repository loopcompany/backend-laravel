<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'term_type',
        'label',
        'value',
        'sort_order',
        'is_active',
        'start_time'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Scope برای فیلتر کردن گزینه‌های فعال
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope برای فیلتر بر اساس نوع
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope برای فیلتر بر اساس term_type
     */
    public function scopeForTerm($query, string $termType)
    {
        return $query->where('term_type', $termType);
    }

    /**
     * مرتب‌سازی بر اساس sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }
}
