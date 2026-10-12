<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ردیف‌های فهرست دستگاه‌های Google Play (`php artisan devices:import-names`).
 */
class DeviceMarketingName extends Model
{
    public $timestamps = false;

    protected $fillable = ['model', 'brand', 'marketing_name'];
}
