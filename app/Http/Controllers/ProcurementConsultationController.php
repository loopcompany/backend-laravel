<?php
namespace App\Http\Controllers;
use App\Models\{User,UserAddress,ProcurementConsultation};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class ProcurementConsultationController extends Controller {
 public const ITEMS=['case_standard'=>'کیس معمولی','case_professional'=>'کیس حرفه‌ای','mini_case'=>'مینی کیس','laptop'=>'لپ تاپ'];
 public function store(Request $request) {
  abort_unless($request->user() instanceof User,403);
  $data=$request->validate([
   'client_request_id'=>['required','uuid'],'item_id'=>['required',Rule::in(array_keys(self::ITEMS))],
   'new_count'=>['required','integer','min:0','max:10000'],'used_count'=>['required','integer','min:0','max:10000'],
   'budget_rial'=>['required','integer','min:1','max:9007199254740990'],'description'=>['required','string','min:3','max:3000'],
   'address_id'=>['nullable','integer',Rule::exists('user_addresses','id')->where('user_id',$request->user()->id)],
  ]);
  if ($data['new_count']+$data['used_count']===0) throw \Illuminate\Validation\ValidationException::withMessages(['new_count'=>'حداقل یک کالا انتخاب کنید.']);
  $address=UserAddress::where('user_id',$request->user()->id)->when($data['address_id']??null,fn($q,$id)=>$q->whereKey($id))->first();
  $addressText=$address ? implode('، ',array_filter([$address->city,$address->address,'پلاک '.$address->number,'واحد '.$address->unit])) : ($request->user()->isOrganization() ? $request->user()->organization?->organization_address : $request->user()->home_address);
  if (blank($addressText)) throw \Illuminate\Validation\ValidationException::withMessages(['address_id'=>'ابتدا آدرس حساب کاربری یا آدرس منتخب را تکمیل کنید.']);
  $result=DB::transaction(function() use($request,$data,$addressText) {
   // Serializes retries for one account, including two simultaneous first submissions.
   User::lockForUpdate()->findOrFail($request->user()->id);
   $record=ProcurementConsultation::firstOrCreate(['user_id'=>$request->user()->id,'client_request_id'=>$data['client_request_id']],
    array_diff_key($data,['address_id'=>true])+['title'=>self::ITEMS[$data['item_id']],'address'=>$addressText]);
   if ($record->wasRecentlyCreated) \App\Jobs\SendConsultationConfirmation::dispatch($record)->afterCommit();
   return $record;
  });
  return response()->json(['success'=>true,'message'=>'درخواست مشاوره شما ثبت شد. کارشناسان لوپ برای راهنمایی شما در تأمین کالا / محصول مورد نظر شما، با شما تماس خواهند گرفت.','data'=>['id'=>$result->id]],201);
 }
}
