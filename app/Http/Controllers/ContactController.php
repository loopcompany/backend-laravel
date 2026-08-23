<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * دریافت اطلاعات تماس تلفنی
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPhoneContact(Request $request): JsonResponse
    {
        try {
            $contact = Contact::where('type', 'phone')->first();
            $contact_urgent = Contact::where('type', 'urgent')->first();

            if (!$contact) {
                return response()->json([
                    'success' => false,
                    'message' => 'اطلاعات تماس تلفنی یافت نشد.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'contact' => $contact,
                    'contact_urgent' => $contact_urgent
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error fetching phone contact: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'خطا در دریافت اطلاعات تماس.'
            ], 500);
        }
    }
}
