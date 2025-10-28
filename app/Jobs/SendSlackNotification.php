<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Exception;

class SendSlackNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 180, 300]; // 1min, 3min, 5min
    public $timeout = 30;

    protected $user;
    protected $message;
    protected $context;
    protected $botToken;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, array $message, string $context = 'Slack Notification')
    {
        $this->user = $user;
        $this->message = $message;
        $this->context = $context;
        $this->botToken = $this->getSlackBotToken();

        // تحديد الـ queue connection والاسم للإشعارات
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting Slack notification job', [
            'user_id' => $this->user->id,
            'context' => $this->context,
            'attempt' => $this->attempts(),
            'has_bot_token' => !empty($this->botToken)
        ]);

        // 🚀 Smart Check: تحقق سريع قبل أي محاولة إرسال
        if (empty($this->user->slack_user_id)) {
            Log::info('Skipping Slack notification job - User has no Slack ID', [
                'user_id' => $this->user->id,
                'context' => $this->context
            ]);
            return; // تم إنهاء المهمة بنجاح
        }

        if (!$this->botToken) {
            Log::warning('Skipping Slack notification job - No bot token configured', [
                'user_id' => $this->user->id,
                'context' => $this->context
            ]);
            return; // تم إنهاء المهمة بنجاح
        }

        try {
            $this->sendSlackMessage();

            Log::info('Slack notification job completed successfully', [
                'user_id' => $this->user->id,
                'context' => $this->context,
                'attempt' => $this->attempts()
            ]);

        } catch (Exception $e) {
            Log::error('Slack notification job failed', [
                'user_id' => $this->user->id,
                'context' => $this->context,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // إذا فشل آخر محاولة، نسجل الفشل النهائي
            if ($this->attempts() >= $this->tries) {
                Log::error('Slack notification job permanently failed after all retries', [
                    'user_id' => $this->user->id,
                    'context' => $this->context,
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
        Log::error('Slack notification job permanently failed', [
            'user_id' => $this->user->id,
            'context' => $this->context,
            'error' => $exception->getMessage(),
            'total_attempts' => $this->tries
        ]);
    }

    /**
     * إرسال رسالة Slack
     */
    private function sendSlackMessage(): void
    {
        $baseUrl = 'https://slack.com/api/';

        // فتح قناة المحادثة
        Log::info('Opening Slack conversation in job', [
            'user_id' => $this->user->id,
            'slack_user_id' => $this->user->slack_user_id
        ]);

        $dmResponse = Http::timeout(15)
            ->retry(2, 200) // محاولتان مع تأخير 200ms
            ->withToken($this->botToken)
            ->post($baseUrl . 'conversations.open', [
                'users' => $this->user->slack_user_id,
            ]);

        if (!$dmResponse->successful() || !$dmResponse->json('ok')) {
            throw new Exception('Failed to open Slack conversation: ' . $dmResponse->json('error'));
        }

        $channelId = $dmResponse->json('channel.id');

        Log::info('Slack conversation opened successfully in job', [
            'channel_id' => $channelId,
            'user_id' => $this->user->id
        ]);

        // إرسال الرسالة
        $response = Http::timeout(15)
            ->retry(2, 200)
            ->withToken($this->botToken)
            ->post($baseUrl . 'chat.postMessage', [
                'channel' => $channelId,
                'text' => $this->message['text'],
                'blocks' => $this->message['blocks']
            ]);

        if (!$response->successful() || !$response->json('ok')) {
            throw new Exception('Failed to send Slack message: ' . $response->json('error'));
        }

        Log::info('Slack message sent successfully in job', [
            'user_id' => $this->user->id,
            'context' => $this->context
        ]);
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
}
