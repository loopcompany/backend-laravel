<?php

namespace App\Traits;

trait HasFilamentPermissions
{
    /**
     * Define the permission mappings for this resource
     */
    protected static function getPermissions(): array
    {
        return [
            'viewAny' => static::getViewPermission(),
            'create' => static::getCreatePermission(),
            'edit' => static::getEditPermission(),
            'delete' => static::getDeletePermission(),
        ];
    }

    /**
     * Override these methods in your resources
     */
    protected static function getViewPermission(): string
    {
        return 'view-dashboard';
    }

    protected static function getCreatePermission(): string
    {
        return 'view-dashboard';
    }

    protected static function getEditPermission(): string
    {
        return 'view-dashboard';
    }

    protected static function getDeletePermission(): string
    {
        return 'view-dashboard';
    }

    /**
     * Auto-generated permission methods
     */
    public static function canViewAny(): bool
    { 
        return auth('admin')->user()?->can(static::getViewPermission()) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth('admin')->user()?->can(static::getCreatePermission()) ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth('admin')->user()?->can(static::getEditPermission()) ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth('admin')->user()?->can(static::getDeletePermission()) ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth('admin')->user()?->can(static::getDeletePermission()) ?? false;
    }

    /**
     * Hide navigation if no view permission
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }
}