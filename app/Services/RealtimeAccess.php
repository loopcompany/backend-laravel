<?php

namespace App\Services;

use App\Models\Admin;

class RealtimeAccess
{
    public const PERMISSIONS = [
        'orders' => ['view-orders'],
        'chat' => ['view-technician-chats'],
        'support' => ['view-user-chats', 'view-technician-chats', 'view-fault-reports', 'view-report-violations'],
        'wallet' => ['view-user', 'view-technicians', 'view-debt-requests', 'review-order-payments'],
        'account' => ['view-user', 'view-technicians'],
        'contracts' => ['view-contract-request', 'view-contracts'],
        'catalog' => ['view-categories', 'view-fields', 'view-terms', 'view-letter-rates'],
        'activity' => ['view-login-activities'],
        'requests' => ['view-manpower-requests', 'view-leave-requests', 'view-transfer-requests', 'view-education-requests', 'view-termination-requests'],
    ];

    public static function adminTopics(?Admin $admin): array
    {
        if (!$admin || $admin->is_active === false || !$admin->canAccessPanel(\Filament\Facades\Filament::getPanel('admin'))) return [];
        $topics = [];
        foreach (self::PERMISSIONS as $topic => $permissions) {
            foreach ($permissions as $permission) {
                if ($admin->isSuperAdmin() || $admin->can($permission)) {
                    $topics[] = $topic;
                    break;
                }
            }
        }
        return $topics;
    }
}
