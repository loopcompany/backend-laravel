<?php
namespace App\Services;

use App\Models\{IncentivePlan, Technician, TechnicianTransaction};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TechnicianIncentiveService
{
    private function fail(string $message): never { throw ValidationException::withMessages(['incentive'=>$message]); }

    public function request(Technician $technician, int $id): IncentivePlan
    {
        return DB::transaction(function () use ($technician,$id) {
            // Lock order is shared with fulfill() to serialize financial effects.
            $technician = Technician::lockForUpdate()->findOrFail($technician->id);
            $plan = IncentivePlan::where('technician_id',$technician->id)->lockForUpdate()->findOrFail($id);
            if ($plan->redeemed_at || $plan->status === IncentivePlan::STATUS_USED) return $plan;
            if ($plan->status !== IncentivePlan::STATUS_PENDING || ($plan->end_at && $plan->end_at->lte(now()))) $this->fail('این طرح منقضی یا غیرفعال است.');
            if ($plan->benefit_type === 'legacy') $this->fail('نوع مزیت این طرح هنوز توسط مدیریت تعیین نشده است.');
            if (in_array($plan->benefit_type,['wallet','allowance','reward'],true)) return $this->credit($technician,$plan);
            if (!$plan->requested_at) $plan->update(['requested_at'=>now()]);
            return $plan;
        });
    }

    public function fulfill(int $id, int $adminId): IncentivePlan
    {
        $owner = IncentivePlan::findOrFail($id)->technician_id;
        return DB::transaction(function () use ($id,$owner,$adminId) {
            Technician::lockForUpdate()->findOrFail($owner);
            $plan = IncentivePlan::lockForUpdate()->findOrFail($id);
            if (!in_array($plan->benefit_type,['purchase_discount','company_item'],true) || !$plan->requested_at) $this->fail('ابتدا تکنسین باید درخواست استفاده ثبت کند.');
            if ($plan->redeemed_at) return $plan;
            if ($plan->status !== IncentivePlan::STATUS_PENDING || ($plan->end_at && $plan->end_at->lte(now()))) $this->fail('طرح منقضی یا غیرفعال است.');
            $plan->update(['status'=>IncentivePlan::STATUS_USED,'redeemed_at'=>now(),'fulfilled_by_admin_id'=>$adminId]);
            return $plan;
        });
    }

    private function credit(Technician $technician, IncentivePlan $plan): IncentivePlan
    {
        if ((int)$plan->amount < 1) $this->fail('مبلغ این طرح توسط مدیریت تکمیل نشده است.');
        $transaction = TechnicianTransaction::create([
            'technician_id'=>$technician->id,'price'=>$plan->amount,'commission'=>0,'referenceId'=>'INCENTIVE_'.$plan->id,
            'type'=>1,'status'=>100,'description'=>'طرح تشویقی: '.$plan->description,
        ]);
        $technician->increment('wallet',$plan->amount);
        $plan->update(['requested_at'=>now(),'redeemed_at'=>now(),'status'=>IncentivePlan::STATUS_USED,'technician_transaction_id'=>$transaction->id]);
        return $plan;
    }
}
