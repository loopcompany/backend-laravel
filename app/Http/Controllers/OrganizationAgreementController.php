<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PdfDocument;
use App\Models\OrganizationContractRequest;
use App\Services\OrganizationAgreementService;
use App\Services\AdminPanelNotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrganizationAgreementController extends Controller
{
    private function actor(Request $request): User
    {
        $user=$request->user();
        abort_unless($user instanceof User && $user->isOrganization() && $user->organization,403);
        return $user;
    }

    public function index(Request $request, OrganizationAgreementService $service)
    {
        $user=$this->actor($request);
        $documents=PdfDocument::latest('id')->first();
        return response()->json(['success'=>true,'data'=>[
            'requests'=>OrganizationContractRequest::where('user_id',$user->id)->with('gallery')->latest('id')->get()->map(fn ($row) => $service->payload($row)),
            'documents'=>[
                'terms'=>$documents?->organ_term?asset('storage/'.$documents->organ_term):asset('assets/guid/organization-service-terms.pdf'),
                'assessment'=>$documents?->organization_assessment?asset('storage/'.$documents->organization_assessment):asset('assets/guid/organization-assessment.pdf'),
                'general_draft'=>$documents?->organization_agreement_draft?asset('storage/'.$documents->organization_agreement_draft):asset('assets/guid/organization-agreement-draft.pdf'),
            ],
        ]])->header('Cache-Control','no-store');
    }

    public function store(Request $request, OrganizationAgreementService $service, AdminPanelNotificationService $notifications)
    {
        $user=$this->actor($request);
        $data=$request->validate(['delivery_modes'=>['required','array','min:1'],
            'delivery_modes.*'=>['required',Rule::in(array_keys(OrganizationAgreementService::MODES))],
            'previous_request_id'=>['nullable','integer']]);
        $record=$service->request($user,$data['delivery_modes'],$data['previous_request_id']??null);
        $notifications->sendToAdmins('درخواست توافق‌نامه سازمانی/شرکتی','درخواست شماره '.$record->id.' برای خدمات '.implode('، ',array_map(fn ($mode) => OrganizationAgreementService::MODES[$mode],$record->delivery_modes)).' ثبت شد.');
        return response()->json(['success'=>true,'message'=>'درخواست توافق‌نامه ثبت شد؛ پس از ارسال ادمین، پیش‌نویس فعال می‌شود.','data'=>$service->payload($record)],201);
    }
}
