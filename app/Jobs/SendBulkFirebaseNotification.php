<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendFirebaseNotification;
use Exception;

class SendBulkFirebaseNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 60;

    protected $tokens;
    protected $title;
    protected $body;
    protected $link;

    /**
     * Create a new job instance.
     */
    public function __construct(array $tokens, string $title, string $body, string $link = '/test')
    {
        $this->tokens = collect($tokens)->filter(); // فلترة الـ tokens الفارغة
        $this->title = $title;
        $this->body = $body;
        $this->link = $link;

        // تحديد الـ queue connection والاسم للإشعارات
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting bulk Firebase notification job', [
            'title' => $this->title,
            'tokens_count' => $this->tokens->count(),
            'attempt' => $this->attempts()
        ]);

        // 🚀 Smart Check: تحقق سريع من وجود tokens
        if ($this->tokens->isEmpty()) {
            Log::info('Skipping bulk Firebase notification job - No valid tokens', [
                'title' => $this->title
            ]);
            return; // تم إنهاء المهمة بنجاح
        }

        try {
            $this->dispatchIndividualNotifications();

            Log::info('Bulk Firebase notification job completed successfully', [
                'title' => $this->title,
                'tokens_count' => $this->tokens->count(),
                'attempt' => $this->attempts()
            ]);

        } catch (Exception $e) {
            Log::error('Bulk Firebase notification job failed', [
                'title' => $this->title,
                'tokens_count' => $this->tokens->count(),
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // إذا فشل آخر محاولة، نسجل الفشل النهائي
            if ($this->attempts() >= $this->tries) {
                Log::error('Bulk Firebase notification job permanently failed after all retries', [
                    'title' => $this->title,
                    'tokens_count' => $this->tokens->count(),
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
        Log::error('Bulk Firebase notification job permanently failed', [
            'title' => $this->title,
            'tokens_count' => $this->tokens->count(),
            'error' => $exception->getMessage(),
            'total_attempts' => $this->tries
        ]);
    }

    /**
     * إرسال إشعارات فردية لكل token
     */
    private function dispatchIndividualNotifications(): void
    {
        $successCount = 0;
        $failCount = 0;

        foreach ($this->tokens as $index => $token) {
            try {
                // فلترة الـ tokens الفارغة أو null
                if (empty($token)) {
                    Log::debug('Skipping empty FCM token', ['index' => $index]);
                    continue;
                }

                // إرسال job فردي لكل token
                SendFirebaseNotification::dispatch($token, $this->title, $this->body, $this->link)
                    ->onQueue('notifications')
                    ->delay(now()->addSeconds($index * 2)); // تأخير بسيط لتجنب rate limiting

                $successCount++;

                Log::debug('Dispatched individual Firebase notification', [
                    'index' => $index,
                    'token_preview' => substr($token, 0, 20) . '...',
                    'delay_seconds' => $index * 2
                ]);

            } catch (Exception $e) {
                $failCount++;
                Log::warning('Failed to dispatch individual Firebase notification', [
                    'index' => $index,
                    'token_preview' => !empty($token) ? substr($token, 0, 20) . '...' : 'empty',
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Bulk Firebase notification dispatch summary', [
            'title' => $this->title,
            'total_tokens' => $this->tokens->count(),
            'successful_dispatches' => $successCount,
            'failed_dispatches' => $failCount
        ]);

        if ($failCount > 0 && $successCount === 0) {
            throw new Exception("Failed to dispatch any Firebase notifications");
        }
    }

    /**
     * تقسيم الـ tokens إلى مجموعات صغيرة لتحسين الأداء
     */
    public static function dispatchInChunks(array $tokens, string $title, string $body, string $link = '/test', int $chunkSize = 100): void
    {
        $chunks = collect($tokens)->filter()->chunk($chunkSize);

        Log::info('Dispatching bulk Firebase notifications in chunks', [
            'total_tokens' => count($tokens),
            'chunk_size' => $chunkSize,
            'total_chunks' => $chunks->count(),
            'title' => $title
        ]);

        foreach ($chunks as $chunkIndex => $chunk) {
            try {
                static::dispatch($chunk->toArray(), $title, $body, $link)
                    ->onQueue('notifications')
                    ->delay(now()->addSeconds($chunkIndex * 10)); // تأخير بين chunks

                Log::debug('Dispatched Firebase notification chunk', [
                    'chunk_index' => $chunkIndex,
                    'chunk_size' => $chunk->count(),
                    'delay_seconds' => $chunkIndex * 10
                ]);

            } catch (Exception $e) {
                Log::error('Failed to dispatch Firebase notification chunk', [
                    'chunk_index' => $chunkIndex,
                    'chunk_size' => $chunk->count(),
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
