<?php

namespace App\Http\Controllers\Traits;

use App\Models\PermissionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait PermissionRequestManagementTrait
{
    public function store(Request $request)
    {
        if (!Auth::user()->hasPermissionTo('create_permission')) {
            abort(403, 'ليس لديك صلاحية تقديم طلب استئذان');
        }

        $user = Auth::user();

        $validated = $request->validate([
            'departure_time' => 'required|date|after:now',
            'return_time' => 'required|date|after:departure_time',
            'reason' => 'required|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'registration_type' => 'nullable|in:self,other'
        ]);

        if ($request->input('registration_type') === 'other' && $request->filled('user_id') && $request->input('user_id') != $user->id) {
            $result = $this->permissionRequestService->createRequestForUser($request->input('user_id'), $validated);
        } else {
            $result = $this->permissionRequestService->createRequest($validated);
        }

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        $message = $result['message'] ?? 'تم إنشاء طلب الاستئذان بنجاح.';

        if (isset($result['used_minutes'])) {
            if (isset($result['exceeded_limit']) && $result['exceeded_limit']) {
                $message = "تنبيه: لقد تجاوزت الحد المجاني للاستئذان الشهري المسموح به (180 دقيقة). سيتم احتساب الدقائق الإضافية على حسابك.";
            } else {
                $message .= " إجمالي الدقائق المستخدمة هذا الشهر: {$result['used_minutes']} دقيقة.";
            }
        }

        return redirect()->route('permission-requests.index')->with('success', $message);
    }

    public function update(Request $request, PermissionRequest $permissionRequest)
    {
        // التحقق من صلاحية تعديل طلب الاستئذان
        if (!auth()->user()->hasPermissionTo('update_permission')) {
            abort(403, 'ليس لديك صلاحية تعديل طلب الاستئذان');
        }

        // التحقق من أن الطلب في حالة pending وأن المستخدم هو صاحب الطلب
        if ($permissionRequest->status !== 'pending' || auth()->id() !== $permissionRequest->user_id) {
            abort(403, 'لا يمكن تعديل هذا الطلب');
        }

        $user = Auth::user();

        if ($user->role !== 'manager' && $user->id !== $permissionRequest->user_id) {
            return redirect()->route('welcome')->with('error', 'Unauthorized action.');
        }

        $validated = $request->validate([
            'departure_time' => 'required|date|after:now',
            'return_time' => 'required|date|after:departure_time',
            'reason' => 'required|string|max:255',
            'returned_on_time' => 'nullable|boolean',
            'minutes_used' => 'nullable|integer'
        ]);

        $result = $this->permissionRequestService->updateRequest($permissionRequest, $validated);

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        $message = 'تم تحديث طلب الاستئذان بنجاح.';
        if (isset($result['used_minutes'])) {
            if (isset($result['exceeded_limit']) && $result['exceeded_limit']) {
                $message = "تنبيه: لقد تجاوزت الحد المجاني للاستئذان الشهري المسموح به (180 دقيقة). سيتم احتساب الدقائق الإضافية على حسابك.";
            } else {
                $message .= " إجمالي الدقائق المستخدمة هذا الشهر: {$result['used_minutes']} دقيقة.";
                if (isset($result['remaining_minutes']) && $result['remaining_minutes'] > 0) {
                    $message .= " الدقائق المتبقية: {$result['remaining_minutes']} دقيقة.";
                }
            }
        }

        return redirect()->route('permission-requests.index')
            ->with('success', $message);
    }

    public function destroy(PermissionRequest $permissionRequest)
    {
        try {
            // التحقق من صلاحية حذف طلب الاستئذان
            if (!auth()->user()->hasPermissionTo('delete_permission')) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية حذف طلب الاستئذان'
                ], 403);
            }

            // التحقق من أن الطلب في حالة pending وأن المستخدم هو صاحب الطلب
            if ($permissionRequest->status !== 'pending' || auth()->id() !== $permissionRequest->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف هذا الطلب'
                ], 403);
            }

            $user = Auth::user();

            // التحقق من حالة الموافقة من المدير أو HR
            if ($permissionRequest->manager_status !== 'pending' || $permissionRequest->hr_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف الطلب لأنه تمت الموافقة عليه أو رفضه من قبل المدير أو HR'
                ], 403);
            }

            try {
                $this->permissionRequestService->deleteRequest($permissionRequest);

                if (request()->ajax() || request()->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'تم حذف الطلب بنجاح'
                    ]);
                }

                return redirect()->route('permission-requests.index')
                    ->with('success', 'تم حذف الطلب بنجاح');
            } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                if (request()->ajax() || request()->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $e->getMessage()
                    ], 403);
                }

                return redirect()->route('permission-requests.index')
                    ->with('error', $e->getMessage());
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error deleting permission request: ' . $e->getMessage());

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء حذف الطلب: ' . $e->getMessage()
                ]);
            }

            return redirect()->route('permission-requests.index')
                ->with('error', 'حدث خطأ أثناء حذف الطلب: ' . $e->getMessage());
        }
    }
}
