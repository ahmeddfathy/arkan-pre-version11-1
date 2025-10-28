<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AttachmentShare;
use App\Services\ProjectManagement\AttachmentSharingNotificationService;
use Carbon\Carbon;

class CheckExpiredShares extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shares:check-expired
                            {--send-reminders : Send expiration reminders}
                            {--cleanup : Cleanup expired shares}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expired attachment shares and send notifications';

    protected $notificationService;

    public function __construct(AttachmentSharingNotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting attachment shares check...');

        if ($this->option('send-reminders')) {
            $this->sendExpirationReminders();
        }

        if ($this->option('cleanup')) {
            $this->cleanupExpiredShares();
        }

        if (!$this->option('send-reminders') && !$this->option('cleanup')) {
            // إذا لم يتم تمرير أي خيارات، تشغيل كلا العمليتين
            $this->sendExpirationReminders();
            $this->cleanupExpiredShares();
        }

        $this->info('Attachment shares check completed!');
    }

    /**
     * إرسال تذكيرات انتهاء المشاركة
     */
    private function sendExpirationReminders()
    {
        $this->info('Sending expiration reminders...');

        try {
            $this->notificationService->sendExpirationReminders();
            $this->info('✓ Expiration reminders sent successfully');
        } catch (\Exception $e) {
            $this->error('✗ Error sending expiration reminders: ' . $e->getMessage());
        }
    }

    /**
     * تنظيف المشاركات المنتهية الصلاحية
     */
    private function cleanupExpiredShares()
    {
        $this->info('Cleaning up expired shares...');

        try {
            // البحث عن المشاركات المنتهية الصلاحية والنشطة
            $expiredShares = AttachmentShare::where('is_active', true)
                ->where('expires_at', '<', Carbon::now())
                ->with(['sharedBy', 'attachment'])
                ->get();

            $this->info("Found {$expiredShares->count()} expired shares to process");

            $cleanedCount = 0;
            foreach ($expiredShares as $share) {
                try {
                    // إرسال إشعار انتهاء الصلاحية
                    $this->notificationService->notifyShareExpired($share);

                    // إلغاء تفعيل المشاركة
                    $share->deactivate();

                    $cleanedCount++;

                    $this->line("Cleaned share ID: {$share->id} for attachment: {$share->attachment->original_name}");

                } catch (\Exception $e) {
                    $this->error("Error processing share ID {$share->id}: " . $e->getMessage());
                }
            }

            $this->info("✓ Cleaned up {$cleanedCount} expired shares");

        } catch (\Exception $e) {
            $this->error('✗ Error during cleanup: ' . $e->getMessage());
        }
    }
}
