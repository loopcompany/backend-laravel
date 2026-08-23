<?php

namespace App\Policies;

use App\Models\Admin;
use Illuminate\Auth\Access\HandlesAuthorization;

class FilamentPolicy
{
    use HandlesAuthorization;

    /**
     * Check if admin can access any resource
     */
    public function viewAny(Admin $admin, string $permission): bool
    {
        return $admin->is_active && $admin->can($permission);
    }

    /**
     * Check if admin can create resource
     */
    public function create(Admin $admin, string $permission): bool
    {
        return $admin->is_active && $admin->can($permission);
    }

    /**
     * Check if admin can edit resource
     */
    public function update(Admin $admin, string $permission): bool
    {
        return $admin->is_active && $admin->can($permission);
    }

    /**
     * Check if admin can delete resource
     */
    public function delete(Admin $admin, string $permission): bool
    {
        return $admin->is_active && $admin->can($permission);
    }
}