<?php
namespace Tests\Feature;
use App\Models\{LocalizationLanguage,LocalizationMessage};
use App\Services\LocalizationCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
class DynamicLocalizationTest extends TestCase {
 use RefreshDatabase;
 public function test_public_resources_use_editable_persian_fallback_and_never_mix_application_namespaces():void {
  LocalizationLanguage::create(['code'=>'fr','name'=>'فرانسوی','native_name'=>'Français','direction'=>'ltr','is_enabled'=>true,'sort_order'=>4]);
  LocalizationMessage::create(['scope'=>'customer','message_key'=>'Hello','source_fa'=>'درود','translations'=>['fr'=>'Bonjour']]);
  LocalizationMessage::create(['scope'=>'customer','message_key'=>'Missing','source_fa'=>'فارسی معیار','translations'=>[]]);
  LocalizationMessage::create(['scope'=>'technician','message_key'=>'private-scope','source_fa'=>'فقط تکنسین','translations'=>[]]);
  $this->getJson('/api/localization/bootstrap?app=customer&locale=fr')->assertOk()->assertJsonPath('base_locale','fa')->assertJsonPath('resources.fr.translation.Hello','Bonjour')->assertJsonPath('resources.fa.translation.Missing','فارسی معیار')->assertJsonMissingPath('resources.fa.translation.private-scope')->assertJsonPath('realtime.channels.0','localization');
  LocalizationMessage::where('message_key','Hello')->first()->update(['source_fa'=>'سلام تازه']);
  $this->getJson('/api/localization/bootstrap?app=customer&locale=fa')->assertOk()->assertJsonPath('resources.fa.translation.Hello','سلام تازه');
 }
 public function test_json_import_is_atomic_and_preserves_interpolation_variables():void {
  $catalog=app(LocalizationCatalog::class);
  $catalog->import('customer','fa',['orders'=>['count'=>'تعداد {{count}} سفارش']]);
  $this->assertSame('تعداد {{count}} سفارش',$catalog->resources('customer','fa')['orders.count']);
  try{$catalog->import('customer','en',['orders'=>['count'=>'Orders without variable']]);$this->fail('Missing placeholder accepted');}catch(ValidationException){}
  $this->assertArrayNotHasKey('orders.count',$catalog->resources('customer','en'));
  $catalog->import('customer','en',['orders'=>['count'=>'{{count}} orders']]);
  $this->assertSame('{{count}} orders',$catalog->resources('customer','en')['orders.count']);
 }
 public function test_website_text_and_direction_follow_an_enabled_language_and_persian_edits():void {
  LocalizationLanguage::create(['code'=>'fr','name'=>'فرانسوی','native_name'=>'Français','direction'=>'ltr','is_enabled'=>true]);
  LocalizationMessage::create(['scope'=>'website','message_key'=>'تماس با ما','source_fa'=>'ارتباط با ما','translations'=>['fr'=>'Contactez-nous']]);
  app()->setLocale('fa');$this->assertSame('ارتباط با ما',site_text('تماس با ما'));
  app()->setLocale('fr');$this->assertSame('Contactez-nous',site_text('تماس با ما'));
  $this->get('/?lang=fr')->assertOk()->assertSee('lang="fr"',false)->assertSee('dir="ltr"',false)->assertSee('<body dir="ltr">',false)->assertSee('Français');
 }
 public function test_persian_language_cannot_be_disabled_or_deleted():void {
  $language=LocalizationLanguage::where('code','fa')->firstOrFail();
  try{$language->update(['is_enabled'=>false]);$this->fail('Base disabled');}catch(ValidationException){}
  $language->refresh();$this->assertTrue($language->is_enabled);
  $this->assertThrows(fn()=>$language->delete(),ValidationException::class);
 }
 public function test_shipped_seed_is_idempotent_and_does_not_overwrite_admin_edits():void {
  $this->artisan('localization:seed')->assertSuccessful();
  $count=LocalizationMessage::count();$this->assertGreaterThan(3000,$count);
  $row=LocalizationMessage::firstOrFail();$row->update(['source_fa'=>'متن ویرایش‌شده مدیر','translations'=>[]]);
  $this->artisan('localization:seed')->assertSuccessful();
  $this->assertSame($count,LocalizationMessage::count());$this->assertSame('متن ویرایش‌شده مدیر',$row->fresh()->source_fa);
 }
 public function test_invalid_app_and_disabled_language_do_not_expose_unknown_catalogs():void {
  $this->getJson('/api/localization/bootstrap?app=admin')->assertUnprocessable();
  LocalizationLanguage::create(['code'=>'ar','name'=>'عربی','native_name'=>'العربية','direction'=>'rtl','is_enabled'=>false]);
  $this->getJson('/api/localization/bootstrap?app=customer&locale=ar')->assertOk()->assertJsonPath('locale','fa')->assertJsonMissingPath('resources.ar');
 }
 public function test_region_language_tags_are_canonical_and_admin_permissions_are_independent():void {
  $language=LocalizationLanguage::create(['code'=>'fr-ca','name'=>'فرانسوی کانادا','native_name'=>'Français canadien','direction'=>'ltr','is_enabled'=>true]);
  $this->assertSame('fr-CA',$language->code);
  $definitions=\App\Services\AdminPermissionCatalog::definitions();
  foreach(['view-localization-languages','create-localization-languages','edit-localization-languages','delete-localization-languages','view-translations','create-translations','edit-translations','delete-translations'] as $permission)$this->assertArrayHasKey($permission,$definitions);
  \App\Services\AdminPermissionCatalog::sync();
  $admin=\App\Models\Admin::create(['name'=>'Translator','email'=>'translator@example.test','password'=>'test-password','is_active'=>true]);
  $this->actingAs($admin,'admin');
  $this->assertFalse(\App\Filament\Resources\LocalizationMessageResource::canViewAny());
  $admin->givePermissionTo('view-translations');
  $this->assertTrue(\App\Filament\Resources\LocalizationMessageResource::canViewAny());
  $this->assertFalse(\App\Filament\Resources\LocalizationMessageResource::canCreate());
  $this->assertFalse(\App\Filament\Resources\LocalizationMessageResource::canEdit(null));
 }
 public function test_api_messages_follow_the_current_header_without_translating_customer_content():void {
  LocalizationLanguage::create(['code'=>'fr','name'=>'فرانسوی','native_name'=>'Français','direction'=>'ltr','is_enabled'=>true]);
  LocalizationMessage::create(['scope'=>'customer','message_key'=>'سلام','source_fa'=>'درود معیار','translations'=>['fr'=>'Bonjour']]);
  $request=\Illuminate\Http\Request::create('/api/profile','GET',[],[],[],['HTTP_ACCEPT_LANGUAGE'=>'fr']);
  $response=app(\App\Http\Middleware\SetApiLocale::class)->handle($request,fn()=>response()->json(['message'=>'سلام','errors'=>['name'=>['سلام']],'data'=>['name'=>'سلام','unchanged'=>(object)[]]]));
  $data=$response->getData();$this->assertSame('Bonjour',$data->message);$this->assertSame('Bonjour',$data->errors->name[0]);$this->assertSame('سلام',$data->data->name);$this->assertInstanceOf(\stdClass::class,$data->data->unchanged);$this->assertSame('fr',$response->headers->get('Content-Language'));
 }
}
