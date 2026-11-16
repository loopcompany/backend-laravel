<?php
namespace App\Services;

use App\Models\Admin;
use Spatie\Permission\Models\{Role,Permission};

class AdminDelegation
{
    public static function root(): bool { return auth('admin')->user()?->hasRole('super-admin') ?? false; }
    public static function can(string $permission): bool { return self::root() || (auth('admin')->user()?->can($permission) ?? false); }
    public static function permissionIds(): array
    {
        $actor=auth('admin')->user();
        if (!$actor || !self::can('assign-permissions')) return [];
        return (self::root() ? Permission::where('guard_name','admin')->get() : $actor->getAllPermissions()->where('guard_name','admin'))->pluck('id')->map(fn ($id)=>(int)$id)->all();
    }
    public static function roleIds(): array
    {
        if (!self::can('assign-roles')) return [];
        $held=auth('admin')->user()?->getAllPermissions()->pluck('id')->map(fn ($id)=>(int)$id)->all() ?? [];
        return Role::where('guard_name','admin')->with('permissions')->get()->filter(fn ($role)=>self::root() || ($role->name!=='super-admin' && !array_diff($role->permissions->pluck('id')->all(),$held)))->pluck('id')->map(fn ($id)=>(int)$id)->all();
    }
    public static function editable(Role $role): bool
    {
        if (self::root()) return true;
        $held=auth('admin')->user()?->getAllPermissions()->pluck('id')->all() ?? [];
        return $role->name!=='super-admin' && !array_diff($role->permissions->pluck('id')->all(),$held);
    }
    public static function checkIds(array $selected, array $allowed): bool { return !array_diff(array_map('intval',$selected),$allowed); }
    public static function editableAdmin(Admin $target): bool
    {
        if (self::root()) return true;
        if ($target->hasRole('super-admin')) return false;
        $held=auth('admin')->user()?->getAllPermissions()->pluck('id')->all() ?? [];
        return !array_diff($target->getAllPermissions()->pluck('id')->all(),$held);
    }
}
