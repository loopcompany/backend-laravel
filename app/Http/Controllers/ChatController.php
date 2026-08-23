<?php

namespace App\Http\Controllers;

use App\Http\Requests\FetchMessagesRequest;
use App\Http\Requests\SendMessageRequest;
use App\Services\ChatService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        protected ChatService $chatService
    ) {}

    /**
     * دریافت لیست چت‌های کاربر
     */
    public function fetchChats(Request $request)
    {
        $result = $this->chatService->fetchChats($request->user()->id);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? null
            ], 500);
        }

        return response()->json([
            'success' => true,
            'chats' => $result['data']['chats']
        ], 200);
    }

    /**
     * دریافت پیام‌های بین کاربر و تکنسین
     */
    public function fetchMessages(FetchMessagesRequest $request)
    {
        $result = $this->chatService->fetchMessages(
            $request->user()->id,
            $request->validated('technician_id')
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? null
            ], 500);
        }

        return response()->json([
            'success' => true,
            'messages' => $result['data']['messages'],
            'is_chat_open' => $result['data']['is_chat_open']
        ], 200);
    }

    /**
     * ارسال پیام جدید
     */
    public function sendMessage(SendMessageRequest $request)
    {
        $result = $this->chatService->sendMessage(
            $request->user()->id,
            $request->validated('technician_id'),
            $request->validated('message')
        );

        if (!$result['success']) {
            $statusCode = isset($result['error_code']) && $result['error_code'] === 'CHAT_CLOSED' ? 403 : 500;
            
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? null
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'chat' => $result['data']['chat']
        ], 201);
    }

    /**
     * علامت‌گذاری پیام‌ها به عنوان خوانده شده
     */
    public function markAsRead(FetchMessagesRequest $request)
    {
        $result = $this->chatService->markAsRead(
            $request->user()->id,
            $request->validated('technician_id')
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'] ?? null
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'updated_count' => $result['data']['updated_count']
        ], 200);
    }
}
