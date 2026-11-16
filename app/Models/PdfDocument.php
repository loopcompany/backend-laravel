<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdfDocument extends Model
{
    protected $fillable = [
        'organization_assessment', 'organization_agreement_draft',
        'user',
        'tech',
        'organ',
        'organ_term',
    ];
}
