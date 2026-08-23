<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Contact
 * 
 * @property int $id
 * @property string $type
 * @property string $title
 * @property string $link
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $deleted_at
 *
 * @package App\Models
 */
class Contact extends Model
{
	use SoftDeletes;
	protected $table = 'contacts';

	protected $fillable = [
		'type',
		'title',
		'link',
		'name'
	];
}
