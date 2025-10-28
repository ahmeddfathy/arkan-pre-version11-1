<?php

namespace App\Http\Controllers\Traits;

use App\Models\PermissionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait PermissionRequestStatusTrait
{
    public function resetStatus(PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        if ($user->role !== 'manager') {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $this->permissionRequestService->resetStatus($permissionRequest, 'manager');

        return redirect()->route('permission-requests.index')
            ->with('success', 'Request status reset to pending successfully.');
    }

    public function modifyResponse(Request $request, PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        if ($user->id === $permissionRequest->user_id) {
            return redirect()->back()->with('error', 'لا يمكنك تعديل الرد على طلب الاستئذان الخاص بك');
        }

        if ($request->has('status')) {
            $status = $request->status;
            $rejectionReason = $status === 'rejected' ? $request->rejection_reason : null;

            if ($request->response_type === 'manager') {
                $permissionRequest->updateManagerStatus($status, $rejectionReason);
            } elseif ($request->response_type === 'hr') {
                $permissionRequest->updateHrStatus($status, $rejectionReason);
            }

            return redirect()->back()->with('success', 'تم تحديث الرد بنجاح');
        }

        return redirect()->back()->with('error', 'حدث خطأ أثناء تحديث الرد');
    }

    public function updateStatus(Request $request, PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        if ($user->hasRole('team_leader') && !$user->hasPermissionTo('manager_respond_permission_request')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية الرد على طلبات الاستئذان');
        }

        if ($user->hasRole('hr') && !$user->hasPermissionTo('hr_respond_permission_request')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية الرد على طلبات الاستئذان');
        }

        // منع المستخدم من الرد على طلباته الخاصة
        if ($user->id === $permissionRequest->user_id) {
            return redirect()->back()->with('error', 'لا يمكنك الرد على طلب الاستئذان الخاص بك');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'response_type' => 'required|in:manager,hr',
            'rejection_reason' => 'nullable|required_if:status,rejected|string|max:255',
        ]);

        // التحقق من الصلاحيات حسب نوع الرد
        if ($validated['response_type'] === 'manager') {
            // التحقق من صلاحية الرد كمدير
            if ($user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader']) && !$user->hasPermissionTo('manager_respond_permission_request')) {
                return redirect()->back()->with('error', 'ليس لديك صلاحية الرد على طلبات الاستئذان');
            }

            // التحقق من أن المستخدم إما مدير أو HR لديه صلاحية الرد كمدير
            if (
                !$user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager']) &&
                !($user->hasRole('hr') && $user->hasPermissionTo('manager_respond_permission_request'))
            ) {
                return redirect()->back()->with('error', 'ليس لديك صلاحية الرد على طلبات الاستئذان كمدير');
            }
        } elseif ($validated['response_type'] === 'hr') {
            // التحقق من صلاحية الرد كـ HR
            if (!$user->hasPermissionTo('hr_respond_permission_request')) {
                return redirect()->back()->with('error', 'ليس لديك صلاحية الرد على طلبات الاستئذان كموارد بشرية');
            }

            // التحقق من أن المستخدم HR
            if (!$user->hasRole('hr')) {
                return redirect()->back()->with('error', 'ليس لديك صلاحية الرد على طلبات الاستئذان كموارد بشرية');
            }
        }

        if ($validated['response_type'] === 'manager' && $user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager'])) {
            $permissionRequest->manager_status = $validated['status'];
            $permissionRequest->manager_rejection_reason = $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null;
        } elseif ($validated['response_type'] === 'hr' && $user->hasRole('hr')) {
            $permissionRequest->hr_status = $validated['status'];
            $permissionRequest->hr_rejection_reason = $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null;
        } else {
            return redirect()->back()->with('error', 'نوع الرد غير صحيح');
        }

        $permissionRequest->updateFinalStatus();
        $permissionRequest->save();

        return redirect()->back()->with('success', 'تم تحديث حالة الطلب بنجاح');
    }

    public function updateHrStatus(Request $request, PermissionRequest $permissionRequest)
    {
        // التحقق من صلاحية الرد على الطلب كـ HR
        if (!auth()->user()->hasPermissionTo('hr_respond_permission_request')) {
            abort(403, 'ليس لديك صلاحية الرد على طلبات الاستئذان كموارد بشرية');
        }

        try {
            $validated = $request->validate([
                'status' => 'required|in:approved,rejected',
                'rejection_reason' => 'nullable|required_if:status,rejected|string|max:255',
            ]);

            $user = Auth::user();

            // التحقق من أن المستخدم HR
            if (!$user->hasRole('hr')) {
                return back()->with('error', 'Unauthorized action.');
            }

            // تحديث حالة الرد كـ HR
            $permissionRequest->updateHrStatus($validated['status'], $validated['rejection_reason'] ?? null);
            $permissionRequest->save();

            $this->notificationService->createPermissionStatusUpdateNotification($permissionRequest);

            return back()->with('success', 'تم تحديث الرد بنجاح');
        } catch (\Exception $e) {
            \Log::error('Update HR Status Error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحديث الرد');
        }
    }

    public function modifyHrStatus(Request $request, PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        // منع المستخدم من الرد على طلباته الخاصة
        if ($user->id === $permissionRequest->user_id) {
            return redirect()->back()->with('error', 'لا يمكنك تعديل الرد على طلب الاستئذان الخاص بك');
        }

        if (!$user->hasRole('hr') || !$user->hasPermissionTo('hr_respond_permission_request')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية تعديل الرد على طلبات الاستئذان');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:255'
        ]);

        $permissionRequest->hr_status = $validated['status'];
        $permissionRequest->hr_rejection_reason = $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null;
        $permissionRequest->updateFinalStatus();
        $permissionRequest->save();

        $this->notificationService->notifyHRStatusUpdate($permissionRequest);

        return redirect()->back()->with('success', 'تم تعديل الرد بنجاح');
    }

    public function resetHrStatus(Request $request, PermissionRequest $permissionRequest)
    {
        // التحقق من صلاحية الرد على الطلب كـ HR
        if (!auth()->user()->hasPermissionTo('hr_respond_permission_request')) {
            abort(403, 'ليس لديك صلاحية إعادة تعيين الرد على طلبات الاستئذان كموارد بشرية');
        }

        $user = Auth::user();

        if (!$user->hasRole('hr') || !$user->hasPermissionTo('hr_respond_permission_request')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية إعادة تعيين الرد'
                ]);
            }
            return redirect()->back()->with('error', 'ليس لديك صلاحية إعادة تعيين الرد');
        }

        try {
            $this->permissionRequestService->resetStatus($permissionRequest, 'hr');

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إعادة تعيين الرد بنجاح'
                ]);
            }
            return redirect()->back()->with('success', 'تم إعادة تعيين الرد بنجاح');
        } catch (\Exception $e) {
            \Log::error('Reset HR Status Error: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء إعادة تعيين الرد'
                ]);
            }
            return redirect()->back()->with('error', 'حدث خطأ أثناء إعادة تعيين الرد');
        }
    }

    public function updateManagerStatus(Request $request, PermissionRequest $permissionRequest)
    {
        // التحقق من صلاحية الرد على الطلب كمدير
        if (!auth()->user()->hasPermissionTo('manager_respond_permission_request')) {
            abort(403, 'ليس لديك صلاحية الرد على طلبات الاستئذان كمدير');
        }

        try {
            $validated = $request->validate([
                'status' => 'required|in:approved,rejected',
                'rejection_reason' => 'nullable|required_if:status,rejected|string|max:255',
            ]);

            $user = Auth::user();

            // التحقق من أن المستخدم إما مدير أو HR لديه صلاحية الرد كمدير
            if (
                !$user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager']) &&
                !($user->hasRole('hr') && $user->hasPermissionTo('manager_respond_permission_request'))
            ) {
                return back()->with('error', 'Unauthorized action.');
            }

            // تحديث حالة الرد كمدير
            $permissionRequest->updateManagerStatus($validated['status'], $validated['rejection_reason'] ?? null);
            $permissionRequest->save();

            $this->notificationService->createPermissionStatusUpdateNotification($permissionRequest);

            return back()->with('success', 'تم تحديث الرد بنجاح');
        } catch (\Exception $e) {
            \Log::error('Update Manager Status Error: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحديث الرد');
        }
    }

    public function resetManagerStatus(PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        // منع المستخدم من الرد على طلباته الخاصة
        if ($user->id === $permissionRequest->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك إعادة تعيين الرد على طلب الاستئذان الخاص بك'
            ]);
        }

        // التحقق من أن المستخدم إما مدير أو HR لديه صلاحية الرد كمدير
        if (!$user->hasPermissionTo('manager_respond_permission_request')) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية إعادة تعيين الرد على طلبات الاستئذان كمدير'
            ]);
        }

        // التحقق من أن المستخدم إما مدير أو HR لديه صلاحية الرد كمدير
        if (
            !$user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager']) &&
            !($user->hasRole('hr') && $user->hasPermissionTo('manager_respond_permission_request'))
        ) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية إعادة تعيين الرد على طلبات الاستئذان كمدير'
            ]);
        }

        try {
            $this->permissionRequestService->resetStatus($permissionRequest, 'manager');
            return response()->json([
                'success' => true,
                'message' => 'تم إعادة تعيين الرد بنجاح'
            ]);
        } catch (\Exception $e) {
            \Log::error('Reset Manager Status Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إعادة تعيين الرد'
            ]);
        }
    }

    public function modifyManagerStatus(Request $request, PermissionRequest $permissionRequest)
    {
        $user = Auth::user();

        // منع المستخدم من الرد على طلباته الخاصة
        if ($user->id === $permissionRequest->user_id) {
            return redirect()->back()->with('error', 'لا يمكنك تعديل الرد على طلب الاستئذان الخاص بك');
        }

        // التحقق من أن المستخدم لديه صلاحية الرد كمدير
        if (!$user->hasPermissionTo('manager_respond_permission_request')) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية تعديل الرد على طلبات الاستئذان كمدير');
        }

        // التحقق من أن المستخدم إما مدير أو HR لديه صلاحية الرد كمدير
        if (
            !$user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager']) &&
            !($user->hasRole('hr') && $user->hasPermissionTo('manager_respond_permission_request'))
        ) {
            return redirect()->back()->with('error', 'ليس لديك صلاحية تعديل الرد على طلبات الاستئذان كمدير');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:255'
        ]);

        $permissionRequest->manager_status = $validated['status'];
        $permissionRequest->manager_rejection_reason = $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null;
        $permissionRequest->updateFinalStatus();
        $permissionRequest->save();

        $this->notificationService->notifyManagerStatusUpdate($permissionRequest);

        return redirect()->back()->with('success', 'تم تعديل الرد بنجاح');
    }
}
