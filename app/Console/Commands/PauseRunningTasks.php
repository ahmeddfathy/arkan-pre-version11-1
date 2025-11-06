<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TaskUser;
use App\Models\TemplateTaskUser;
use App\Services\Tasks\TaskTimeSplitService;
use App\Services\TaskController\TaskStatusService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Traits\HasNTPTime;

class PauseRunningTasks extends Command
{
    use HasNTPTime;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:pause-running
                            {--time=all : الوقت المحدد للإيقاف (12pm, 4pm, all)}
                            {--dry-run : معاينة المهام بدون إيقافها فعلياً}
                            {--user= : إيقاف مهام مستخدم معين فقط}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'إيقاف جميع المهام النشطة مع حساب الأوقات بدقة - مجدولة للساعة 12 و 4';

    protected $taskTimeSplitService;
    protected $taskStatusService;

    public function __construct(
        TaskTimeSplitService $taskTimeSplitService,
        TaskStatusService $taskStatusService
    ) {
        parent::__construct();
        $this->taskTimeSplitService = $taskTimeSplitService;
        $this->taskStatusService = $taskStatusService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = $this->getCurrentCairoTime();
        $timeOption = $this->option('time');
        $isDryRun = $this->option('dry-run');
        $specificUser = $this->option('user');

        // التحقق من الوقت المحدد
        $shouldRun = $this->shouldRunAtThisTime($timeOption, $now);

        if (!$shouldRun && $timeOption !== 'all') {
            $this->warn("⏰ لم يحن وقت الإيقاف المجدول. الوقت الحالي: {$now->format('H:i')}");
            return Command::SUCCESS;
        }

        $this->info("🚀 بدء عملية إيقاف المهام النشطة - {$now->format('Y-m-d H:i:s')}");

        if ($isDryRun) {
            $this->warn("👁️ وضع المعاينة مُفعّل - لن يتم إيقاف المهام فعلياً");
        }

        if ($specificUser) {
            $this->info("👤 إيقاف مهام المستخدم: {$specificUser}");
        }

        try {
            DB::beginTransaction();

            $results = [
                'regular_tasks' => 0,
                'template_tasks' => 0,
                'total_time_calculated' => 0,
                'users_affected' => [],
                'errors' => []
            ];

            // إيقاف المهام العادية مع حساب الأوقات
            $regularTasksResults = $this->pauseRegularTasks($now, $isDryRun, $specificUser);
            $results['regular_tasks'] = $regularTasksResults['count'];
            $results['total_time_calculated'] += $regularTasksResults['total_minutes'];
            $results['users_affected'] = array_merge($results['users_affected'], $regularTasksResults['users']);
            $results['errors'] = array_merge($results['errors'], $regularTasksResults['errors']);

            // إيقاف مهام القوالب مع حساب الأوقات
            $templateTasksResults = $this->pauseTemplateTasks($now, $isDryRun, $specificUser);
            $results['template_tasks'] = $templateTasksResults['count'];
            $results['total_time_calculated'] += $templateTasksResults['total_minutes'];
            $results['users_affected'] = array_merge($results['users_affected'], $templateTasksResults['users']);
            $results['errors'] = array_merge($results['errors'], $templateTasksResults['errors']);

            if (!$isDryRun) {
                DB::commit();
            } else {
                DB::rollBack();
            }

            $this->displayResults($results, $isDryRun, $now);
        } catch (\Exception $e) {
            DB::rollBack();

            $this->error("❌ حدث خطأ أثناء إيقاف المهام: " . $e->getMessage());

            Log::error('Failed to pause running tasks', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'time' => $now->toDateTimeString()
            ]);

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * التحقق من وقت التشغيل المحدد
     */
    private function shouldRunAtThisTime(string $timeOption, Carbon $now): bool
    {
        $currentHour = (int) $now->format('H');

        switch ($timeOption) {
            case '12pm':
                return $currentHour == 12;
            case '4pm':
                return $currentHour == 16;
            case 'all':
            default:
                return true;
        }
    }

    /**
     * إيقاف المهام العادية مع حساب الأوقات
     */
    private function pauseRegularTasks(Carbon $now, bool $isDryRun, ?string $specificUser): array
    {
        $query = TaskUser::where('status', 'in_progress')
            ->whereNotNull('start_date')
            ->with(['user', 'task']);

        if ($specificUser) {
            $query->whereHas('user', function ($q) use ($specificUser) {
                $q->where('name', 'like', "%{$specificUser}%")
                    ->orWhere('email', 'like', "%{$specificUser}%");
            });
        }

        $runningTasks = $query->get();

        $results = [
            'count' => 0,
            'total_minutes' => 0,
            'users' => [],
            'errors' => []
        ];

        foreach ($runningTasks as $taskUser) {
            try {
                $startTime = Carbon::parse($taskUser->start_date);

                // حساب الوقت المقسم بدقة
                $allocatedMinutes = $this->taskTimeSplitService->calculateSplitTimeForTask(
                    $taskUser->id,
                    false, // ليست مهمة قالب
                    $startTime,
                    $now,
                    $taskUser->user_id
                );

                if (!$isDryRun) {
                    // تحديث الأوقات الإجمالية
                    $totalMinutes = ($taskUser->actual_hours * 60) + $taskUser->actual_minutes + $allocatedMinutes;
                    $hours = intdiv($totalMinutes, 60);
                    $minutes = $totalMinutes % 60;

                    $taskUser->update([
                        'status' => 'paused',
                        'actual_hours' => $hours,
                        'actual_minutes' => $minutes,
                        'start_date' => null, // إعادة تعيين وقت البداية
                    ]);

                    // تحديث نقطة التحقق للمهام النشطة الأخرى للمستخدم
                    $this->taskTimeSplitService->updateActiveTasksCheckpoint(
                        $taskUser->user_id,
                        $now,
                        $taskUser->id,
                        false
                    );
                }

                $results['count']++;
                $results['total_minutes'] += $allocatedMinutes;
                $results['users'][] = [
                    'user_name' => $taskUser->user->name,
                    'task_name' => $taskUser->task->name,
                    'time_added' => $allocatedMinutes,
                    'type' => 'regular'
                ];

                $this->line("  ✅ {$taskUser->user->name} - {$taskUser->task->name} ({$allocatedMinutes} دقيقة)");
            } catch (\Exception $e) {
                $results['errors'][] = "خطأ في مهمة {$taskUser->task->name}: " . $e->getMessage();
                $this->error("  ❌ خطأ في إيقاف مهمة {$taskUser->task->name}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * إيقاف مهام القوالب مع حساب الأوقات
     */
    private function pauseTemplateTasks(Carbon $now, bool $isDryRun, ?string $specificUser): array
    {
        $query = TemplateTaskUser::where('status', 'in_progress')
            ->whereNotNull('started_at')
            ->with(['user', 'templateTask']);

        if ($specificUser) {
            $query->whereHas('user', function ($q) use ($specificUser) {
                $q->where('name', 'like', "%{$specificUser}%")
                    ->orWhere('email', 'like', "%{$specificUser}%");
            });
        }

        $runningTemplateTasks = $query->get();

        $results = [
            'count' => 0,
            'total_minutes' => 0,
            'users' => [],
            'errors' => []
        ];

        foreach ($runningTemplateTasks as $templateTaskUser) {
            try {
                $startTime = Carbon::parse($templateTaskUser->started_at);

                // حساب الوقت المقسم بدقة
                $allocatedMinutes = $this->taskTimeSplitService->calculateSplitTimeForTask(
                    $templateTaskUser->id,
                    true, // مهمة قالب
                    $startTime,
                    $now,
                    $templateTaskUser->user_id
                );

                if (!$isDryRun) {
                    // تحديث الأوقات الإجمالية
                    $totalMinutes = ($templateTaskUser->actual_minutes ?? 0) + $allocatedMinutes;

                    $templateTaskUser->update([
                        'status' => 'paused',
                        'actual_minutes' => $totalMinutes,
                        'paused_at' => $now,
                        'started_at' => null, // إعادة تعيين وقت البداية
                    ]);

                    // تحديث نقطة التحقق للمهام النشطة الأخرى للمستخدم
                    $this->taskTimeSplitService->updateActiveTasksCheckpoint(
                        $templateTaskUser->user_id,
                        $now,
                        $templateTaskUser->id,
                        true
                    );
                }

                $results['count']++;
                $results['total_minutes'] += $allocatedMinutes;
                $results['users'][] = [
                    'user_name' => $templateTaskUser->user->name,
                    'task_name' => $templateTaskUser->templateTask->name,
                    'time_added' => $allocatedMinutes,
                    'type' => 'template'
                ];

                $this->line("  ✅ {$templateTaskUser->user->name} - {$templateTaskUser->templateTask->name} ({$allocatedMinutes} دقيقة)");
            } catch (\Exception $e) {
                $results['errors'][] = "خطأ في مهمة قالب {$templateTaskUser->templateTask->name}: " . $e->getMessage();
                $this->error("  ❌ خطأ في إيقاف مهمة القالب {$templateTaskUser->templateTask->name}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * عرض نتائج العملية
     */
    private function displayResults(array $results, bool $isDryRun, Carbon $now): void
    {
        $this->newLine();

        if ($isDryRun) {
            $this->info("📋 نتائج المعاينة:");
        } else {
            $this->info("✅ نتائج الإيقاف:");
        }

        $this->table(
            ['النوع', 'العدد', 'الوقت المحسوب (دقيقة)'],
            [
                ['المهام العادية', $results['regular_tasks'], 0],
                ['مهام القوالب', $results['template_tasks'], 0],
                ['الإجمالي', $results['regular_tasks'] + $results['template_tasks'], $results['total_time_calculated']]
            ]
        );

        $uniqueUsers = collect($results['users_affected'])->pluck('user_name')->unique();
        $this->info("👥 المستخدمون المتأثرون: " . $uniqueUsers->count());

        // عرض الأخطاء إن وجدت
        if (!empty($results['errors'])) {
            $this->error("⚠️  الأخطاء:");
            foreach ($results['errors'] as $error) {
                $this->error("  - {$error}");
            }
        }

        // تسجيل في اللوج
        if (!$isDryRun) {
            Log::info("Scheduled pause completed successfully", [
                'regular_tasks' => $results['regular_tasks'],
                'template_tasks' => $results['template_tasks'],
                'total_time_calculated' => $results['total_time_calculated'],
                'unique_users' => $uniqueUsers->count(),
                'errors_count' => count($results['errors']),
                'timestamp' => $now->toDateTimeString()
            ]);
        }

        $this->newLine();
        $action = $isDryRun ? "معاينة" : "إيقاف";
        $this->info("🎉 تمت عملية {$action} المهام بنجاح في {$now->format('H:i:s')}");
    }
}
