<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class About
 * 
 * @property int $id
 * @property string $title
 * @property string $des
 * @property string|null $image_path
 * @property string|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @package App\Models
 */
class About extends Model
{
	use SoftDeletes;
	protected $table = 'abouts';

	protected $fillable = [
		'title',
		'des',
		'image_path',
		'meta'
	];
}
