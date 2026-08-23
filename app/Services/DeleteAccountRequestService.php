<?php

namespace App\Services;

use App\Models\DeleteAccountRequest;
use Exception;
use Illuminate\Support\Facades\Log;

class DeleteAccountRequestService
{
    /**
     * Submit a new delete account request
     */
    public function submitDeleteAccountRequest(array $data): array
    {
        try {
            // Additional validation if needed
            $this->validateRequestData($data);

            // Store the delete account request in database
            $request = DeleteAccountRequest::create([
                'phone' => $data['phone'],
                'title' => $data['title'],
                'description' => $data['description'],
            ]);

            Log::info('Delete account request submitted successfully', [
                'request_id' => $request->id,
                'phone' => $data['phone'],
                'title' => $data['title']
            ]);

            return [
                'success' => true,
                'message' => 'Your account deletion request has been submitted successfully. Our team will review your request and contact you shortly.',
                'request_id' => $request->id,
            ];

        } catch (Exception $e) {
            Log::error('Failed to submit delete account request', [
                'error' => $e->getMessage(),
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'An error occurred while submitting your request. Please try again.',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Additional validation for delete account request data
     */
    protected function validateRequestData(array $data): void
    {
        // Additional business logic validations
        if (empty(trim($data['title']))) {
            throw new Exception('Title cannot be empty');
        }

        if (strlen($data['description']) < 10) {
            throw new Exception('Description must be at least 10 characters');
        }

        // Check for spam (optional)
        if ($this->isSpamRequest($data['description'])) {
            throw new Exception('Your request has been identified as spam');
        }
    }

    /**
     * Check if request is spam
     */
    protected function isSpamRequest(string $description): bool
    {
        // Simple algorithm for spam detection
        $spamKeywords = ['spam', 'promotion', 'click here', 'buy now'];

        foreach ($spamKeywords as $keyword) {
            if (stripos($description, $keyword) != false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get delete account request statistics
     */
    public function getRequestStats(): array
    {
        return [
            'total_requests' => DeleteAccountRequest::count(),
            'today_requests' => DeleteAccountRequest::whereDate('created_at', today())->count(),
        ];
    }

    /**
     * Get recent delete account requests
     */
    public function getRecentRequests(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return DeleteAccountRequest::latest()->limit($limit)->get();
    }
}
