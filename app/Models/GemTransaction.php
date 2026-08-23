<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GemTransaction extends Model
{
	protected $fillable = [
		'user_id',
		'gems',
		'gem_action_id',
	];

	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class)->withoutGlobalScope(SoftDeletingScope::class);
	}

	public function gem_action(): BelongsTo
	{
		return $this->belongsTo(GemAction::class);
	}
}
