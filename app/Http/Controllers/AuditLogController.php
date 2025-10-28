<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;
use App\Models\AbsenceRequest;
use App\Models\PermissionRequest;
use App\Models\OverTimeRequests;
use App\Models\User;
use Carbon\Carbon;

class AuditLogController extends Controller
{
    // تعريف الثوابت للأنواع المختلفة من الطلبات
    const REQUEST_TYPES = [
        'absence' => AbsenceRequest::class,
        'permission' => PermissionRequest::class,
        'overtime' => OverTimeRequests::class,
        'project' => \App\Models\Project::class,
        'project_attachment' => \App\Models\ProjectAttachment::class,
        'task' => \App\Models\Task::class,
        'task_user' => \App\Models\TaskUser::class,
        'ticket_assignment' => \App\Models\TicketAssignment::class,
        'template_task' => \App\Models\TemplateTask::class,
        'template_task_user' => \App\Models\TemplateTaskUser::class
    ];

    public function index(Request $request)
    {
        $query = Audit::with(['user', 'auditable']);

        // تحويل نوع الطلب إلى اسم الموديل المناسب
        if ($request->filled('request_type')) {
            $requestType = $request->request_type;

            // التحقق إذا كانت القيمة المرسلة هي اسم الكلاس مباشرة
            if (in_array($requestType, array_values(self::REQUEST_TYPES))) {
                $query->where('auditable_type', $requestType);
            }
            // أو إذا كانت مفتاح في مصفوفة REQUEST_TYPES
            elseif (isset(self::REQUEST_TYPES[$requestType])) {
                $query->where('auditable_type', self::REQUEST_TYPES[$requestType]);
            }
        } else {
            $query->whereIn('auditable_type', array_values(self::REQUEST_TYPES));
        }

        // Filter by date
        if ($request->filled('date')) {
            $date = Carbon::parse($request->date);
            $query->whereDate('created_at', $date);
        }

        // Filter by month and year
        if ($request->filled('month') && $request->filled('year')) {
            $query->whereMonth('created_at', $request->month)
                ->whereYear('created_at', $request->year);
        }

        // Filter by user (employee)
        if ($request->filled('user_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('user_id', $request->user_id)
                    ->orWhereHas('auditable', function ($q) use ($request) {
                        $q->where('user_id', $request->user_id);
                    });
            });
        }

        // Filter by action type
        if ($request->filled('action')) {
            $query->where('event', $request->action);
        }

        // Filter by model ID
        if ($request->filled('model_id')) {
            $query->where('auditable_id', $request->model_id);
        }

        $audits = $query->latest()
            ->get()
            ->map(function ($audit) {
                $actionType = match ($audit->event) {
                    'created' => 'إنشاء',
                    'updated' => 'تحديث',
                    'deleted' => 'حذف',
                    default => $audit->event
                };

                $modelType = match ($audit->auditable_type) {
                    AbsenceRequest::class => 'طلب غياب',
                    PermissionRequest::class => 'طلب إذن',
                    OverTimeRequests::class => 'طلب وقت إضافي',
                    \App\Models\Project::class => 'مشروع',
                    \App\Models\ProjectAttachment::class => 'مرفق مشروع',
                    \App\Models\Task::class => 'مهمة',
                    \App\Models\TaskUser::class => 'تخصيص مهمة',
                    \App\Models\TicketAssignment::class => 'تخصيص تذكرة',
                    \App\Models\TemplateTask::class => 'قالب مهمة',
                    \App\Models\TemplateTaskUser::class => 'تخصيص قالب مهمة',
                    default => $audit->auditable_type
                };

                // Get request owner information
                $requestOwner = null;
                if ($audit->auditable && method_exists($audit->auditable, 'user')) {
                    $requestOwner = $audit->auditable->user;
                } else if (isset($audit->new_values['user_id'])) {
                    $requestOwner = User::find($audit->new_values['user_id']);
                } else if (isset($audit->old_values['user_id'])) {
                    $requestOwner = User::find($audit->old_values['user_id']);
                }

                $changes = [];

                // Process old and new values
                foreach ($audit->old_values as $field => $oldValue) {
                    $newValue = $audit->new_values[$field] ?? null;
                    $fieldName = $this->translateFieldName($field);

                    if ($field === 'status' || $field === 'manager_status' || $field === 'hr_status') {
                        $oldValue = $this->translateStatus($oldValue);
                        $newValue = $this->translateStatus($newValue);
                    }

                    if ($field === 'user_id') {
                        $oldValue = $oldValue ? User::find($oldValue)?->name . ' (' . $oldValue . ')' : null;
                        $newValue = $newValue ? User::find($newValue)?->name . ' (' . $newValue . ')' : null;
                    }

                    $changes[] = [
                        'field' => $fieldName,
                        'old' => $oldValue,
                        'new' => $newValue
                    ];
                }

                foreach ($audit->new_values as $field => $newValue) {
                    if (!isset($audit->old_values[$field])) {
                        $fieldName = $this->translateFieldName($field);

                        if ($field === 'status' || $field === 'manager_status' || $field === 'hr_status') {
                            $newValue = $this->translateStatus($newValue);
                        }

                        if ($field === 'user_id') {
                            $newValue = $newValue ? User::find($newValue)?->name . ' (' . $newValue . ')' : null;
                        }

                        $changes[] = [
                            'field' => $fieldName,
                            'old' => null,
                            'new' => $newValue
                        ];
                    }
                }

                $actionDescription = $this->getActionDescription($audit, $changes);

                return [
                    'id' => $audit->id,
                    'user' => $audit->user ? $audit->user->name : 'غير معروف',
                    'request_owner' => $requestOwner ? $requestOwner->name : 'غير معروف',
                    'action' => $actionType,
                    'action_description' => $actionDescription,
                    'model_type' => $modelType,
                    'model_id' => $audit->auditable_id,
                    'changes' => $changes,
                    'created_at' => $audit->created_at->format('Y-m-d H:i:s'),
                    'ip_address' => $audit->ip_address,
                    'user_agent' => $audit->user_agent,
                    'url' => $audit->url
                ];
            });

