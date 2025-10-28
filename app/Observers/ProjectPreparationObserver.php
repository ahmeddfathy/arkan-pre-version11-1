<?php

namespace App\Observers;

use App\Models\Project;
use App\Models\ProjectPreparationHistory;
use Illuminate\Support\Facades\Auth;

class ProjectPreparationObserver
{
    /**
     * Handle the Project "updated" event.
     */
    public function updated(Project $project): void
    {
        // التحقق من تغيير حقول فترة التحضير
        $preparationFieldsChanged = false;
        $oldStartDate = null;
        $oldDays = null;

        if ($project->isDirty('preparation_start_date')) {
            $preparationFieldsChanged = true;
            $oldStartDate = $project->getOriginal('preparation_start_date');
        }

        if ($project->isDirty('preparation_days')) {
            $preparationFieldsChanged = true;
            $oldDays = $project->getOriginal('preparation_days');
        }

        if ($project->isDirty('preparation_enabled')) {
            $preparationFieldsChanged = true;
        }

        // إذا تغيرت حقول فترة التحضير، سجل في التاريخ
        if ($preparationFieldsChanged && $project->preparation_enabled) {
            $notes = [];

            if ($oldStartDate && $oldStartDate != $project->preparation_start_date) {
                $notes[] = sprintf(
                    'تم تغيير تاريخ البداية من %s إلى %s',
                    $oldStartDate ? \Carbon\Carbon::parse($oldStartDate)->format('Y-m-d H:i') : 'غير محدد',
                    $project->preparation_start_date ? $project->preparation_start_date->format('Y-m-d H:i') : 'غير محدد'
                );
            }

            if ($oldDays !== null && $oldDays != $project->preparation_days) {
                $notes[] = sprintf(
                    'تم تغيير عدد الأيام من %d إلى %d',
                    $oldDays,
                    $project->preparation_days
                );
            }

            if ($project->getOriginal('preparation_enabled') == false && $project->preparation_enabled == true) {
                $notes[] = 'تم تفعيل فترة التحضير';
            }

            ProjectPreparationHistory::logPreparationPeriod(
                $project->id,
                $project->preparation_start_date,
                $project->preparation_days,
                $project->preparation_end_date,
                implode(' | ', $notes),
                Auth::id()
            );
        }

        // إذا تم إيقاف فترة التحضير
        if ($project->getOriginal('preparation_enabled') == true && $project->preparation_enabled == false) {
            // جعل جميع الفترات السابقة غير current
            ProjectPreparationHistory::where('project_id', $project->id)
                ->where('is_current', true)
                ->update([
                    'is_current' => false
                ]);

            // إنشاء سجل بإيقاف فترة التحضير
            ProjectPreparationHistory::create([
                'project_id' => $project->id,
                'preparation_start_date' => null,
                'preparation_days' => null,
                'preparation_end_date' => null,
                'notes' => 'تم إيقاف فترة التحضير',
                'user_id' => Auth::id() ?? 1,
                'is_current' => false,
                'effective_from' => now()
            ]);
        }
    }

    /**
     * Handle the Project "created" event.
     */
    public function created(Project $project): void
    {
        // إذا تم إنشاء مشروع بفترة تحضير
        if ($project->preparation_enabled && $project->preparation_start_date && $project->preparation_days) {
            ProjectPreparationHistory::logPreparationPeriod(
                $project->id,
                $project->preparation_start_date,
                $project->preparation_days,
                $project->preparation_end_date,
                'إنشاء المشروع مع فترة تحضير',
                Auth::id()
            );
        }
    }
}
