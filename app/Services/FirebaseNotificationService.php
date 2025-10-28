<?php

namespace App\Services;

use App\Models\User;
use App\Jobs\SendFirebaseNotification;
use App\Jobs\SendBulkFirebaseNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    private $credentials;
    private $accessToken;

    public function __construct()
    {
        $firebaseKey = file_get_contents(storage_path('app/firebase/hr-system-46dda-firebase-adminsdk-fbsvc-4465c46c3e.json'));
        $this->credentials = json_decode($firebaseKey, true);
    }

    /**
     * إرسال إشعار Firebase باستخدام Queue (الطريقة المفضلة)
     */
    public function sendNotificationQueued(string $fcmToken, string $title, string $body, string $link = '/test', int $delay = 0)
    {
        try {
            // 🚀 Smart Check: تحقق سريع من FCM token
            if (empty($fcmToken)) {
                Log::info('FCM Token is empty - skipping queued Firebase notification');
                return [
                    'success' => true,
                    'message' => 'No FCM token - notification skipped',
                    'queued' => false
                ];
            }

            // إرسال الإشعار إلى الـ queue
            $job = SendFirebaseNotification::dispatch($fcmToken, $title, $body, $link)
                ->onQueue('notifications');

            if ($delay > 0) {
                $job->delay(now()->addSeconds($delay));
            }

            Log::info('Firebase notification queued successfully', [
                'token_preview' => substr($fcmToken, 0, 20) . '...',
                'title' => $title,
                'delay' => $delay
            ]);

            return [
                'success' => true,
                'message' => 'Notification queued successfully',
                'queued' => true
            ];

        } catch (\Exception $e) {
            Log::error('Error queuing Firebase notification', [
                'error' => $e->getMessage(),
                'title' => $title,
                'token_preview' => !empty($fcmToken) ? substr($fcmToken, 0, 20) . '...' : 'empty'
            ]);

            return [
                'success' => false,
                'message' => 'Failed to queue notification: ' . $e->getMessage(),
                'queued' => false
            ];
        }
    }

    /**
     * إرسال إشعار Firebase مباشرة (للحالات الطارئة فقط)
     */
    public function sendNotification(string $fcmToken, string $title, string $body, string $link = '/test')
    {
        try {
            // 🚀 Smart Check: تحقق سريع من FCM token
            if (empty($fcmToken)) {
                Log::info('FCM Token is empty - skipping Firebase notification');
                return [
                    'success' => true,
                    'message' => 'No FCM token - notification skipped'
                ];
            }

            Log::info('Starting to send notification', [
                'token' => substr($fcmToken, 0, 20) . '...',  // Log partial token for security
                'title' => $title,
                'body' => $body,
                'link' => $link,
                'token_length' => strlen($fcmToken),
                'has_credentials' => !empty($this->credentials)
            ]);

            if (!$this->accessToken) {
                Log::info('Getting new access token');
                $this->accessToken = $this->getAccessToken();
                Log::info('Access token obtained', ['token_length' => strlen($this->accessToken)]);
            } else {
                Log::info('Using existing access token', ['token_length' => strlen($this->accessToken)]);
            }

            $payload = [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body
                    ],
                    'data' => [
                        'url' => $link,
                        'title' => $title,
                        'body' => $body,
                        'click_action' => $link
                    ],
                    'webpush' => [
                        'headers' => [
                            'Urgency' => 'high'
                        ],
                        'fcm_options' => [
                            'link' => $link
                        ],
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                            'icon' => '/favicon.ico',
                            'click_action' => $link
                        ]
                    ]
                ]
            ];

            Log::info('Firebase payload', ['payload' => json_encode($payload, JSON_UNESCAPED_UNICODE)]);

            // Firebase API with proper timeout and retry settings
            $response = Http::timeout(30)
                ->retry(5, 1000) // 5 محاولات مع انتظار ثانية واحدة
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json'
                ])->post('https://fcm.googleapis.com/v1/projects/hr-system-46dda/messages:send', $payload);

            $success = $response->successful();
            $responseData = $response->json();
            $statusCode = $response->status();

            Log::info('FCM response received', [
                'success' => $success,
                'status' => $statusCode,
                'response_body' => $responseData,
                'response_headers' => $response->headers(),
                'title' => $title
            ]);

            if (!$success) {
                Log::error('Firebase notification failed', [
                    'status' => $statusCode,
                    'error' => $responseData,
                    'title' => $title,
                    'token_preview' => substr($fcmToken, 0, 20) . '...'
                ]);

                // If token is invalid, log specific error
                if ($statusCode === 400 && isset($responseData['error']['details'])) {
                    foreach ($responseData['error']['details'] as $detail) {
                        if (isset($detail['errorCode']) && $detail['errorCode'] === 'INVALID_ARGUMENT') {
                            Log::error('Invalid FCM token detected', [
                                'token_preview' => substr($fcmToken, 0, 20) . '...',
                                'detail' => $detail
                            ]);
                        }
                    }
                }
            } else {
                Log::info('Firebase notification sent successfully', [
                    'title' => $title,
                    'token_preview' => substr($fcmToken, 0, 20) . '...'
                ]);
            }

            return [
                'success' => $success,
                'message' => $responseData,
                'status_code' => $statusCode,
                'firebase_message_id' => $success && isset($responseData['name']) ? $responseData['name'] : null
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // خطأ اتصال محدد
            Log::error('Firebase connection timeout/error', [
                'error' => $e->getMessage(),
                'title' => $title
            ]);

            return [
                'success' => false,
                'message' => 'Connection timeout to Firebase'
            ];

        } catch (\Exception $e) {
            // أي خطأ آخر
            Log::error('Firebase general error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'title' => $title
            ]);

            return [
                'success' => false,
                'message' => 'Firebase notification failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * إرسال إشعارات Firebase للمدراء باستخدام Queue (الطريقة المفضلة)
     */
    public function sendNotificationToAdminsQueued(string $title, string $body, string $link = '/admin/dashboard', int $delay = 0)
    {
        try {
            Log::info('Starting to queue notifications to admins');

            // Get all admin users who have FCM tokens
            $admins = User::where('role', 'admin')
                         ->whereNotNull('fcm_token')
                         ->get();

            Log::info('Found admins with FCM tokens for queue', [
                'count' => $admins->count(),
                'admin_ids' => $admins->pluck('id')->toArray()
            ]);

            if ($admins->isEmpty()) {
                Log::info('No admins with FCM tokens found for queuing');
                return [
                    'success' => true,
                    'message' => 'No admins with FCM tokens',
                    'queued_count' => 0
                ];
            }

            // جمع جميع tokens
            $tokens = $admins->pluck('fcm_token')->filter()->toArray();

            // إرسال الإشعارات باستخدام bulk job
            SendBulkFirebaseNotification::dispatchInChunks($tokens, $title, $body, $link);

            Log::info('Firebase notifications to admins queued successfully', [
                'title' => $title,
                'tokens_count' => count($tokens)
            ]);

            return [
                'success' => true,
                'message' => 'Notifications queued successfully',
                'queued_count' => count($tokens)
            ];

        } catch (\Exception $e) {
            Log::error('Error queuing notifications to admins', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'title' => $title
            ]);

            return [
                'success' => false,
                'message' => 'Failed to queue notifications: ' . $e->getMessage(),
                'queued_count' => 0
            ];
        }
    }

    /**
     * إرسال إشعارات Firebase للمدراء مباشرة (للحالات الطارئة فقط)
     */
    public function sendNotificationToAdmins(string $title, string $body, string $link = '/admin/dashboard')
    {
        try {
            Log::info('Starting to send notifications to admins');

            // Get all admin users who have FCM tokens
            $admins = User::where('role', 'admin')
                         ->whereNotNull('fcm_token')
                         ->get();

            // Add this debug line
            Log::info('Admin users query', [
                'sql' => User::where('role', 'admin')->whereNotNull('fcm_token')->toSql(),
                'count' => $admins->count(),
                'admins' => $admins->toArray()
            ]);

            Log::info('Found admins with FCM tokens', [
                'count' => $admins->count(),
                'admin_ids' => $admins->pluck('id')->toArray()
            ]);

            $results = [];
            foreach ($admins as $admin) {
                try {
                    Log::info('Sending notification to admin', [
                        'admin_id' => $admin->id,
                        'fcm_token' => $admin->fcm_token
                    ]);

                    $result = $this->sendNotification(
                        $admin->fcm_token,
                        $title,
                        $body,
                        $link
                    );

                    Log::info('Notification sent to admin successfully', [
                        'admin_id' => $admin->id,
                        'result' => $result
                    ]);

                    $results[$admin->id] = $result;
                } catch (\Exception $e) {
                    Log::error("Failed to send notification to admin {$admin->id}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'fcm_token' => $admin->fcm_token
                    ]);
                    $results[$admin->id] = [
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }

            return [
                'success' => true,
                'results' => $results
            ];

        } catch (\Exception $e) {
            Log::error('Error sending notifications to admins', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * إرسال إشعارات Firebase للموظفين باستخدام Queue (الطريقة المفضلة)
     */
    public function sendNotificationToEmployeesQueued(string $title, string $body, string $link = '/employee/dashboard', int $delay = 0)
    {
        try {
            Log::info('Starting to queue notifications to employees');

            // Get all employee users who have FCM tokens
            $employees = User::where('role', 'employee')
                         ->whereNotNull('fcm_token')
                         ->get();

            Log::info('Found employees with FCM tokens for queue', [
                'count' => $employees->count(),
                'employee_ids' => $employees->pluck('id')->toArray()
            ]);

            if ($employees->isEmpty()) {
                Log::info('No employees with FCM tokens found for queuing');
                return [
                    'success' => true,
                    'message' => 'No employees with FCM tokens',
                    'queued_count' => 0
                ];
            }

            // جمع جميع tokens
            $tokens = $employees->pluck('fcm_token')->filter()->toArray();

            // إرسال الإشعارات باستخدام bulk job
            SendBulkFirebaseNotification::dispatchInChunks($tokens, $title, $body, $link);

            Log::info('Firebase notifications to employees queued successfully', [
                'title' => $title,
                'tokens_count' => count($tokens)
            ]);

            return [
                'success' => true,
                'message' => 'Notifications queued successfully',
                'queued_count' => count($tokens)
            ];

        } catch (\Exception $e) {
            Log::error('Error queuing notifications to employees', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'title' => $title
            ]);

            return [
                'success' => false,
                'message' => 'Failed to queue notifications: ' . $e->getMessage(),
                'queued_count' => 0
            ];
        }
    }

    /**
     * إرسال إشعارات Firebase للموظفين مباشرة (للحالات الطارئة فقط)
     */
    public function sendNotificationToEmployees(string $title, string $body, string $link = '/employee/dashboard')
    {
        try {
            Log::info('Starting to send notifications to employees');

            // Get all employee users who have FCM tokens
            $employees = User::where('role', 'employee')
                         ->whereNotNull('fcm_token')
                         ->get();

            Log::info('Found employees with FCM tokens', [
                'count' => $employees->count(),
                'employee_ids' => $employees->pluck('id')->toArray()
            ]);

            $results = [];
            foreach ($employees as $employee) {
                try {
                    Log::info('Sending notification to employee', [
                        'employee_id' => $employee->id,
                        'fcm_token' => $employee->fcm_token
                    ]);

                    $result = $this->sendNotification(
                        $employee->fcm_token,
                        $title,
                        $body,
                        $link
                    );

                    Log::info('Notification sent to employee successfully', [
                        'employee_id' => $employee->id,
                        'result' => $result
                    ]);

                    $results[$employee->id] = $result;
                } catch (\Exception $e) {
                    Log::error("Failed to send notification to employee {$employee->id}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'fcm_token' => $employee->fcm_token
                    ]);
                    $results[$employee->id] = [
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }

            return [
                'success' => true,
                'results' => $results
            ];

        } catch (\Exception $e) {
            Log::error('Error sending notifications to employees', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    private function getAccessToken()
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
                Log::error('Failed to get access token', [
                    'response' => $response->json()
                ]);
                throw new \Exception('Failed to get access token: ' . $response->body());
            }

            return $response->json()['access_token'];

        } catch (\Exception $e) {
            Log::error('Error getting access token', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function generateJWT($payload, $privateKey)
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'RS256']);
        $payload = json_encode($payload);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signatureInput = $base64UrlHeader . "." . $base64UrlPayload;
        openssl_sign($signatureInput, $signature, $privateKey, 'SHA256');
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
}
