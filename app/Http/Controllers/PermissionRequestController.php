<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PermissionRequest;
use App\Services\PermissionRequest\PermissionRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Violation;
use App\Services\NotificationPermissionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Traits\PermissionRequestIndexTrait;
use App\Http\Controllers\Traits\PermissionRequestManagementTrait;
use App\Http\Controllers\Traits\PermissionRequestStatusTrait;
use App\Http\Controllers\Traits\PermissionRequestReturnStatusTrait;
use App\Http\Controllers\Traits\PermissionRequestStatisticsTrait;

class PermissionRequestController extends Controller
{
    use PermissionRequestIndexTrait;
    use PermissionRequestManagementTrait;
    use PermissionRequestStatusTrait;
    use PermissionRequestReturnStatusTrait;
    use PermissionRequestStatisticsTrait;

    protected $permissionRequestService;
    protected $notificationService;

    public function __construct(PermissionRequestService $permissionRequestService, NotificationPermissionService $notificationService)
    {
        $this->permissionRequestService = $permissionRequestService;
        $this->notificationService = $notificationService;
    }

    /**
     * Display a specific permission request.
     */
    public function show(PermissionRequest $permissionRequest)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // تسجيل النشاط - عرض تفاصيل طلب الإذن
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->performedOn($permissionRequest)
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'permission_request_id' => $permissionRequest->id,
                    'request_user_id' => $permissionRequest->user_id,
                    'request_user_name' => $permissionRequest->user->name ?? null,
                    'departure_time' => $permissionRequest->departure_time,
                    'return_time' => $permissionRequest->return_time,
                    'reason' => $permissionRequest->reason,
                    'status' => $permissionRequest->status,
                    'action_type' => 'view',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('شاهد تفاصيل طلب الإذن');
        }

        // التحقق من الصلاحيات
        $canView = false;

        // صاحب الطلب يمكنه رؤية طلبه
        if ($user->id === $permissionRequest->user_id) {
            $canView = true;
        }
        // HR يمكنهم رؤية جميع الطلبات
        elseif ($user->hasRole('hr')) {
            $canView = true;
        }
        // مالكي الفرق يمكنهم رؤية طلبات أعضاء فريقهم
        elseif ($user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager'])) {
            // التحقق من أن الموظف في فريق المستخدم الحالي
            if ($user->currentTeam && $user->currentTeam->users->contains('id', $permissionRequest->user_id)) {
                $canView = true;
            }
        }

        if (!$canView) {
            abort(403, 'غير مصرح لك بعرض هذا الطلب');
        }

        // تحميل العلاقات المطلوبة
        $permissionRequest->load('user');

        // التحقق من الصلاحيات للإجراءات

        // صاحب الطلب فقط يستطيع تعديل الطلب نفسه وفقط إذا لم يرد المدير أو HR
        $canUpdateRequest = $user->hasPermissionTo('update_permission') &&
                           ($user->id === $permissionRequest->user_id) &&
                           ($permissionRequest->manager_status === 'pending' && $permissionRequest->hr_status === 'pending');

                // صاحب الطلب فقط يستطيع حذف الطلب وفقط إذا لم يرد المدير أو HR
        $canDelete = $user->hasPermissionTo('delete_permission') &&
                    ($user->id === $permissionRequest->user_id) &&
                    ($permissionRequest->manager_status === 'pending' && $permissionRequest->hr_status === 'pending');

        // المدير يستطيع الرد الأولي أو تعديل رده
        $canRespondAsManager = $user->hasPermissionTo('manager_respond_permission_request') &&
                              $user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager']) &&
                              $user->currentTeam && $user->currentTeam->users->contains('id', $permissionRequest->user_id);

        // المدير يستطيع تعديل رده إذا كان قد رد من قبل
        $canModifyManagerResponse = $canRespondAsManager && $permissionRequest->manager_status !== 'pending';

        // HR يستطيع الرد الأولي أو تعديل رده
        $canRespondAsHR = $user->hasPermissionTo('hr_respond_permission_request') && $user->hasRole('hr');

        // HR يستطيع تعديل رده إذا كان قد رد من قبل
        $canModifyHRResponse = $canRespondAsHR && $permissionRequest->hr_status !== 'pending';

        // الحصول على قائمة المستخدمين للـ modals
        $users = collect();
        if ($user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager'])) {
            if ($user->currentTeam) {
                $users = $user->currentTeam->users;
            }
        } elseif ($user->hasRole('hr')) {
            $users = User::whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['company_manager', 'hr']);
            });

            if ($user->currentTeam) {
                $teamMembersIds = $user->currentTeam->users->pluck('id')->toArray();
                $users = $users->orWhereIn('id', $teamMembersIds);
            }

            $users = $users->get();
        } else {
            $users = collect([$user]);
        }

        return view('permission-requests.show', compact(
            'permissionRequest',
            'canUpdateRequest',
            'canDelete',
            'canRespondAsManager',
            'canModifyManagerResponse',
            'canRespondAsHR',
            'canModifyHRResponse',
            'users'
        ));
    }
}
