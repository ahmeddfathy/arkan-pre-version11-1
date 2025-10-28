<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProjectManagement\AttachmentConfirmationService;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class AttachmentConfirmationController extends Controller
{
    protected $confirmationService;

    public function __construct(AttachmentConfirmationService $confirmationService)
    {
        $this->confirmationService = $confirmationService;
    }

    /**
     * عرض صفحة طلبات التأكيد للمسؤول مع النظام الهرمي
     */
    public function index(Request $request)
    {
        $status = $request->get('status');
        $projectId = $request->get('project_id');
        $month = $request->get('month');

        $confirmations = $this->confirmationService->getManagerConfirmations(
            Auth::id(),
            $status,
            $projectId,
            $month
        );

        $statistics = $this->confirmationService->getStatistics();

        // جلب المشاريع المفلترة حسب النظام الهرمي
        $projects = $this->confirmationService->getFilteredProjects(Auth::user());

        return view('attachment-confirmations.index', compact(
            'confirmations',
            'statistics',
            'projects',
            'status',
            'projectId',
            'month'
        ));
    }

    /**
     * طلب تأكيد مرفق
     */
    public function requestConfirmation(Request $request, $attachmentId)
    {
        $request->validate([
            'file_type' => 'nullable|string|max:100'
        ]);

        $result = $this->confirmationService->requestConfirmation(
            $attachmentId,
            $request->file_type
        );

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * تأكيد المرفق
     */
    public function confirm(Request $request, $confirmationId)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000'
        ]);

        $result = $this->confirmationService->confirmAttachment(
            $confirmationId,
            $request->notes
        );

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * رفض المرفق
     */
    public function reject(Request $request, $confirmationId)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000'
        ]);

        $result = $this->confirmationService->rejectAttachment(
            $confirmationId,
            $request->notes
        );

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * جلب طلبات التأكيد الخاصة بالمستخدم (التي أرسلها)
     */
    public function myRequests(Request $request)
    {
        $status = $request->get('status');
        $projectId = $request->get('project_id');
        $month = $request->get('month');

        $confirmations = $this->confirmationService->getUserConfirmations(
            Auth::id(),
            $status,
            $projectId,
            $month
        );

        // حساب الإحصائيات للطلبات المرسلة من المستخدم
        $statistics = $this->confirmationService->getUserStatistics(Auth::id());

        // جلب المشاريع المفلترة حسب النظام الهرمي
        $projects = $this->confirmationService->getFilteredProjects(Auth::user());

        return view('attachment-confirmations.my-requests', compact(
            'confirmations',
            'statistics',
            'projects',
            'status',
            'projectId',
            'month'
        ));
    }

    /**
     * إعادة تعيين طلب التأكيد
     */
    public function reset(Request $request, $confirmationId)
    {
        $result = $this->confirmationService->resetConfirmation($confirmationId);

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * التحقق من حالة تأكيد المرفق
     */
    public function checkStatus($attachmentId)
    {
        $status = $this->confirmationService->getAttachmentConfirmationStatus($attachmentId);

        return response()->json([
            'success' => true,
            'status' => $status
        ]);
    }
}
