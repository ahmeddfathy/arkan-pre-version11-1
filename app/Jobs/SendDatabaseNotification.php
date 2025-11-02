<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Exception;

class SendDatabaseNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60]; // 10s, 30s, 1min
    public $timeout = 30;

    protected $user;
    protected $data;
    protected $context;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, array $data, string $context = 'Database Notification')
    {
        $this->user = $user;
        $this->data = $data;
        $this->context = $context;

        // تحديد الـ queue للإشعارات
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting Database notification job', [
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'context' => $this->context,
            'attempt' => $this->attempts()
        ]);

        try {
            // إنشاء الإشعار في قاعدة البيانات
            // ملاحظة: جدول notifications يحتوي على: id (auto increment), user_id, type, data, read_at, created_at, updated_at
            \Illuminate\Support\Facades\DB::table('notifications')->insert([
                'user_id' => $this->user->id,
                'type' => 'dependent_service_status_updated',
                'data' => json_encode($this->data),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('✅ Database notification created successfully', [
                'user_id' => $this->user->id,
                'user_name' => $this->user->name,
                'context' => $this->context,
                'attempt' => $this->attempts(),
                'data' => $this->data
            ]);
        } catch (Exception $e) {
            Log::error('❌ Database notification job failed', [
                'user_id' => $this->user->id,
                'user_name' => $this->user->name,
                'context' => $this->context,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // إذا فشل آخر محاولة، نسجل الفشل النهائي
            if ($this->attempts() >= $this->tries) {
                Log::error('Database notification job permanently failed after all retries', [
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
        Log::error('Database notification job permanently failed', [
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'context' => $this->context,
            'error' => $exception->getMessage(),
            'total_attempts' => $this->tries
        ]);
    }
}
