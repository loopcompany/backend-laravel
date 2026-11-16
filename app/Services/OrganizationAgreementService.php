<?php
namespace App\Services;

use App\Models\OrganizationContractRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrganizationAgreementService
{
    public const MODES=['monthly_short'=>'ماهانه','yearly_long'=>'سالانه','project'=>'پروژه‌ای'];

    public function request(User $user, array $modes, ?int $previousId=null): OrganizationContractRequest
    {
        abort_unless($user->isOrganization() && $user->organization,403);
        if (!$modes || array_diff($modes,array_keys(self::MODES))) {
            throw ValidationException::withMessages(['delivery_modes'=>'توافق‌نامه فقط برای خدمات ماهانه، سالانه و پروژه‌ای فعال است.']);
        }
        return DB::transaction(function () use ($user,$modes,$previousId) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (OrganizationContractRequest::where('user_id',$user->id)->whereIn('status',['0','1'])->exists()) {
                throw ValidationException::withMessages(['request'=>'درخواست باز دارید؛ ادامهٔ آن را از سوابق توافق‌نامه‌ها پیگیری کنید.']);
            }
            if ($previousId) OrganizationContractRequest::where('user_id',$user->id)->findOrFail($previousId);
            return OrganizationContractRequest::create([
                'user_id'=>$user->id,'organization_id'=>$user->organization->id,'status'=>'0',
                'delivery_modes'=>array_values(array_unique($modes)),'previous_request_id'=>$previousId,
            ]);
        });
    }

    public function payload(OrganizationContractRequest $request): array
    {
        return [
            'id'=>$request->id,'status'=>(string)$request->status,'created_at'=>$request->created_at,
            'delivery_modes'=>$request->delivery_modes??[], 'previous_request_id'=>$request->previous_request_id,
            'information'=>$request->information,'need_docs'=>$request->need_docs,
            'gallery'=>$request->gallery->map(fn ($file) => ['id'=>$file->id,'url'=>asset('storage/'.$file->file_path)])->values(),
            'draft_url'=>$request->contract_file_path?asset('storage/'.$request->contract_file_path):null,
            'final_url'=>(string)$request->status==='3' && $request->signed_contract_file_path?asset('storage/'.$request->signed_contract_file_path):null,
            'draft_published_at'=>$request->uploaded_by_admin_at,
            'final_published_at'=>$request->final_published_at,
            'reject_reason'=>$request->reject_contract_reason?:$request->reject_reason,
        ];
    }
}
