<?php

namespace App\Http\Controllers;

use App\Models\TechnicianTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TechnicianTicketController extends Controller
{
    /**
     * Get list of all tickets (conversation between technician and admin)
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $technician = auth('sanctum')->user();

        if (!$technician instanceof \App\Models\Technician) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 401);
        }

        try {
            $tickets = TechnicianTicket::where('technician_id', $technician->id)
                ->orderByDesc('id')
                ->get()
                ->map(function ($ticket) {
                    return [
                        'id' => $ticket->id,
                        'message' => $ticket->message,
                        'role' => $ticket->role,
                        'role_label' => $ticket->role_label,
                        'is_read' => $ticket->is_read,
                        'is_mine' => $ticket->role === 'technician',
                        'created_at' => $ticket->created_at->toISOString(),
                        'updated_at' => $ticket->updated_at->toISOString(),
                    ];
                });

            // Mark all admin messages as read
            TechnicianTicket::where('technician_id', $technician->id)
                ->where('role', 'admin')
                ->where('is_read', 0)
                ->update(['is_read' => 1]);

            return response()->json([
                'success' => true,
                'message' => 'لیست پیام‌ها با موفقیت دریافت شد.',
                'data' => $tickets,
                'total' => $tickets->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت لیست پیام‌ها.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send a new message to admin
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $technician = auth('sanctum')->user();

        if (!$technician instanceof \App\Models\Technician) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 401);
        }

        // Validation
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:5000',
        ], [
            'message.required' => 'پیام الزامی است.',
            'message.string' => 'پیام باید متن باشد.',
            'message.max' => 'پیام نباید بیشتر از 5000 کاراکتر باشد.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در اعتبارسنجی.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $ticket = TechnicianTicket::create([
                'technician_id' => $technician->id,
                'message' => $request->message,
                'role' => 'technician',
                'is_read' => 0, // Admin hasn't read it yet
            ]);

            return response()->json([
                'success' => true,
                'message' => 'پیام شما با موفقیت ارسال شد.',
                'data' => [
                    'id' => $ticket->id,
                    'message' => $ticket->message,
                    'role' => $ticket->role,
                    'role_label' => $ticket->role_label,
                    'is_read' => $ticket->is_read,
                    'is_mine' => true,
                    'created_at' => $ticket->created_at->toISOString(),
                    'updated_at' => $ticket->updated_at->toISOString(),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال پیام.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get count of unread messages from admin
     * 
     * @return JsonResponse
     */
    public function unreadCount(): JsonResponse
    {
        $technician = auth('sanctum')->user();

        if (!$technician instanceof \App\Models\Technician) {
            return response()->json([
                'success' => false,
                'message' => 'دسترسی غیرمجاز. این API فقط برای تکنسین‌ها است.',
            ], 401);
        }

        try {
            $unreadCount = TechnicianTicket::where('technician_id', $technician->id)
                ->where('role', 'admin')
                ->where('is_read', 0)
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'تعداد پیام‌های خوانده نشده با موفقیت دریافت شد.',
                'data' => [
                    'unread_count' => $unreadCount,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت تعداد پیام‌های خوانده نشده.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
