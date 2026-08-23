<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationContractGallery extends Model
{
    protected $fillable = [
        'organization_contract_id',
        'file_path'
    ];

    public function organization_contract(): BelongsTo
    {
        return $this->belongsTo(OrganizationContractRequest::class, 'organization_contract_id');
    }
}
