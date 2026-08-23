<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Class Contract
 * 
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string $pdf_path
 * @property bool $is_active
 * @property int|null $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property User|null $creator
 */
class Contract extends Model
{
    protected $table = 'contracts';

    protected $fillable = [
        'title',
        'description',
        'pdf_path',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_by' => 'int',
    ];

    /**
     * Accessors که باید به JSON اضافه شوند
     */
    protected $appends = [
        'creator_name',
    ];

    /**
     * رابطه با کاربر (ادمین) که قرارداد را ایجاد کرده
     * نکته: foreign key حذف شده تا مشکل با admins نداشته باشیم
     */
    // public function creator(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'created_by');
    // }

    /**
     * دریافت نام ایجادکننده از جدول users یا admins
     */
    public function getCreatorNameAttribute(): ?string
    {
        if (!$this->created_by) {
            return null;
        }

        // ابتدا در جدول users جستجو می‌کنیم
        $user = User::find($this->created_by);
        if ($user) {
            return $user->name;
        }

        // اگر نبود، در جدول admins جستجو می‌کنیم
        $admin = DB::table('admins')->where('id', $this->created_by)->first();
        if ($admin) {
            return $admin->name ?? 'ادمین';
        }

        return 'نامشخص';
    }

    /**
     * دریافت URL کامل فایل PDF
     */
    public function getPdfUrlAttribute(): string
    {
        return asset('storage/' . $this->pdf_path);
    }

    /**
     * scope برای قراردادهای فعال
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * scope برای مرتب‌سازی بر اساس جدیدترین
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
