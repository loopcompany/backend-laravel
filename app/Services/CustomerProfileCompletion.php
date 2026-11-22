<?php
namespace App\Services;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Support\Facades\DB;

class CustomerProfileCompletion
{
 public function missing(User $user, array $input = [], ?array $organizationInput = null): array
 {
  $data = array_replace($user->getAttributes(), $input);
  $required = ['name'=>'نام','last_name'=>'نام خانوادگی','melicode'=>'کد ملی','email'=>'ایمیل','birth_date'=>'تاریخ تولد','city'=>'شهر','postal_code'=>'کد پستی'];
  $errors=[];
  if ($user->isOrganization()) {
   $org=array_replace($user->organization?->getAttributes() ?? [], $organizationInput ?? []);
   foreach (['organization_name'=>'نام سازمان','manager_full_name'=>'نام مدیر','manager_national_code'=>'کد ملی مدیر','organization_phone'=>'تلفن محل کار','organization_address'=>'آدرس محل کار'] as $key=>$label) {
    if (blank($org[$key]??null)) $errors[$key]="{$label} تکمیل نشده است.";
   }
   if (mb_strlen(trim($org['organization_address']??''))<30) $errors['organization_address']='آدرس محل کار باید حداقل ۳۰ حرف داشته باشد.';
   unset($required['name'],$required['last_name'],$required['melicode']);
   foreach (['work_number'=>'پلاک محل کار','work_unit'=>'واحد محل کار','work_floor'=>'طبقه محل کار'] as $key=>$label) $required[$key]=$label;
  } else {
   $required += ['home_address'=>'آدرس منزل','home_number'=>'پلاک منزل','home_unit'=>'واحد منزل','home_floor'=>'طبقه منزل'];
   if (mb_strlen(trim($data['home_address']??''))<30) $errors['home_address']='آدرس منزل باید حداقل ۳۰ حرف داشته باشد.';
  }
  foreach($required as $key=>$label) if (!isset($data[$key]) || trim((string)$data[$key])==='') $errors[$key]="{$label} تکمیل نشده است.";
  if (blank($data['region_id']??null) && blank($data['region']??null)) $errors['region_id']='منطقه تکمیل نشده است.';
  return $errors;
 }
 public function finish(User $user): bool
 {
  $user->refresh();
  if ($this->missing($user)) return false;
  $first = false;
  DB::transaction(function() use($user,&$first) {
   $locked=User::lockForUpdate()->findOrFail($user->id);
   if (!$locked->profile_completed_at) { $locked->forceFill(['profile_completed_at'=>now(),'profile_completion_token_id'=>(int)$locked->tokens()->max('id')])->save(); $first=true; }
   foreach (['home','work'] as $kind) {
    $address=$kind==='work' && $locked->isOrganization() ? $locked->organization?->organization_address : $locked->{$kind.'_address'};
    if (blank($address) || blank($locked->{$kind.'_number'}) || blank($locked->{$kind.'_unit'}) || $locked->{$kind.'_floor'}===null) continue;
    // Address rows may belong to historical orders; never rewrite one by title.
    UserAddress::firstOrCreate(['user_id'=>$locked->id,'city'=>$locked->city,'address'=>$address,'number'=>$locked->{$kind.'_number'},'unit'=>$locked->{$kind.'_unit'},'floor'=>$locked->{$kind.'_floor'},'region_id'=>$locked->region_id,'region'=>$locked->region ?: $locked->userRegion?->code],[
      'title'=>$kind==='home'?'منزل':'محل کار',
      'fname'=>$locked->name ?: ($locked->organization?->manager_full_name ?? 'نماینده'), 'lname'=>$locked->last_name ?: 'سازمان',
      'mobile'=>$locked->phone,'telephone'=>$locked->phone_number,'city'=>$locked->city,'region'=>$locked->region ?: $locked->userRegion?->code,
      'region_id'=>$locked->region_id,'address'=>$address,'number'=>$locked->{$kind.'_number'},'unit'=>$locked->{$kind.'_unit'},'floor'=>$locked->{$kind.'_floor'},
    ]);
   }
  });
  if ($first && $user->code && !$user->is_special) {
   \App\Jobs\SendProfileCompletionCode::dispatch($user)->afterCommit();
   \App\Jobs\SendFirebaseNotificationJob::dispatch($user,'شناسه کاربری لوپ','شناسه کاربری شما: '.$user->code,['screen'=>'Profile'])->afterCommit();
  }
  return $first;
 }
 public function orderErrors(User $user): array
 {
  $errors=$this->missing($user);
  if (!$user->code) $errors['profile']='برای دریافت شناسه کاربری ابتدا اطلاعات حساب را کامل کنید.';
  $completed=$user->profile_completed_at;
  $token=$user->currentAccessToken();
  if (!$completed || ($token instanceof \Laravel\Sanctum\PersonalAccessToken && $token->getKey() && ($user->profile_completion_token_id !== null ? $token->id <= $user->profile_completion_token_id : (!$token->created_at || $token->created_at->lte(\Carbon\Carbon::parse($completed))))))
   $errors['profile_relogin']='پس از تکمیل اطلاعات، از حساب خارج شوید و دوباره وارد شوید تا ثبت سفارش فعال شود.';
  return $errors;
 }
}
