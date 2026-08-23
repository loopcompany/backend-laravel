<?php

namespace App\Http\Controllers;

use App\Http\Requests\TechnicianFetchMessagesRequest;
use App\Http\Requests\TechnicianSendMessageRequest;
use App\Services\ChatService;
use Illuminate\Http\Request;

class TechnicianChatController extends Controller
{
    public function __construct(
        protected ChatService $chatService
    ) {}

    /**
     * دریافت لیست چت‌های تکنسین
     */
    public function fetchChats(Request $request)
    {
        $result = $this->chatService->fetchTechnicianChats($request->user()->id);

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
     * دریافت پیام‌های بین تکنسین و کاربر
     */
    public function fetchMessages(TechnicianFetchMessagesRequest $request)
    {
        $result = $this->chatService->fetchTechnicianMessages(
            $request->user()->id,
            $request->validated('user_id')
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
     * ارسال پیام توسط تکنسین
     */
    public function sendMessage(TechnicianSendMessageRequest $request)
    {
        $result = $this->chatService->sendTechnicianMessage(
            $request->user()->id,
            $request->validated('user_id'),
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
    public function markAsRead(TechnicianFetchMessagesRequest $request)
    {
        $result = $this->chatService->markAsReadByTechnician(
            $request->user()->id,
            $request->validated('user_id')
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
