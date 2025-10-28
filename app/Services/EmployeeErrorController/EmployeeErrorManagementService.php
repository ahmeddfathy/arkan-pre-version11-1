<?php

namespace App\Services\EmployeeErrorController;

use App\Models\EmployeeError;
use App\Models\TaskUser;
use App\Models\TemplateTaskUser;
use App\Models\ProjectServiceUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeErrorManagementService
{
    /**
     * تسجيل خطأ جديد على موظف
     */
    public function createError($errorable, array $data): EmployeeError
    {
        // التحقق من نوع الكيان
        if (!$this->isValidErrorable($errorable)) {
            throw new \Exception('نوع الكيان غير صالح');
        }

        // التحقق من وجود البيانات المطلوبة
        $this->validateErrorData($data);

        return DB::transaction(function () use ($errorable, $data) {
            $error = new EmployeeError([
                'user_id' => $errorable->user_id,
                'title' => $data['title'],
                'description' => $data['description'],
                'error_category' => $data['error_category'] ?? 'other',
                'error_type' => $data['error_type'] ?? 'normal',
                'reported_by' => $data['reported_by'] ?? Auth::id(),
            ]);

            $errorable->errors()->save($error);

            Log::info('Employee error created successfully', [
                'error_id' => $error->id,
                'user_id' => $error->user_id,
                'errorable_type' => get_class($errorable),
                'errorable_id' => $errorable->id
            ]);

            return $error;
        });
    }

    /**
     * تحديث خطأ موجود
     */
    public function updateError(EmployeeError $error, array $data): EmployeeError
    {
        $oldType = $error->error_type;

        $error->update([
            'title' => $data['title'] ?? $error->title,
            'description' => $data['description'] ?? $error->description,
            'error_category' => $data['error_category'] ?? $error->error_category,
            'error_type' => $data['error_type'] ?? $error->error_type,
        ]);

        Log::info('Employee error updated successfully', [
            'error_id' => $error->id,
            'old_type' => $oldType,
            'new_type' => $error->error_type
        ]);

        return $error->fresh();
    }

    /**
     * حذف خطأ
     */
    public function deleteError(EmployeeError $error): bool
    {
        Log::info('Employee error deleted', [
            'error_id' => $error->id,
            'user_id' => $error->user_id
        ]);

        return $error->delete();
    }

    /**
     * استعادة خطأ محذوف
     */
    public function restoreError($errorId): EmployeeError
    {
        $error = EmployeeError::withTrashed()->findOrFail($errorId);
        $error->restore();

        Log::info('Employee error restored', [
            'error_id' => $error->id
        ]);

        return $error;
    }

    /**
     * حذف نهائي
     */
    public function forceDeleteError(EmployeeError $error): bool
    {
        Log::info('Employee error force deleted', [
            'error_id' => $error->id
        ]);

        return $error->forceDelete();
    }

    /**
     * التحقق من نوع الكيان الصالح
     */
    private function isValidErrorable($errorable): bool
    {
        return $errorable instanceof TaskUser ||
               $errorable instanceof TemplateTaskUser ||
               $errorable instanceof ProjectServiceUser;
    }

    /**
     * التحقق من صحة بيانات الخطأ
     */
    private function validateErrorData(array $data): void
    {
        if (empty($data['title'])) {
            throw new \Exception('عنوان الخطأ مطلوب');
        }

        if (empty($data['description'])) {
            throw new \Exception('وصف الخطأ مطلوب');
        }

        $validCategories = ['quality', 'deadline', 'communication', 'technical', 'procedural', 'other'];
        if (isset($data['error_category']) && !in_array($data['error_category'], $validCategories)) {
            throw new \Exception('تصنيف الخطأ غير صالح');
        }

        $validTypes = ['normal', 'critical'];
        if (isset($data['error_type']) && !in_array($data['error_type'], $validTypes)) {
            throw new \Exception('نوع الخطأ غير صالح');
        }
    }

    /**
     * تحديث حالة عدة أخطاء دفعة واحدة
     */
    public function bulkUpdateErrorType(array $errorIds, string $errorType): int
    {
        if (!in_array($errorType, ['normal', 'critical'])) {
            throw new \Exception('نوع الخطأ غير صالح');
        }

        $updated = EmployeeError::whereIn('id', $errorIds)
            ->update(['error_type' => $errorType]);

        Log::info('Bulk error type update', [
            'count' => $updated,
            'error_type' => $errorType
        ]);

        return $updated;
    }

    /**
     * حذف عدة أخطاء دفعة واحدة
     */
    public function bulkDeleteErrors(array $errorIds): int
    {
        $deleted = EmployeeError::whereIn('id', $errorIds)->delete();

        Log::info('Bulk errors deleted', [
            'count' => $deleted
        ]);

        return $deleted;
    }
}

