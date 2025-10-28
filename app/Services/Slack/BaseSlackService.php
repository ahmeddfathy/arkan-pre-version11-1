<?php

namespace App\Services\Slack;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Jobs\SendSlackNotification;

abstract class BaseSlackService
{
    protected $botToken;
    protected $baseUrl = 'https://slack.com/api/';

    public function __construct()
    {
        // Read directly from .env file to avoid config cache issues
        $this->botToken = $this->getSlackBotToken();
    }

    /**
     * Get Slack bot token with fallback options
     */
    private function getSlackBotToken(): ?string
    {
        // First try env() function (reads directly from .env)
        $token = env('SLACK_BOT_TOKEN');

        if (empty($token)) {
            // Fallback to config if env is empty
            $token = config('services.slack.bot_token');
        }

        if (empty($token)) {
            // Last resort: read .env file directly
            $envPath = base_path('.env');
            if (file_exists($envPath)) {
                $envContent = file_get_contents($envPath);
                if (preg_match('/SLACK_BOT_TOKEN=(.+)/m', $envContent, $matches)) {
                    $token = trim($matches[1], '"\'');
                }
            }
        }

        return $token;
    }

    /**
     * إرسال رسالة للمستخدم من خلال Queue (الطريقة المفضلة)
     */
    protected function queueSlackMessage(User $user, array $message, string $context = 'Slack Notification'): bool
    {
        // التحقق من وجود Slack ID والبوت توكن
        if (empty($user->slack_user_id)) {
            Log::info('Skipping Slack notification queue - User has no Slack ID', [
                'user_id' => $user->id,
                'context' => $context
            ]);
            $this->setNotificationStatus(true, 'المستخدم ليس لديه Slack ID - تم التجاهل');
            return true;
        }

        if (!$this->botToken) {
            Log::warning('Skipping Slack notification queue - No bot token configured', [
                'user_id' => $user->id,
                'context' => $context
            ]);
            $this->setNotificationStatus(true, 'Slack غير مكون - تم التجاهل');
            return true;
        }

        try {
            // إرسال المهمة للـ Queue
            SendSlackNotification::dispatch($user, $message, $context);

            Log::info('Slack notification queued successfully', [
                'user_id' => $user->id,
                'context' => $context
            ]);

            $this->setNotificationStatus(true, 'تم إضافة الإشعار للطابور بنجاح');
            return true;

        } catch (\Exception $e) {
            Log::error('Failed to queue Slack notification', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'context' => $context
            ]);
            $this->setNotificationStatus(false, 'فشل في إضافة الإشعار للطابور');
            return false;
        }
    }

    /**
     * إرسال رسالة مباشرة للمستخدم (للحالات الطارئة أو عند عدم توفر Queue)
     */
    protected function sendDirectMessage(User $user, array $message): bool
    {
        Log::info('Starting Slack direct message send', [
            'user_id' => $user->id,
            'slack_user_id' => $user->slack_user_id,
            'has_bot_token' => !empty($this->botToken),
            'bot_token_preview' => $this->botToken ? substr($this->botToken, 0, 10) . '...' : 'none',
            'bot_token_length' => $this->botToken ? strlen($this->botToken) : 0
        ]);

        // 🚀 Smart Check: تحقق سريع قبل أي محاولة إرسال
        if (empty($user->slack_user_id)) {
            Log::info('Skipping Slack notification - User has no Slack ID', [
                'user_id' => $user->id
            ]);
            $this->setNotificationStatus(true, 'المستخدم ليس لديه Slack ID - تم التجاهل');
            return true; // نعتبرها نجحت عشان ميحصلش إزعاج للمستخدم
        }

        if (!$this->botToken) {
            Log::warning('Skipping Slack notification - No bot token configured', [
                'user_id' => $user->id
            ]);
            $this->setNotificationStatus(true, 'Slack غير مكون - تم التجاهل');
            return true;
        }

        try {
            Log::info('Opening Slack conversation', [
                'user_id' => $user->id,
                'slack_user_id' => $user->slack_user_id
            ]);

            // تحسين الـ timeout والـ retry مع exponential backoff
            $dmResponse = Http::timeout(15)
                ->retry(3, function ($attempt, $exception) {
                    // Exponential backoff: 100ms, 200ms, 400ms
                    return pow(2, $attempt - 1) * 100;
                })
                ->withToken($this->botToken)
                ->post($this->baseUrl . 'conversations.open', [
                    'users' => $user->slack_user_id,
                ]);

            Log::info('Slack conversation response', [
                'successful' => $dmResponse->successful(),
                'ok' => $dmResponse->json('ok'),
                'status' => $dmResponse->status(),
                'error' => $dmResponse->json('error')
            ]);

            if (!$dmResponse->successful() || !$dmResponse->json('ok')) {
                Log::warning('Failed to open Slack conversation', [
                    'user_id' => $user->id,
                    'slack_user_id' => $user->slack_user_id,
                    'status' => $dmResponse->status(),
                    'error' => $dmResponse->json('error')
                ]);
                $this->setNotificationStatus(false, 'فشل فتح قناة المحادثة');
                return false;
            }

            $channelId = $dmResponse->json('channel.id');
            Log::info('Slack conversation opened successfully', [
                'channel_id' => $channelId,
                'user_id' => $user->id
            ]);

            // إرسال الرسالة مع retry محسن
            $response = Http::timeout(15)
                ->retry(3, function ($attempt, $exception) {
                    // Exponential backoff للرسائل أيضاً
                    return pow(2, $attempt - 1) * 150;
                })
                ->withToken($this->botToken)
                ->post($this->baseUrl . 'chat.postMessage', [
                    'channel' => $channelId,
                    'text' => $message['text'],
                    'blocks' => $message['blocks']
                ]);

            Log::info('Slack message response', [
                'successful' => $response->successful(),
                'ok' => $response->json('ok'),
                'status' => $response->status(),
                'error' => $response->json('error'),
                'user_id' => $user->id
            ]);

            $success = $response->successful() && $response->json('ok');
            $this->setNotificationStatus($success, $success ? 'تم الإرسال بنجاح' : 'فشل إرسال الرسالة');

            return $success;

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // خطأ اتصال (timeout, network)
            Log::warning('Slack connection timeout/error - continuing', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);
            $this->setNotificationStatus(false, 'انتهت مهلة الاتصال مع Slack');
            return false;
        } catch (\Exception $e) {
            // أي خطأ آخر
            Log::warning('Slack general exception - continuing', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);
            $this->setNotificationStatus(false, 'خطأ في إرسال إشعار Slack');
            return false;
        }
    }

    protected function setNotificationStatus(bool $success, string $message = '')
    {
        $context = session()->get('slack_context', 'Slack');
        session()->flash('slack_notification', [
            'success' => $success,
            'message' => $message,
            'context' => $context,
            'timestamp' => now()->timestamp
        ]);
    }

    protected function setNotificationContext(string $context)
    {
        session()->put('slack_context', $context);
    }

    /**
     * إرسال رسالة Slack مع خيار الـ Queue (الطريقة الموصى بها)
     */
    protected function sendSlackNotification(User $user, array $message, string $context = 'Slack Notification', bool $useQueue = true): bool
    {
        if ($useQueue) {
            return $this->queueSlackMessage($user, $message, $context);
        } else {
            return $this->sendDirectMessage($user, $message);
        }
    }

    /**
     * إرسال إشعار Slack بإعدادات افتراضية محسنة
     * Helper method لتبسيط الاستخدام
     */
    protected function notify(User $user, array $message, string $context = 'إشعار Slack'): bool
    {
        return $this->sendSlackNotification($user, $message, $context, true);
    }

    /**
     * إرسال إشعار Slack مباشر (بدون queue) - للحالات الطارئة فقط
     */
    protected function notifyImmediate(User $user, array $message, string $context = 'إشعار Slack عاجل'): bool
    {
        return $this->sendSlackNotification($user, $message, $context, false);
    }

    /**
     * بناء زر الإجراء
     */
    protected function buildActionButton(string $text, string $url, string $style = 'primary'): array
    {
        return [
            'type' => 'button',
            'text' => [
                'type' => 'plain_text',
                'text' => $text
            ],
            'url' => $url,
            'style' => $style
        ];
    }

    /**
     * بناء قسم المعلومات
     */
    protected function buildInfoSection(array $fields): array
    {
        return [
            'type' => 'section',
            'fields' => array_map(function($field) {
                return [
                    'type' => 'mrkdwn',
                    'text' => $field
                ];
            }, $fields)
        ];
    }

    /**
     * بناء قسم النص
     */
    protected function buildTextSection(string $text): array
    {
        return [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => $text
            ]
        ];
    }

    /**
     * بناء العنوان الرئيسي
     */
    protected function buildHeader(string $text): array
    {
        return [
            'type' => 'header',
            'text' => [
                'type' => 'plain_text',
                'text' => $text
            ]
        ];
    }

    /**
     * بناء قسم السياق (التاريخ والوقت)
     */
    protected function buildContextSection(string $text = null): array
    {
        $contextText = $text ?: "📅 " . now()->format('d/m/Y - H:i');

        return [
            'type' => 'context',
            'elements' => [
                [
                    'type' => 'mrkdwn',
                    'text' => $contextText
                ]
            ]
        ];
    }

    /**
     * بناء قسم الأزرار
     */
    protected function buildActionsSection(array $buttons): array
    {
        return [
            'type' => 'actions',
            'elements' => $buttons
        ];
    }
}

