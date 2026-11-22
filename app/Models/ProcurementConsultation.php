<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProcurementConsultation extends Model {
 protected $guarded=[];
 protected $casts=['budget_rial'=>'integer','new_count'=>'integer','used_count'=>'integer'];
 public function user() { return $this->belongsTo(User::class); }
}
