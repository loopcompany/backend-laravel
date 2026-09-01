<?php

namespace App\Services;

use App\Models\FirebaseDeviceToken;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FirebaseNotificationService
{
    private const OAUTH_TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const FCM_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    private ?string $cachedAccessToken = null;
    private int $cachedAccessTokenExpiresAt = 0;

    /**
     * Send a notification to every registered device of a user or technician.
     * Data values are cast to strings because FCM requires string data values.
     *
     * @return array{sent:int, failed:int, removed:int}
     */
    public function sendToUser(
        Authenticatable $notifiable,
        string $title,
        string $body,
        array $data = [],
        array $options = []
    ): array {
        $tokens = FirebaseDeviceToken::query()
            ->where('tokenable_type', $notifiable::class)
            ->where('tokenable_id', $notifiable->getAuthIdentifier())
            ->pluck('token')
            ->all();

        return $this->sendToTokens($tokens, $title, $body, $data, $options);
    }

    /**
     * Send one message per FCM token. FCM HTTP v1 does not accept a list of
     * registration tokens in a single message request.
     *
     * @param iterable<string> $tokens
     * @return array{sent:int, failed:int, removed:int}
     */
    public function sendToTokens(
        iterable $tokens,
        string $title,
        string $body,
        array $data = [],
        array $options = []
    ): array {
        $tokens = array_values(array_unique(array_filter(is_array($tokens) ? $tokens : iterator_to_array($tokens))));

        if ($tokens === []) {
            return ['sent' => 0, 'failed' => 0, 'removed' => 0];
        }

        $accessToken = $this->accessToken();
        $sent = 0;
        $failed = 0;
        $removed = 0;

        foreach ($tokens as $token) {
            $response = $this->client($accessToken)->post(
                $this->fcmUrl(),
                ['message' => $this->message($token, $title, $body, $data, $options)]
            );

            if ($response->successful()) {
                $sent++;
                continue;
            }

            $failed++;
            if ($this->isInvalidTokenResponse($response->status(), $response->json())) {
                $removed += FirebaseDeviceToken::where('token_hash', hash('sha256', $token))->delete();
            }

            Log::warning('Firebase notification delivery failed.', [
                'status' => $response->status(),
                'token_hash' => hash('sha256', $token),
                'response' => $response->json(),
            ]);
        }

        return compact('sent', 'failed', 'removed');
    }

    /**
     * Send to an FCM topic. Topic names must be controlled by the backend.
     *
     * @return array{message_name:string|null}
     */
    public function sendToTopic(
        string $topic,
        string $title,
        string $body,
        array $data = [],
        array $options = []
    ): array {
        $response = $this->client($this->accessToken())->post($this->fcmUrl(), [
            'message' => array_merge(
                $this->message(null, $title, $body, $data, $options),
                ['topic' => $topic]
            ),
        ]);

        $response->throw();

        return ['message_name' => $response->json('name')];
    }

    /** @return array<string, mixed> */
    protected function message(
        ?string $token,
        string $title,
        string $body,
        array $data,
        array $options
    ): array {
        $message = [
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => collect($data)->map(fn ($value) => (string) $value)->all(),
        ];

        if ($token !== null) {
            $message['token'] = $token;
        }

        if (!empty($options['android'])) {
            $message['android'] = $options['android'];
        }

        if (!empty($options['apns'])) {
            $message['apns'] = $options['apns'];
        }

        if (!empty($options['webpush'])) {
            $message['webpush'] = $options['webpush'];
        }

        return $message;
    }

    protected function client(string $accessToken): PendingRequest
    {
        return Http::withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('firebase.timeout', 15));
    }

    protected function fcmUrl(): string
    {
        $projectId = config('firebase.project_id');

        if (!$projectId) {
            throw new RuntimeException('FIREBASE_PROJECT_ID is not configured.');
        }

        return 'https://fcm.googleapis.com/v1/projects/' . rawurlencode($projectId) . '/messages:send';
    }

    protected function accessToken(): string
    {
        if (!config('firebase.enabled')) {
            throw new RuntimeException('Firebase notifications are disabled. Set FIREBASE_ENABLED=true.');
        }

        if ($this->cachedAccessToken && $this->cachedAccessTokenExpiresAt > time()) {
            return $this->cachedAccessToken;
        }

        $credentials = $this->credentials();
        $now = time();
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => self::FCM_SCOPE,
            'aud' => self::OAUTH_TOKEN_URL,
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));
        $unsignedJwt = $header . '.' . $claims;

        $signature = '';
        if (!openssl_sign($unsignedJwt, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Could not sign Firebase service account JWT.');
        }

        $response = Http::asForm()
            ->acceptJson()
            ->timeout((int) config('firebase.timeout', 15))
            ->post(self::OAUTH_TOKEN_URL, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $unsignedJwt . '.' . $this->base64UrlEncode($signature),
            ]);

        $response->throw();
        $token = $response->json('access_token');

        if (!is_string($token) || $token === '') {
            throw new RuntimeException('Firebase OAuth response did not contain an access token.');
        }

        $this->cachedAccessToken = $token;
        // Refresh a little before Google's one-hour expiry.
        $this->cachedAccessTokenExpiresAt = time() + 3300;

        return $token;
    }

    /** @return array{client_email:string,private_key:string} */
    protected function credentials(): array
    {
        $path = config('firebase.credentials_path');
        $json = config('firebase.credentials_json');

        if ($path && is_readable($path)) {
            $json = file_get_contents($path);
        }

        if (!$json) {
            throw new RuntimeException('Firebase credentials are not configured. Set FIREBASE_CREDENTIALS_PATH or FIREBASE_CREDENTIALS_JSON.');
        }

        $credentials = json_decode($json, true);
        if (!is_array($credentials) || empty($credentials['client_email']) || empty($credentials['private_key'])) {
            throw new RuntimeException('Firebase credentials JSON is invalid.');
        }

        return [
            'client_email' => $credentials['client_email'],
            'private_key' => str_replace('\\n', "\n", $credentials['private_key']),
        ];
    }

    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    protected function isInvalidTokenResponse(int $status, mixed $json): bool
    {
        $errorCode = data_get($json, 'error.details.0.errorCode');
        return $errorCode === 'UNREGISTERED' || ($status === 404 && $errorCode === null);
    }
}
