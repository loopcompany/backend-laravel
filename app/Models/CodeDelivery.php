<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodeDelivery extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['sent_at' => 'datetime'];
    public function user() { return $this->belongsTo(User::class); }
    public static function statuses(): array
    {
        return ['queued'=>'در صف','processing'=>'در حال ارسال','sent'=>'پذیرفته‌شده توسط سرویس پیامک','failed'=>'ناموفق','disabled'=>'پیامک غیرفعال','recorded'=>'ارتباط تلفنی/حضوری ثبت شد'];
    }
}
