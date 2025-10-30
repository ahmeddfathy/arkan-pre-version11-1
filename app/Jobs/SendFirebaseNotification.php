<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class SendFirebaseNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 180, 300]; // 1min, 3min, 5min
    public $timeout = 30;

    protected $fcmToken;
    protected $title;
    protected $body;
    protected $link;
    protected $credentials;
    protected $accessToken;

    /**
     * Create a new job instance.
     */
    public function __construct(string $fcmToken, string $title, string $body, string $link = '/test')
    {
        $this->fcmToken = $fcmToken;
        $this->title = $title;
        $this->body = $body;
        $this->link = $link;

        // تحديد الـ queue connection والاسم للإشعارات
        $this->onQueue('notifications');

        // تحميل credentials
        $firebaseKey = file_get_contents(storage_path('app/firebase/hr-system-46dda-firebase-adminsdk-fbsvc-4465c46c3e.json'));
        $this->credentials = json_decode($firebaseKey, true);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting Firebase notification job', [
            'title' => $this->title,
            'token_preview' => substr($this->fcmToken, 0, 20) . '...',
            'attempt' => $this->attempts(),
            'link' => $this->link,
            'credentials_loaded' => !empty($this->credentials),
            'has_private_key' => isset($this->credentials['private_key']),
            'has_client_email' => isset($this->credentials['client_email'])
        ]);

        // 🚀 Smart Check: تحقق سريع من FCM token
        if (empty($this->fcmToken)) {
            Log::info('Skipping Firebase notification job - FCM Token is empty', [
                'title' => $this->title
            ]);
            return; // تم إنهاء المهمة بنجاح
        }

        // التحقق من تحميل الـ credentials
        if (empty($this->credentials)) {
            Log::error('Firebase credentials not loaded', [
                'title' => $this->title
            ]);
            throw new Exception('Firebase credentials not loaded');
        }

        try {
            $this->sendFirebaseNotification();

            Log::info('Firebase notification job completed successfully', [
                'title' => $this->title,
                'token_preview' => substr($this->fcmToken, 0, 20) . '...',
                'attempt' => $this->attempts()
            ]);

        } catch (Exception $e) {
            // ✅ تحقق إذا كان الخطأ بسبب توكن غلط (404 أو 400) - نتجاهله ونعدي عادي
            if ($e instanceof \Illuminate\Http\Client\RequestException) {
                $statusCode = $e->response ? $e->response->status() : null;

                if (in_array($statusCode, [404, 400])) {
                    Log::warning('Firebase notification skipped due to invalid/expired token', [
                        'title' => $this->title,
                        'token_preview' => substr($this->fcmToken, 0, 20) . '...',
                        'status_code' => $statusCode,
                        'error_message' => $e->getMessage()
                    ]);
                    // لا نرمي exception، نخلي الـ job ينجح
                    return;
                }
            }

            Log::error('Firebase notification job failed', [
                'title' => $this->title,
                'token_preview' => substr($this->fcmToken, 0, 20) . '...',
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // إذا فشل آخر محاولة، نسجل الفشل النهائي
            if ($this->attempts() >= $this->tries) {
                Log::error('Firebase notification job permanently failed after all retries', [
                    'title' => $this->title,
                    'token_preview' => substr($this->fcmToken, 0, 20) . '...',
                    'total_attempts' => $this->tries
                ]);
            }

            throw $e; // إعادة رمي الخطأ ليتم إعادة المحاولة
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Firebase notification job permanently failed', [
            'title' => $this->title,
            'token_preview' => substr($this->fcmToken, 0, 20) . '...',
            'error' => $exception->getMessage(),
            'total_attempts' => $this->tries
        ]);
    }

    /**
     * إرسال إشعار Firebase
     */
    private function sendFirebaseNotification(): void
    {
        // الحصول على access token
        if (!$this->accessToken) {
            Log::info('Getting new access token for Firebase job');
            $this->accessToken = $this->getAccessToken();
            Log::info('Access token obtained for Firebase job', ['token_length' => strlen($this->accessToken)]);
        }

        $payload = [
            'message' => [
                'token' => $this->fcmToken,
                'notification' => [
                    'title' => $this->title,
                    'body' => $this->body
                ],
                'data' => [
                    'url' => $this->link,
                    'title' => $this->title,
                    'body' => $this->body,
                    'click_action' => $this->link
                ],
                'webpush' => [
                    'headers' => [
                        'Urgency' => 'high'
                    ],
                    'fcm_options' => [
                        'link' => $this->link
                    ],
                    'notification' => [
                        'title' => $this->title,
                        'body' => $this->body,
                        'icon' => '/favicon.ico',
                        'click_action' => $this->link
                    ]
                ]
            ]
        ];

        Log::info('Firebase job payload', ['payload' => json_encode($payload, JSON_UNESCAPED_UNICODE)]);

        // Firebase API with proper timeout and retry settings
        // ⚠️ مهم: لا نرمي exception تلقائياً، سنتعامل مع الأخطاء يدوياً
        $response = Http::timeout(30)
            ->retry(5, 1000, function($exception, $request) {
                // نعيد المحاولة فقط في حالة network errors، لا في حالة 404
                return !($exception instanceof \Illuminate\Http\Client\RequestException && $exception->response->status() === 404);
            })
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->post('https://fcm.googleapis.com/v1/projects/hr-system-46dda/messages:send', $payload);

        $success = $response->successful();
        $responseData = $response->json();
        $statusCode = $response->status();

        Log::info('FCM response received in job', [
            'success' => $success,
            'status' => $statusCode,
            'response_body' => $responseData,
            'title' => $this->title
        ]);

        if (!$success) {
            // ✅ أخطاء بسبب التوكن (404, 400) - نتجاهلها ونعدي عادي
            if (in_array($statusCode, [404, 400])) {
                $errorMessage = 'FCM token issue detected - skipping notification';

                if ($statusCode === 404) {
                    $errorMessage = 'FCM token not found (expired or unregistered)';
                } elseif ($statusCode === 400 && isset($responseData['error']['message'])) {
                    if (str_contains($responseData['error']['message'], 'not found') ||
                        str_contains($responseData['error']['message'], 'invalid')) {
                        $errorMessage = 'FCM token is invalid or expired';
                    }
                }

                Log::warning($errorMessage . ' - skipping', [
                    'title' => $this->title,
                    'token_preview' => substr($this->fcmToken, 0, 20) . '...',
                    'status' => $statusCode,
                    'error_message' => $responseData['error']['message'] ?? 'Unknown error'
                ]);
                // نخلي الـ job ينجح عشان ميعيدش المحاولة
                return;
            }

            Log::error('Firebase notification failed in job', [
                'status' => $statusCode,
                'error' => $responseData,
                'title' => $this->title,
                'token_preview' => substr($this->fcmToken, 0, 20) . '...'
            ]);

            // If token is invalid, log specific error
            if ($statusCode === 400 && isset($responseData['error']['details'])) {
                foreach ($responseData['error']['details'] as $detail) {
                    if (isset($detail['errorCode']) && $detail['errorCode'] === 'INVALID_ARGUMENT') {
                        Log::error('Invalid FCM token detected in job', [
                            'token_preview' => substr($this->fcmToken, 0, 20) . '...',
                            'detail' => $detail
                        ]);
                    }
                }
            }

            throw new Exception('Firebase notification failed: ' . json_encode($responseData));
        }

        Log::info('Firebase notification sent successfully in job', [
            'title' => $this->title,
            'token_preview' => substr($this->fcmToken, 0, 20) . '...'
        ]);
    }

    /**
     * الحصول على access token
     */
    private function getAccessToken(): string
    {
        try {
            $now = time();
            $payload = [
                'iss' => $this->credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $this->credentials['token_uri'],
                'exp' => $now + 3600,
                'iat' => $now
            ];

            $jwt = $this->generateJWT($payload, $this->credentials['private_key']);

            // Proper timeout for Firebase access token request
            $response = Http::timeout(15)->asForm()->post($this->credentials['token_uri'], [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]);

            if (!$response->successful()) {
                Log::error('Failed to get access token in Firebase job', [
                    'response' => $response->json()
                ]);
                throw new Exception('Failed to get access token: ' . $response->body());
            }

            return $response->json()['access_token'];

        } catch (Exception $e) {
            Log::error('Error getting access token in Firebase job', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * إنشاء JWT token
     */
    private function generateJWT(array $payload, string $privateKey): string
    {
        try {
            $header = json_encode(['typ' => 'JWT', 'alg' => 'RS256']);
            $payload = json_encode($payload);

            $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
            $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

            $signatureInput = $base64UrlHeader . "." . $base64UrlPayload;

            // التحقق من openssl_sign
            $signResult = openssl_sign($signatureInput, $signature, $privateKey, 'SHA256');

            if ($signResult === false) {
                $opensslError = openssl_error_string();
                Log::error('openssl_sign failed in Firebase job', [
                    'openssl_error' => $opensslError
                ]);
                throw new Exception('Failed to sign JWT: ' . $opensslError);
            }

            $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

            Log::info('JWT generated successfully in Firebase job');

            return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

        } catch (Exception $e) {
            Log::error('Error in generateJWT', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
