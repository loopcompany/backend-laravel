<?php

namespace App\Repositories;

use App\Models\Chat;
use Illuminate\Support\Facades\DB;

class ChatRepository
{
    /**
     * دریافت لیست چت‌های کاربر (آخرین پیام هر تکنسین)
     */
    public function getUserChats(int $userId)
    {
        // پیدا کردن آخرین پیام هر تکنسین
        $lastMessageIds = Chat::where('user_id', $userId)
            ->select(DB::raw('MAX(id) as id'))
            ->groupBy('technician_id')
            ->pluck('id');

        return Chat::with('technician')
            ->whereIn('id', $lastMessageIds)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * شمارش پیام‌های خوانده نشده از یک تکنسین
     */
    public function getUnreadCountByTechnician(int $userId, int $technicianId): int
    {
        return Chat::where([
            'user_id' => $userId,
            'technician_id' => $technicianId,
            'is_read' => 0,
            'is_user' => 0 // پیام‌های تکنسین
        ])->count();
    }

    /**
     * دریافت تمام پیام‌های بین کاربر و تکنسین
     */
    public function getMessages(int $userId, int $technicianId)
    {
        return Chat::where([
            'user_id' => $userId,
            'technician_id' => $technicianId
        ])->orderBy('id', 'desc')->get();
    }

    /**
     * بستن چت (is_closed = 1)
     */
    public function closeChat(int $userId, int $technicianId): int
    {
        return Chat::where([
            'user_id' => $userId,
            'technician_id' => $technicianId
        ])->update(['is_closed' => 1]);
    }

    /**
     * ارسال پیام جدید
     */
    public function sendMessage(array $data): Chat
    {
        return Chat::create($data);
    }

    /**
     * علامت‌گذاری پیام‌ها به عنوان خوانده شده
     */
    public function markMessagesAsRead(int $userId, int $technicianId): int
    {
        return Chat::where([
            'user_id' => $userId,
            'technician_id' => $technicianId,
            'is_read' => 0,
            'is_user' => 0 // فقط پیام‌های تکنسین
        ])->update(['is_read' => 1]);
    }

    /**
     * بررسی وجود سفارش فعال بین کاربر و تکنسین
     */
    public function hasActiveOrder(int $userId, int $technicianId): bool
    {
        return DB::table('orders')
            ->where('user_id', $userId)
            ->where('technician_id', $technicianId)
            ->whereIn('status', [0, 1]) // 0: pending, 1: processing
            ->exists();
    }

    /**
     * دریافت آخرین پیام بین کاربر و تکنسین
     */
    public function getLastMessage(int $userId, int $technicianId): ?Chat
    {
        return Chat::where([
            'user_id' => $userId,
            'technician_id' => $technicianId
        ])->orderBy('id', 'desc')->first();
    }

    /**
     * دریافت لیست چت‌های تکنسین (آخرین پیام هر کاربر)
     */
    public function getTechnicianChats(int $technicianId)
    {
        // پیدا کردن آخرین پیام هر کاربر
        $lastMessageIds = Chat::where('technician_id', $technicianId)
            ->select(DB::raw('MAX(id) as id'))
            ->groupBy('user_id')
            ->pluck('id');

        return Chat::with('user')
            ->whereIn('id', $lastMessageIds)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * شمارش پیام‌های خوانده نشده از یک کاربر برای تکنسین
     */
    public function getUnreadCountByUser(int $technicianId, int $userId): int
    {
        return Chat::where([
            'technician_id' => $technicianId,
            'user_id' => $userId,
            'is_read' => 0,
            'is_user' => 1 // پیام‌های کاربر
        ])->count();
    }

    /**
     * علامت‌گذاری پیام‌ها به عنوان خوانده شده توسط تکنسین
     */
    public function markMessagesAsReadByTechnician(int $technicianId, int $userId): int
    {
        return Chat::where([
            'technician_id' => $technicianId,
            'user_id' => $userId,
            'is_read' => 0,
            'is_user' => 1 // فقط پیام‌های کاربر
        ])->update(['is_read' => 1]);
    }
}
