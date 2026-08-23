<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExtraService extends Model
{
    use SoftDeletes;

	protected $fillable = [
		'title',
		'des'
	];

    public function categories()
    {
        return $this->belongsToMany(
            Category::class,
            'extra_service_categories',
            'extra_service_id',
            'category_id'
        )->withTimestamps()->withPivot('deleted_at')->wherePivotNull('deleted_at');
    }
    
	public function extra_service_categories()
	{
		return $this->hasMany(ExtraServiceCategory::class);
	}
	
	public function order_extra_services()
	{
		return $this->hasMany(OrderExtraService::class);
	}
	public function extra_service_details()
	{
		return $this->hasMany(ExtraServiceDetail::class);
	}
}
