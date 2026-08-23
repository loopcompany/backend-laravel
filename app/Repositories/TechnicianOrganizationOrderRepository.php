<?php

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TechnicianOrganizationOrderRepository
{
    /**
     * دریافت لیست سازمان‌هایی که به تکنسین سفارش داده‌اند با تعداد سفارشات
     *
     * @param int $technicianId
     * @return \Illuminate\Support\Collection
     */
    public function getOrganizationsWithOrderCount(int $technicianId)
    {
        try {
            return DB::table('orders')
                ->join('users', 'orders.user_id', '=', 'users.id')
                ->join('organizations', 'users.id', '=', 'organizations.user_id')
                ->where('orders.technician_id', $technicianId)
                ->where('users.account_type','!=', 'individual')
                ->select(
                    'organizations.id as organization_id',
                    'organizations.organization_name',
                    'organizations.organization_code',
                    'organizations.organization_phone',
                    'organizations.profile_image',
                    DB::raw('COUNT(orders.id) as total_orders'),
                    DB::raw('SUM(CASE WHEN orders.status IN (0, 1) THEN 1 ELSE 0 END) as active_orders_count')
                )
                ->groupBy(
                    'organizations.id',
                    'organizations.organization_name',
                    'organizations.organization_code',
                    'organizations.organization_phone',
                    'organizations.profile_image'
                )
                ->orderBy('total_orders', 'desc')
                ->get();

        } catch (\Exception $e) {
            Log::error('Error fetching organizations with order count: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * دریافت سفارشات یک سازمان برای تکنسین
     *
     * @param int $technicianId
     * @param int $organizationId
     * @return \Illuminate\Support\Collection
     */
    public function getOrganizationOrders(int $technicianId, int $organizationId)
    {
        try {
            $orders = Order::with(['user', 'user_address', 'category'])
                ->whereHas('user', function ($query) use ($organizationId) {
                    $query->where('account_type', '!=', 'individual')
                          ->whereHas('organization', function ($orgQuery) use ($organizationId) {
                              $orgQuery->where('id', $organizationId);
                          });
                })
                ->where('technician_id', $technicianId)
                ->orderBy('created_at', 'desc')
                ->get();

            // اضافه کردن فیلدهای service_schedule به هر سفارش (حتی اگر null باشند)
            return $orders->map(function ($order) {
                $orderData = $order->toArray();
                
                // اطمینان از وجود فیلدهای service_schedule در response
                $orderData['service_schedule_type'] = $order->service_schedule_type;
                $orderData['service_schedule_long_duration'] = $order->service_schedule_long_duration;
                $orderData['service_schedule_long_date'] = $order->service_schedule_long_date;
                $orderData['service_schedule_long_time'] = $order->service_schedule_long_time;
                $orderData['service_schedule_long_file'] = $order->service_schedule_long_file;
                $orderData['service_schedule_short_date'] = $order->service_schedule_short_date;
                $orderData['service_schedule_short_time'] = $order->service_schedule_short_time;
                $orderData['service_schedule_short_file'] = $order->service_schedule_short_file;
                
                return $orderData;
            });

        } catch (\Exception $e) {
            Log::error('Error fetching organization orders: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * دریافت تعداد کل سفارشات سازمانی تکنسین
     *
     * @param int $technicianId
     * @return int
     */
    public function getTotalOrganizationOrdersCount(int $technicianId): int
    {
        try {
            return Order::whereHas('user', function ($query) {
                $query->where('account_type', '!=','individual');
            })
            ->where('technician_id', $technicianId)
            ->count();

        } catch (\Exception $e) {
            Log::error('Error counting total organization orders: ' . $e->getMessage());
            return 0;
        }
    }
}
