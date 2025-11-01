<?php

namespace App\Services\TaskController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidationValidator;

class TaskValidationService
{
    /**
     * التحقق الأساسي من بيانات المهمة
     */
    public function validateBasicTaskData(Request $request): ValidationValidator
    {
        $basicValidationRules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'nullable|exists:projects,id',
            'service_id' => 'nullable|exists:company_services,id',
            'graphic_task_type_id' => 'nullable|exists:graphic_task_types,id',
            'points' => 'nullable|integer|min:0|max:1000',
            'due_date' => 'nullable|date',
            'assigned_users' => 'required|array|min:1', // ✅ إجباري ولازم موظف واحد على الأقل
            'assigned_users.*.user_id' => 'required|exists:users,id',
            'assigned_users.*.role' => 'nullable|string',
            'is_flexible_time' => 'nullable|in:true,false,1,0',
        ];

        return Validator::make($request->all(), $basicValidationRules);
    }

    /**
     * التحقق من بيانات المهمة مع قواعد الوقت
     */
    public function validateTaskWithTimeRules(Request $request, bool $isFlexible): ValidationValidator
    {
        $validationRules = $this->getBasicValidationRules();

        if (!$isFlexible) {
            $validationRules = array_merge($validationRules, $this->getRequiredTimeRules());
        } else {
            $validationRules = array_merge($validationRules, $this->getOptionalTimeRules());
        }

        return Validator::make($request->all(), $validationRules);
    }

    /**
     * التحقق من بيانات تحديث المهمة
     */
    public function validateTaskUpdate(Request $request, bool $isFlexible): ValidationValidator
    {
        $validationRules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'nullable|exists:projects,id',
            'service_id' => 'nullable|exists:company_services,id',
            'graphic_task_type_id' => 'nullable|exists:graphic_task_types,id',
            'status' => 'required|in:new,cancelled',
            'due_date' => 'nullable|date',
            'points' => 'nullable|integer|min:0|max:1000',
            'assigned_users' => 'required|array|min:1', // ✅ إجباري ولازم موظف واحد على الأقل
            'assigned_users.*.user_id' => 'required|exists:users,id',
            'assigned_users.*.role' => 'nullable|string',
            'is_flexible_time' => 'nullable|in:true,false,1,0',
        ];

        if (!$isFlexible) {
            $validationRules = array_merge($validationRules, [
                'estimated_hours' => 'required|integer|min:0',
                'estimated_minutes' => 'required|integer|min:0|max:59',
                'assigned_users.*.estimated_hours' => 'required|integer|min:0',
                'assigned_users.*.estimated_minutes' => 'required|integer|min:0|max:59',
            ]);
        } else {
            $validationRules = array_merge($validationRules, [
                'estimated_hours' => 'nullable|integer|min:0',
                'estimated_minutes' => 'nullable|integer|min:0|max:59',
                'assigned_users.*.estimated_hours' => 'nullable|integer|min:0',
                'assigned_users.*.estimated_minutes' => 'nullable|integer|min:0|max:59',
            ]);
        }

        return Validator::make($request->all(), $validationRules);
    }

    /**
     * التحقق من بيانات مهمة القالب
     */
    public function validateTemplateTask(Request $request): ValidationValidator
    {
        $validationRules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'nullable|exists:projects,id',
            'status' => 'required|in:new,cancelled',
            'estimated_hours' => 'nullable|integer|min:0',
            'estimated_minutes' => 'nullable|integer|min:0|max:59',
            'points' => 'nullable|integer|min:0|max:1000',
            'assigned_users' => 'nullable|array',
            'assigned_users.*.user_id' => 'required|exists:users,id',
        ];

        return Validator::make($request->all(), $validationRules);
    }

    /**
     * تحديد ما إذا كانت المهمة مرنة الوقت
     */
    public function determineFlexibleTime(Request $request): bool
    {
        $flexibleTimeValue = $request->input('is_flexible_time', '0');
        return filter_var($flexibleTimeValue, FILTER_VALIDATE_BOOLEAN) || $flexibleTimeValue === '1';
    }

    /**
     * الحصول على قواعد التحقق الأساسية
     */
    private function getBasicValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'nullable|exists:projects,id',
            'service_id' => 'nullable|exists:company_services,id',
            'graphic_task_type_id' => 'nullable|exists:graphic_task_types,id',
            'points' => 'nullable|integer|min:0|max:1000',
            'due_date' => 'nullable|date',
            'assigned_users' => 'required|array|min:1', // ✅ إجباري ولازم موظف واحد على الأقل
            'assigned_users.*.user_id' => 'required|exists:users,id',
            'assigned_users.*.role' => 'nullable|string',
            'is_flexible_time' => 'nullable|in:true,false,1,0',
        ];
    }

    /**
     * الحصول على قواعد الوقت المطلوبة
     */
    private function getRequiredTimeRules(): array
    {
        return [
            'estimated_hours' => 'required|integer|min:0',
            'estimated_minutes' => 'required|integer|min:0|max:59',
            'assigned_users.*.estimated_hours' => 'nullable|integer|min:0',
            'assigned_users.*.estimated_minutes' => 'nullable|integer|min:0|max:59',
        ];
    }

    /**
     * الحصول على قواعد الوقت الاختيارية
     */
    private function getOptionalTimeRules(): array
    {
        return [
            'estimated_hours' => 'nullable|integer|min:0',
            'estimated_minutes' => 'nullable|integer|min:0|max:59',
        ];
    }
}
