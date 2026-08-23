<?php

namespace App\Services;

use App\Repositories\ChatRepository;
use Illuminate\Support\Facades\Log;

class ChatService
{
    public function __construct(
        protected ChatRepository $chatRepo
    ) {}

    /**
     * دریافت لیست چت‌های کاربر
     */
    public function fetchChats(int $userId): array
    {
        try {
            $chats = $this->chatRepo->getUserChats($userId);

            // اضافه کردن تعداد پیام‌های خوانده نشده به هر چت
            $chats = $chats->map(function ($chat) use ($userId) {
                $unreadCount = $this->chatRepo->getUnreadCountByTechnician($userId, $chat->technician_id);
                $chat->unread_count = $unreadCount;
                return $chat;
            });

            return [
                'success' => true,
                'data' => [
                    'chats' => $chats
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در دریافت لیست چت‌ها', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت لیست چت‌ها',
                'error_code' => 'FETCH_CHATS_ERROR'
            ];
        }
    }

    /**
     * دریافت پیام‌های بین کاربر و تکنسین
     */
    public function fetchMessages(int $userId, int $technicianId): array
    {
        try {
            // بررسی وجود سفارش فعال
            $hasActiveOrder = $this->chatRepo->hasActiveOrder($userId, $technicianId);

            // اگر سفارش فعال نداشت، چت بسته می‌شود
            if (!$hasActiveOrder) {
                $this->chatRepo->closeChat($userId, $technicianId);
                
                Log::info('چت بسته شد (بدون سفارش فعال)', [
                    'user_id' => $userId,
                    'technician_id' => $technicianId
                ]);
            }

            // دریافت پیام‌ها
            $messages = $this->chatRepo->getMessages($userId, $technicianId);

            return [
                'success' => true,
                'data' => [
                    'messages' => $messages,
                    'is_chat_open' => $hasActiveOrder
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در دریافت پیام‌ها', [
                'user_id' => $userId,
                'technician_id' => $technicianId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت پیام‌ها',
                'error_code' => 'FETCH_MESSAGES_ERROR'
            ];
        }
    }

    /**
     * ارسال پیام جدید
     */
    public function sendMessage(int $userId, int $technicianId, string $message): array
    {
        try {
            // بررسی وجود سفارش فعال
            $hasActiveOrder = $this->chatRepo->hasActiveOrder($userId, $technicianId);

            if (!$hasActiveOrder) {
                return [
                    'success' => false,
                    'message' => 'چت بسته شده است. سفارش فعالی وجود ندارد.',
                    'error_code' => 'CHAT_CLOSED'
                ];
            }

            // ارسال پیام
            $chat = $this->chatRepo->sendMessage([
                'user_id' => $userId,
                'technician_id' => $technicianId,
                'msg' => $message,
                'is_user' => 1, // پیام از طرف کاربر
                'is_read' => 0,
                'is_closed' => 0,
            ]);

            Log::info('پیام جدید ارسال شد', [
                'user_id' => $userId,
                'technician_id' => $technicianId,
                'message_id' => $chat->id
            ]);

            return [
                'success' => true,
                'message' => 'پیام با موفقیت ارسال شد.',
                'data' => [
                    'chat' => $chat
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در ارسال پیام', [
                'user_id' => $userId,
                'technician_id' => $technicianId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ارسال پیام',
                'error_code' => 'SEND_MESSAGE_ERROR'
            ];
        }
    }

    /**
     * علامت‌گذاری پیام‌ها به عنوان خوانده شده
     */
    public function markAsRead(int $userId, int $technicianId): array
    {
        try {
            $updatedCount = $this->chatRepo->markMessagesAsRead($userId, $technicianId);

            Log::info('پیام‌ها علامت‌گذاری شدند', [
                'user_id' => $userId,
                'technician_id' => $technicianId,
                'updated_count' => $updatedCount
            ]);

            return [
                'success' => true,
                'message' => 'پیام‌ها با موفقیت خوانده شدند.',
                'data' => [
                    'updated_count' => $updatedCount
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در علامت‌گذاری پیام‌ها', [
                'user_id' => $userId,
                'technician_id' => $technicianId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در علامت‌گذاری پیام‌ها',
                'error_code' => 'MARK_AS_READ_ERROR'
            ];
        }
    }

    /**
     * دریافت لیست چت‌های تکنسین
     */
    public function fetchTechnicianChats(int $technicianId): array
    {
        try {
            $chats = $this->chatRepo->getTechnicianChats($technicianId);

            // اضافه کردن تعداد پیام‌های خوانده نشده به هر چت
            $chats = $chats->map(function ($chat) use ($technicianId) {
                $unreadCount = $this->chatRepo->getUnreadCountByUser($technicianId, $chat->user_id);
                $chat->unread_count = $unreadCount;
                return $chat;
            });

            return [
                'success' => true,
                'data' => [
                    'chats' => $chats
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در دریافت لیست چت‌های تکنسین', [
                'technician_id' => $technicianId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت لیست چت‌ها',
                'error_code' => 'FETCH_CHATS_ERROR'
            ];
        }
    }

    /**
     * دریافت پیام‌های بین تکنسین و کاربر
     */
    public function fetchTechnicianMessages(int $technicianId, int $userId): array
    {
        try {
            // بررسی وجود سفارش فعال
            $hasActiveOrder = $this->chatRepo->hasActiveOrder($userId, $technicianId);

            // اگر سفارش فعال نداشت، چت بسته می‌شود
            if (!$hasActiveOrder) {
                $this->chatRepo->closeChat($userId, $technicianId);
                
                Log::info('چت بسته شد (بدون سفارش فعال)', [
                    'technician_id' => $technicianId,
                    'user_id' => $userId
                ]);
            }

            // دریافت پیام‌ها
            $messages = $this->chatRepo->getMessages($userId, $technicianId);

            return [
                'success' => true,
                'data' => [
                    'messages' => $messages,
                    'is_chat_open' => $hasActiveOrder
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در دریافت پیام‌های تکنسین', [
                'technician_id' => $technicianId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در دریافت پیام‌ها',
                'error_code' => 'FETCH_MESSAGES_ERROR'
            ];
        }
    }

    /**
     * ارسال پیام توسط تکنسین
     */
    public function sendTechnicianMessage(int $technicianId, int $userId, string $message): array
    {
        try {
            // بررسی وجود سفارش فعال
            $hasActiveOrder = $this->chatRepo->hasActiveOrder($userId, $technicianId);

            if (!$hasActiveOrder) {
                return [
                    'success' => false,
                    'message' => 'چت بسته شده است. سفارش فعالی وجود ندارد.',
                    'error_code' => 'CHAT_CLOSED'
                ];
            }

            // ارسال پیام
            $chat = $this->chatRepo->sendMessage([
                'user_id' => $userId,
                'technician_id' => $technicianId,
                'msg' => $message,
                'is_user' => 0, // پیام از طرف تکنسین
                'is_read' => 0,
                'is_closed' => 0,
            ]);

            Log::info('پیام تکنسین ارسال شد', [
                'technician_id' => $technicianId,
                'user_id' => $userId,
                'message_id' => $chat->id
            ]);

            return [
                'success' => true,
                'message' => 'پیام با موفقیت ارسال شد.',
                'data' => [
                    'chat' => $chat
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در ارسال پیام تکنسین', [
                'technician_id' => $technicianId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در ارسال پیام',
                'error_code' => 'SEND_MESSAGE_ERROR'
            ];
        }
    }

    /**
     * علامت‌گذاری پیام‌ها به عنوان خوانده شده توسط تکنسین
     */
    public function markAsReadByTechnician(int $technicianId, int $userId): array
    {
        try {
            $updatedCount = $this->chatRepo->markMessagesAsReadByTechnician($technicianId, $userId);

            Log::info('پیام‌ها توسط تکنسین علامت‌گذاری شدند', [
                'technician_id' => $technicianId,
                'user_id' => $userId,
                'updated_count' => $updatedCount
            ]);

            return [
                'success' => true,
                'message' => 'پیام‌ها با موفقیت خوانده شدند.',
                'data' => [
                    'updated_count' => $updatedCount
                ]
            ];

        } catch (\Exception $e) {
            Log::error('خطا در علامت‌گذاری پیام‌ها توسط تکنسین', [
                'technician_id' => $technicianId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'خطا در علامت‌گذاری پیام‌ها',
                'error_code' => 'MARK_AS_READ_ERROR'
            ];
        }
    }
}
