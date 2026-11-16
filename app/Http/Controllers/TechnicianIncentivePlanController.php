<?php
namespace App\Http\Controllers;
use App\Models\{IncentivePlan,Technician};
use App\Services\TechnicianIncentiveService;
use Illuminate\Http\Request;
class TechnicianIncentivePlanController extends Controller
{
    private function technician(Request $request): Technician
    {
        $actor=$request->user();
        abort_unless($actor instanceof Technician,403);
        return $actor;
    }
    private function data(IncentivePlan $plan): array
    {
        $expired=$plan->status !== IncentivePlan::STATUS_USED && $plan->end_at && $plan->end_at->lte(now());
        return array_merge($plan->only(['id','description','status','benefit_type','amount','benefit_location','requested_at','redeemed_at','created_at','updated_at','end_at']),[
            'benefit_label'=>$plan->benefit_type === 'legacy' ? 'طرح تشویقی' : (IncentivePlan::BENEFITS[$plan->benefit_type] ?? 'طرح تشویقی'),
            'benefit_code'=>$plan->requested_at ? $plan->benefit_code : null,
            'status_label'=>$expired ? 'Expired' : $plan->status_label,
            'can_request'=>!$expired && $plan->status === IncentivePlan::STATUS_PENDING && !$plan->requested_at && $plan->benefit_type !== 'legacy',
        ]);
    }
    public function index(Request $request)
    {
        $technician=$this->technician($request);
        $query=IncentivePlan::where('technician_id',$technician->id)->latest('id');
        if ($request->filled('status')) $query->where('status',$request->validate(['status'=>'integer|in:0,1,2'])['status']);
        $plans=$query->get();
        return response()->json(['success'=>true,'data'=>$plans->map(fn ($p)=>$this->data($p)), 'statistics'=>[
            'total'=>$plans->count(),'active'=>$plans->filter(fn ($p)=>$p->status===0 && (!$p->end_at || $p->end_at->gt(now())))->count(),
            'used'=>$plans->where('status',1)->count(),'expired'=>$plans->filter(fn ($p)=>$p->status!==1 && $p->end_at && $p->end_at->lte(now()))->count(),
        ]]);
    }
    public function show(Request $request,int $id)
    {
        $plan=IncentivePlan::where('technician_id',$this->technician($request)->id)->findOrFail($id);
        return response()->json(['success'=>true,'data'=>$this->data($plan)]);
    }
    public function redeem(Request $request,int $id,TechnicianIncentiveService $service)
    {
        $plan=$service->request($this->technician($request),$id);
        return response()->json(['success'=>true,'message'=>$plan->redeemed_at ? 'مزیت به کیف پول واریز شد.' : 'درخواست استفاده برای مدیریت ثبت شد.','data'=>$this->data($plan)]);
    }
}
