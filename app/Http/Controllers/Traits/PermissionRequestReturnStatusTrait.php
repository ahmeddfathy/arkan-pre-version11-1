<?php

namespace App\Http\Controllers\Traits;

use App\Models\PermissionRequest;
use App\Models\Violation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

trait PermissionRequestReturnStatusTrait
{
    public function updateReturnStatus(Request $request, PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        // تحقق من الصلاحيات
        if (
            !$user->hasRole(['hr', 'team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager']) &&
            $user->id !== $permissionRequest->user_id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
            ], 403);
        }

        $validated = $request->validate([
            'return_status' => 'required|in:0,1,2',
        ]);

        try {
            $now = Carbon::now()->setTimezone('Africa/Cairo');
            $returnTime = Carbon::parse($permissionRequest->return_time);
            $maxReturnTime = $returnTime->copy();
            $endOfWorkDay = Carbon::now()->setTimezone('Africa/Cairo')->setTime(16, 0, 0);

            // إذا كان المستخدم مدير أو HR، نسمح له بتسجيل العودة بغض النظر عن الوقت
            $isManager = $user->hasRole(['hr', 'team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager']);

            if ($returnTime->gte($endOfWorkDay)) {
                $permissionRequest->returned_on_time = true;
                $permissionRequest->updateActualMinutesUsed();
                $permissionRequest->save();

                return response()->json([
                    'success' => true,
                    'message' => 'تم تسجيل العودة تلقائياً لانتهاء يوم العمل'
                ]);
            }

            // معالجة إعادة التعيين (return_status = 0)
            if ($validated['return_status'] == 0) {
                $permissionRequest->returned_on_time = 0;
                $permissionRequest->updateActualMinutesUsed();
                $permissionRequest->save();

                return response()->json([
                    'success' => true,
                    'message' => 'تم إعادة تعيين حالة العودة بنجاح',
                    'actual_minutes_used' => $permissionRequest->minutes_used
                ]);
            }
            // تسجيل العودة (return_status = 1)
            else if ($validated['return_status'] == 1) {
                // تخطي التحقق من الوقت للمدراء وHR
                if (!$isManager && !$permissionRequest->canMarkAsReturned($user)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'لقد تجاوزت الوقت المسموح به للعودة'
                    ]);
                }

                $isOnTime = $now->lte($maxReturnTime);
                $permissionRequest->returned_on_time = true;
                $permissionRequest->updateActualMinutesUsed();
                $permissionRequest->save();

                // إضافة مخالفة فقط إذا كان متأخراً وليس مديراً
                if (!$isOnTime && !$isManager) {
                    Violation::create([
                        'user_id' => $permissionRequest->user_id,
                        'permission_requests_id' => $permissionRequest->id,
                        'reason' => 'تسجيل العودة من الاستئذان بعد الموعد المحدد',
                        'manager_mistake' => false
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => $isOnTime ? 'تم تسجيل العودة بنجاح' : 'تم تسجيل العودة، لكن بعد انتهاء الوقت المحدد'
                ]);
            }
            // تسجيل عدم العودة (return_status = 2)
            else if ($validated['return_status'] == 2) {
                $permissionRequest->returned_on_time = 2;
                $permissionRequest->updateActualMinutesUsed();
                $permissionRequest->save();

                Violation::create([
                    'user_id' => $permissionRequest->user_id,
                    'permission_requests_id' => $permissionRequest->id,
                    'reason' => 'عدم العودة من الاستئذان في الوقت المحدد',
                    'manager_mistake' => false
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'تم تسجيل عدم العودة بنجاح',
                    'actual_minutes_used' => $permissionRequest->minutes_used
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'قيمة غير صالحة لحالة العودة'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث حالة العودة: ' . $e->getMessage()
            ], 500);
        }
    }
}
