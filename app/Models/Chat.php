<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Class Chat
 *
 * @property int $id
 * @property int $user_id
 * @property int $technician_id
 * @property string|null $msg
 * @property int $is_user
 * @property int $is_closed
 * @property int $is_read
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Chat extends Model
{
	protected $table = 'chats';

	protected $casts = [
		'user_id' => 'int',
		'technician_id' => 'int',
		'is_user' => 'int',
		'is_closed' => 'int',
		'is_read' => 'int'
	];

	protected $fillable = [
		'user_id',
		'technician_id',
		'msg',
		'is_user',
		'is_closed',
		'is_read'
	];

	public function user()
	{
		return $this->belongsTo(User::class)->withoutGlobalScope(SoftDeletingScope::class);
	}

	public function technician()
	{
		return $this->belongsTo(Technician::class)->withoutGlobalScope(SoftDeletingScope::class);
	}
}
