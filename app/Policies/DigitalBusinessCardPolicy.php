<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\DigitalBusinessCard;

class DigitalBusinessCardPolicy
{
    public function create(Admin $admin): bool
    {
        return $admin->is_active && $admin->can('create-digital-business-cards');
    }

    public function view(Admin $admin, DigitalBusinessCard $card): bool
    {
        return $admin->is_active && $admin->can('view-digital-business-cards');
    }

    public function update(Admin $admin, DigitalBusinessCard $card): bool
    {
        return $admin->is_active && $admin->can('edit-digital-business-cards');
    }
}