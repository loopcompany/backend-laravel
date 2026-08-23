<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdfDocument extends Model
{
    protected $fillable = [
        'user',
        'tech',
        'organ',
        'organ_term',
    ];
}
