<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class Blog extends Model
{
    use SoftDeletes;

    protected $table = 'blogs';

    /**
     * Casts
     */
    protected $casts = [
        'category_id' => 'int',
        'faqs' => 'array', // JSON به آرایه تبدیل می‌شود
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Fillable
     */
    protected $fillable = [
        'title',
        'category_id',
        'audio_path',
        'video_path',
        'image_path',
        'document_path',
        'slug',
        'short_des',
        'des',
        'seo_title',         // جدید
        'meta_description',  // جدید
        'faqs',              // جدید
        'height',
        'width',
        'meta'
    ];


    /**
     * Relationships
     */
    public function category()
    {
        return $this->belongsTo(Category::class)->withoutGlobalScope(SoftDeletingScope::class);
    }

    // Uncomment if you have tags
    // public function tags(): MorphToMany
    // {
    //     return $this->morphToMany(Tag::class, 'taggable');
    // }
}
