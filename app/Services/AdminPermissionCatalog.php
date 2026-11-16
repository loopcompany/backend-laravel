<?php
namespace App\Services;

use Spatie\Permission\Models\{Permission,Role};

class AdminPermissionCatalog
{
    public static function definitions(): array
    {
        $result=['access-admin-panel'=>'دسترسی به پنل ادمین','manage-admins'=>'مدیریت کارکنان و مدیران','view-dashboard'=>'مشاهده داشبورد','view-map'=>'مشاهده محدوده سرویس','edit-map'=>'ویرایش محدوده سرویس',
            'review-order-payments'=>'بررسی وصول پرداخت‌ها توسط حسابداری','assign-paid-orders'=>'ارجاع سفارش‌های پرداخت‌شده توسط مدیر تکنسین',
            'view-roles'=>'مشاهده سمت‌ها','create-roles'=>'ایجاد سمت','edit-roles'=>'ویرایش سمت','delete-roles'=>'حذف سمت','assign-roles'=>'واگذاری سمت به ادمین',
            'view-permissions'=>'مشاهده مجوزها','create-permissions'=>'ساخت مجوز','edit-permissions'=>'ویرایش عنوان مجوز','delete-permissions'=>'حذف مجوز سفارشی','assign-permissions'=>'واگذاری مجوز به سمت‌ها'];
        foreach (glob(app_path('Filament/Resources/*Resource.php')) as $file) {
            $class='App\\Filament\\Resources\\'.basename($file,'.php');
            if (!class_exists($class)) continue;
            foreach (['View'=>'مشاهده','Create'=>'ایجاد','Edit'=>'ویرایش','Delete'=>'حذف'] as $action=>$label) {
                if (!method_exists($class,'get'.$action.'Permission')) continue;
                $method=new \ReflectionMethod($class,'get'.$action.'Permission');
                if (!$method->isStatic() || $method->getNumberOfRequiredParameters()) continue;
                $permission=$method->invoke(null);
                if (is_string($permission) && $permission && !isset($result[$permission])) $result[$permission]=$label.' '.$class::getPluralModelLabel();
            }
        }
        return $result;
    }
    public static function sync(): int
    {
        $count=0;
        foreach (self::definitions() as $name=>$label) {
            $permission=Permission::firstOrCreate(['name'=>$name,'guard_name'=>'admin'],['fa_name'=>$label]);
            if ($permission->wasRecentlyCreated) $count++;
            elseif (!$permission->fa_name) $permission->update(['fa_name'=>$label]);
        }
        foreach (Role::where('name','super-admin')->where('guard_name','admin')->get() as $role) $role->givePermissionTo(Permission::where('guard_name','admin')->get());
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        return $count;
    }
}
