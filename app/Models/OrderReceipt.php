<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Immutable receipt values; review and custody transitions go through OrderSettlementService. */
class OrderReceipt extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['payload_hash', 'receipt_path', 'review_note'];
    protected $casts = ['amount' => 'integer', 'reviewed_at' => 'datetime', 'returned_at' => 'datetime'];

    public function order() { return $this->belongsTo(Order::class); }
    public function technician() { return $this->belongsTo(Technician::class); }

    public const METHODS = ['cash' => 'نقدی', 'card_to_card' => 'کارت به کارت', 'barter' => 'تهاتر کالا'];
    public const COMPANY_METHODS = self::METHODS + ['sheba' => 'شبا', 'bank_transfer' => 'انتقال بانکی'];
    public const STATUSES = ['pending' => 'در انتظار تأیید ادمین', 'approved' => 'تأیید شده', 'rejected' => 'رد شده'];
}
