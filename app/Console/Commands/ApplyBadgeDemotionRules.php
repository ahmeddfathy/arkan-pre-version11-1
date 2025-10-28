<?php

namespace App\Console\Commands;

use App\Models\Season;
use App\Services\BadgeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ApplyBadgeDemotionRules extends Command
{
    /**
     * اسم وتوصيف الأمر.
     *
     * @var string
     */
    protected $signature = 'badges:apply-demotion
                            {old_season_id : معرف الموسم القديم}
                            {new_season_id : معرف الموسم الجديد}
                            {--force : تنفيذ الأمر بدون طلب تأكيد}';

    /**
     * وصف الأمر.
     *
     * @var string
     */
    protected $description = 'تطبيق قواعد هبوط الشارات من موسم قديم إلى موسم جديد';

    /**
     * خدمة الشارات.
     */
    protected $badgeService;

    /**
     * إنشاء مثيل جديد من الأمر.
     */
    public function __construct(BadgeService $badgeService)
    {
        parent::__construct();
        $this->badgeService = $badgeService;
    }

    /**
     * تنفيذ الأمر.
     */
    public function handle()
    {
        $oldSeasonId = $this->argument('old_season_id');
        $newSeasonId = $this->argument('new_season_id');

        // التحقق من وجود المواسم
        $oldSeason = Season::find($oldSeasonId);
        $newSeason = Season::find($newSeasonId);

        if (!$oldSeason) {
            $this->error("الموسم القديم ذو المعرف {$oldSeasonId} غير موجود.");
            return 1;
        }

        if (!$newSeason) {
            $this->error("الموسم الجديد ذو المعرف {$newSeasonId} غير موجود.");
            return 1;
        }

        // عرض معلومات المواسم
        $this->info("الموسم القديم: {$oldSeason->name} ({$oldSeason->start_date->format('Y-m-d')} إلى {$oldSeason->end_date->format('Y-m-d')})");
        $this->info("الموسم الجديد: {$newSeason->name} ({$newSeason->start_date->format('Y-m-d')} إلى {$newSeason->end_date->format('Y-m-d')})");

        // طلب تأكيد ما لم يتم استخدام خيار --force
        if (!$this->option('force') && !$this->confirm('هل أنت متأكد من أنك تريد تطبيق قواعد الهبوط على جميع المستخدمين؟')) {
            $this->info('تم إلغاء العملية.');
            return 0;
        }

        $this->info('جاري تطبيق قواعد الهبوط...');

        try {
            $result = $this->badgeService->applyDemotionRules($oldSeason, $newSeason);

            if ($result['success']) {
                $stats = $result['data'];

                $this->info('تم تطبيق قواعد الهبوط بنجاح!');
                $this->info("إجمالي المستخدمين الذين تم معالجتهم: {$stats['total_users']}");
                $this->info("عدد المستخدمين الذين تم تخفيض رتبتهم: {$stats['demoted_users']}");

                // عرض تفاصيل عمليات الهبوط
                if (!empty($stats['badges'])) {
                    $this->table(
                        ['من شارة', 'إلى شارة', 'عدد المستخدمين'],
                        collect($stats['badges'])->map(function ($item) {
                            return [$item['from'], $item['to'], $item['count']];
                        })->toArray()
                    );
                }

                return 0;
            } else {
                $this->error($result['message']);
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('حدث خطأ أثناء تطبيق قواعد الهبوط: ' . $e->getMessage());
            Log::error('خطأ في أمر تطبيق قواعد الهبوط: ' . $e->getMessage(), [
                'exception' => $e,
                'old_season_id' => $oldSeasonId,
                'new_season_id' => $newSeasonId,
            ]);

            return 1;
        }
    }
}
