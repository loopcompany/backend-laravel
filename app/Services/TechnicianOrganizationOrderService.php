<?php

namespace App\Services;

use App\Repositories\TechnicianOrganizationOrderRepository;
use Illuminate\Support\Facades\Log;

class TechnicianOrganizationOrderService
{
    public function __construct(
        private TechnicianOrganizationOrderRepository $repository
    ) {}

    /**
     * دریافت لیست سازمان‌ها با تعداد سفارشات برای تکنسین
     *
     * @param int $technicianId
     * @return array
     */
    public function getOrganizationsWithOrderCount(int $technicianId): array
    {
        try {
            $organizations = $this->repository->getOrganizationsWithOrderCount($technicianId);
            $totalOrganizationOrders = $this->repository->getTotalOrganizationOrdersCount($technicianId);

            return [
                'success' => true,
                'data' => [
                    'organizations' => $organizations,
                    'total_organization_orders' => $totalOrganizationOrders,
                    'total_organizations' => $organizations->count()
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error in getOrganizationsWithOrderCount service: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در دریافت لیست سازمان‌ها.',
                'error_code' => 'FETCH_ORGANIZATIONS_ERROR'
            ];
        }
    }

    /**
     * دریافت سفارشات یک سازمان خاص برای تکنسین
     *
     * @param int $technicianId
     * @param int $organizationId
     * @return array
     */
    public function getOrganizationOrders(int $technicianId, int $organizationId): array
    {
        try {
            $orders = $this->repository->getOrganizationOrders($technicianId, $organizationId);

            if ($orders->isEmpty()) {
                return [
                    'success' => true,
                    'message' => 'هیچ سفارشی برای این سازمان یافت نشد.',
                    'data' => [
                        'orders' => [],
                        'total' => 0
                    ]
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'orders' => $orders,
                    'total' => $orders->count()
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error in getOrganizationOrders service: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'خطا در دریافت سفارشات سازمان.',
                'error_code' => 'FETCH_ORGANIZATION_ORDERS_ERROR'
            ];
        }
    }
}
