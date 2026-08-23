<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Social
 * 
 * @property int $id
 * @property string $title
 * @property string $value
 * @property string $link
 * @property string $icon
 * @property string $float
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @package App\Models
 */
class Social extends Model
{
	use SoftDeletes;
	protected $table = 'socials';

	protected $fillable = [
		'title',
		'value',
		'link',
		'icon',
		'float'
	];
}
