<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FieldServiceVisit extends Model
{
    public const IN_PROGRESS = 'in_progress';
    public const SUBMITTED = 'submitted';
    public const CHANGES_REQUESTED = 'changes_requested';
    public const APPROVED = 'approved';

    public const CONDITIONS = ['normal', 'problem', 'broken', 'needs_inspection'];
    public const CONTINUATION_OWNERS = ['same_technician', 'next_technician', 'support', 'organization', 'waiting_for_part'];
    public const OUTCOMES = ['completed', 'revisit_required', 'waiting_for_part', 'waiting_for_organization', 'needs_inspection'];

    protected $fillable = [
        'order_id', 'technician_id', 'status', 'started_at', 'finished_at',
        'latitude', 'longitude', 'site_condition', 'problem_description',
        'actions', 'has_remaining_work', 'remaining_action', 'continuation_owner',
        'continuation_note', 'outcome', 'submitted_at', 'reviewed_by_user_id',
        'reviewed_at', 'review_note',
    ];

    protected $casts = [
        'started_at' => 'datetime', 'finished_at' => 'datetime',
        'submitted_at' => 'datetime', 'reviewed_at' => 'datetime',
        'actions' => 'array', 'has_remaining_work' => 'boolean',
        'latitude' => 'decimal:7', 'longitude' => 'decimal:7',
    ];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function technician(): BelongsTo { return $this->belongsTo(Technician::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by_user_id'); }
    public function photos(): HasMany { return $this->hasMany(FieldServicePhoto::class); }
    public function reviews(): HasMany { return $this->hasMany(FieldServiceReview::class); }
}