        // Get all users for the filter dropdown
        $users = User::all();

        // Get current month and year
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        return view('audit-log.index', compact('audits', 'users', 'currentMonth', 'currentYear'));
    }

    private function getActionDescription($audit, $changes)
    {
        $description = '';

        // Check if this is a status change
        $statusChange = collect($changes)->first(function ($change) {
            return in_array($change['field'], ['الحالة', 'حالة المدير', 'حالة الموارد البشرية']);
        });

        if ($statusChange) {
            $description = sprintf(
                'قام %s بتغيير %s من %s إلى %s',
                $audit->user ? $audit->user->name : 'مستخدم غير معروف',
                $statusChange['field'],
                $statusChange['old'] ?: 'غير محدد',
                $statusChange['new']
            );
        } else if ($audit->event === 'created') {
            $description = sprintf(
                'قام %s بإنشاء الطلب',
                $audit->user ? $audit->user->name : 'مستخدم غير معروف'
            );
        } else if ($audit->event === 'updated') {
            $description = sprintf(
                'قام %s بتحديث الطلب',
                $audit->user ? $audit->user->name : 'مستخدم غير معروف'
            );
        } else if ($audit->event === 'deleted') {
            $description = sprintf(
                'قام %s بحذف الطلب',
                $audit->user ? $audit->user->name : 'مستخدم غير معروف'
            );
        }

        return $description;
    }

    private function translateFieldName($field)
    {
        return match ($field) {
            'user_id' => 'المستخدم',
            'reviewer_id' => 'المُقيِّم',
            'status' => 'الحالة',
            'manager_status' => 'حالة المدير',
            'hr_status' => 'حالة الموارد البشرية',
            'reason' => 'السبب',
            'absence_date' => 'تاريخ الغياب',
            'departure_time' => 'وقت المغادرة',
            'return_time' => 'وقت العودة',
            'overtime_date' => 'تاريخ الوقت الإضافي',
            'start_time' => 'وقت البداية',
            'end_time' => 'وقت النهاية',
            'manager_rejection_reason' => 'سبب رفض المدير',
            'hr_rejection_reason' => 'سبب رفض الموارد البشرية',
            'minutes_used' => 'الدقائق المستخدمة',
            'returned_on_time' => 'العودة في الوقت المحدد',
            'review_month' => 'شهر التقييم',
            'total_score' => 'النتيجة الإجمالية',
            'total_after_deductions' => 'النتيجة بعد الخصومات',
            'total_salary' => 'إجمالي الراتب',
            'notes' => 'ملاحظات',
            'percentage' => 'النسبة المئوية',
            'sales_commission' => 'عمولة المبيعات',
            'sales_commission_percentage' => 'نسبة عمولة المبيعات',
            'sales_amount' => 'مبلغ المبيعات',
            // حقول المشاريع
            'name' => 'الاسم',
            'description' => 'الوصف',
            'client_id' => 'العميل',
            'package_id' => 'الباقة',
            'start_date' => 'تاريخ البداية',
            'end_date' => 'تاريخ النهاية',
            'is_urgent' => 'مستعجل',
            'total_points' => 'إجمالي النقاط',
            'manager' => 'المدير',
            'received_date' => 'تاريخ الاستلام',
            'preparation_start_date' => 'تاريخ بداية التحضير',
            'code' => 'الكود',
            'season_id' => 'الموسم',
            // حقول مرفقات المشاريع
            'project_id' => 'المشروع',
            'service_type' => 'نوع الخدمة',
            'file_path' => 'مسار الملف',
            'file_name' => 'اسم الملف',
            'uploaded_by' => 'رفع بواسطة',
            'is_uploaded' => 'تم الرفع',
            'task_type' => 'نوع المهمة',
            'template_task_user_id' => 'تخصيص قالب المهمة',
            'task_user_id' => 'تخصيص المهمة',
            'parent_attachment_id' => 'المرفق الأصلي',
            // حقول المهام
            'task_id' => 'المهمة',
            'service_id' => 'الخدمة',
            'task_template_id' => 'قالب المهمة',
            'estimated_hours' => 'الساعات المقدرة',
            'estimated_minutes' => 'الدقائق المقدرة',
            'actual_hours' => 'الساعات الفعلية',
            'actual_minutes' => 'الدقائق الفعلية',
            'due_date' => 'تاريخ الاستحقاق',
            'completed_date' => 'تاريخ الإكمال',
            'order' => 'الترتيب',
            'points' => 'النقاط',
            // حقول تخصيص المهام
            'role' => 'الدور',
            'started_at' => 'بدأ في',
            'paused_at' => 'توقف في',
            'completed_at' => 'اكتمل في',
            // حقول تخصيص التذاكر
            'ticket_id' => 'التذكرة',
            'assigned_by' => 'خصص بواسطة',
            'assigned_at' => 'خصص في',
            'unassigned_at' => 'ألغي التخصيص في',
            'assignment_notes' => 'ملاحظات التخصيص',
            'is_active' => 'نشط',
            // حقول قوالب المهام
            'template_task_id' => 'قالب المهمة',
            default => $field
        };
    }

    private function translateStatus($status)
    {
        return match ($status) {
            'pending' => 'قيد الانتظار',
            'approved' => 'موافق عليه',
            'rejected' => 'مرفوض',
            default => $status
        };
    }
}
