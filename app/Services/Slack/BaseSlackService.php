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
        $this->botToken = $this->getSlackBotToken();
    }


    private function getSlackBotToken(): ?string
    {
        $token = env('SLACK_BOT_TOKEN');

        if (empty($token)) {
            $token = config('services.slack.bot_token');
        }

        if (empty($token)) {
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

    protected function queueSlackMessage(User $user, array $message, string $context = 'Slack Notification'): bool
    {
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

    protected function sendDirectMessage(User $user, array $message): bool
    {
        Log::info('Starting Slack direct message send', [
            'user_id' => $user->id,
            'slack_user_id' => $user->slack_user_id,
            'has_bot_token' => !empty($this->botToken),
            'bot_token_preview' => $this->botToken ? substr($this->botToken, 0, 10) . '...' : 'none',
            'bot_token_length' => $this->botToken ? strlen($this->botToken) : 0
        ]);

        if (empty($user->slack_user_id)) {
            Log::info('Skipping Slack notification - User has no Slack ID', [
                'user_id' => $user->id
            ]);
            $this->setNotificationStatus(true, 'المستخدم ليس لديه Slack ID - تم التجاهل');
            return true;
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

            $dmResponse = Http::timeout(15)
                ->retry(3, function ($attempt, $exception) {
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

            $response = Http::timeout(15)
                ->retry(3, function ($attempt, $exception) {
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
            Log::warning('Slack connection timeout/error - continuing', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);
            $this->setNotificationStatus(false, 'انتهت مهلة الاتصال مع Slack');
            return false;
        } catch (\Exception $e) {
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

    protected function sendSlackNotification(User $user, array $message, string $context = 'Slack Notification', bool $useQueue = true): bool
    {
        if ($useQueue) {
            return $this->queueSlackMessage($user, $message, $context);
        } else {
            return $this->sendDirectMessage($user, $message);
        }
    }

    protected function notify(User $user, array $message, string $context = 'إشعار Slack'): bool
    {
        return $this->sendSlackNotification($user, $message, $context, true);
    }

    protected function notifyImmediate(User $user, array $message, string $context = 'إشعار Slack عاجل'): bool
    {
        return $this->sendSlackNotification($user, $message, $context, false);
    }

    protected function buildActionButton(string $text, string $url, string $style = 'primary'): array
    {
        $validStyle = in_array($style, ['primary', 'danger']) ? $style : 'primary';

        $button = [
            'type' => 'button',
            'text' => [
                'type' => 'plain_text',
                'text' => $text
            ],
            'url' => $url
        ];

        if ($validStyle !== 'primary') {
            $button['style'] = $validStyle;
        }

        return $button;
    }

    protected function buildInfoSection(array $fields): array
    {
        return [
            'type' => 'section',
            'fields' => array_map(function ($field) {
                return [
                    'type' => 'mrkdwn',
                    'text' => $field
                ];
            }, $fields)
        ];
    }

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

    protected function buildActionsSection(array $buttons): array
    {
        return [
            'type' => 'actions',
            'elements' => $buttons
        ];
    }
}
