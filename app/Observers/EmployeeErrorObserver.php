<?php

namespace App\Observers;

use App\Models\EmployeeError;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class EmployeeErrorObserver
{
    /**
     * Handle the EmployeeError "created" event.
     */
    public function created(EmployeeError $error): void
    {
        // إرسال إشعار للموظف صاحب الخطأ
        $this->notifyEmployee($error);

        // تسجيل log
        Log::info('تم تسجيل خطأ على موظف', [
            'error_id' => $error->id,
            'user_id' => $error->user_id,
            'error_type' => $error->error_type,
            'reported_by' => $error->reported_by,
        ]);
    }

    /**
     * Handle the EmployeeError "updated" event.
     */
    public function updated(EmployeeError $error): void
    {
        // إذا تم تعديل نوع الخطأ من عادي إلى جوهري، نرسل إشعار
        if ($error->isDirty('error_type') && $error->error_type === 'critical') {
            $this->notifyEmployee($error, 'تم تحديث خطأك إلى خطأ جوهري');
        }
    }

    /**
     * Handle the EmployeeError "deleted" event.
     */
    public function deleted(EmployeeError $error): void
    {
        // يمكن إرسال إشعار بحذف الخطأ
        Log::info('تم حذف خطأ موظف', [
            'error_id' => $error->id,
            'user_id' => $error->user_id,
        ]);
    }

    /**
     * إرسال إشعار للموظف
     */
    private function notifyEmployee(EmployeeError $error, $customMessage = null): void
    {
        try {
            $errorTypeText = $error->error_type === 'critical' ? 'جوهري' : 'عادي';
            $message = $customMessage ?? "تم تسجيل خطأ {$errorTypeText} عليك: {$error->title}";

            Notification::create([
                'user_id' => $error->user_id,
                'type' => 'employee_error',
                'title' => 'تسجيل خطأ',
                'message' => $message,
                'data' => json_encode([
                    'error_id' => $error->id,
                    'error_type' => $error->error_type,
                    'error_category' => $error->error_category,
                    'reported_by' => $error->reportedBy->name ?? 'غير معروف',
                ]),
                'link' => route('employee-errors.show', $error->id),
            ]);
        } catch (\Exception $e) {
            Log::error('فشل إرسال إشعار خطأ الموظف', [
                'error_id' => $error->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}

