<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class Club extends Model
{
    use SoftDeletes;
    protected $casts = [
		'category_id' => 'int',
		'gems' => 'int',
		'count' => 'int',
		'expire' => 'int',
		// 'is_weekly' => 'int',
		'discount_percent' => 'int'
	];

	protected $fillable = [
		'category_id',
		'title',
		'gems',
		'count',
		'expire',
		// 'is_weekly',
		'discount_percent',
		'image_path',
		'des',
		'long_des',
		'meta',
		'max_price',
		'expired_at',
		'application_discount',
	];


	public function category()
	{
		return $this->belongsTo(Category::class)->withoutGlobalScope(SoftDeletingScope::class);
	}
}
