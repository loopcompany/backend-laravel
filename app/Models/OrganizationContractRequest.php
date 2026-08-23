<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationContractRequest extends Model
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'status',
        'need_docs',
        'information',
        'title',
        'reject_reason',
        'contract_file_path',
        'signed_contract_file_path',
        'uploaded_by_admin_at',
        'uploaded_at',
        'reject_contract_reason'
    ];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
    public function gallery(): HasMany
    {
        return $this->hasMany(OrganizationContractGallery::class, 'organization_contract_id');
    }

    public function canBeEdited(): bool
    {
        return $this->status == '1' || $this->status == '4';
    }
}
