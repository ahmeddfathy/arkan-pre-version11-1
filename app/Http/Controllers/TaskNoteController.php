<?php

namespace App\Http\Controllers;

use App\Models\TaskNote;
use App\Models\TaskUser;
use App\Models\TemplateTaskUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TaskNoteController extends Controller
{
    /**
     * الحصول على جميع الملاحظات لمهمة محددة للمستخدم الحالي
     */
    public function index($taskType, $taskUserId)
    {
        try {
            $query = TaskNote::forUser(Auth::id())
                           ->with('creator:id,name')
                           ->orderBy('created_at', 'desc');

            if ($taskType === 'regular') {
                $query->forRegularTask($taskUserId);
            } elseif ($taskType === 'template') {
                $query->forTemplateTask($taskUserId);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'نوع المهمة غير صحيح'
                ], 400);
            }

            $notes = $query->get();

            return response()->json([
                'success' => true,
                'notes' => $notes
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching task notes', [
                'task_type' => $taskType,
                'task_user_id' => $taskUserId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الملاحظات'
            ], 500);
        }
    }

    /**
     * إنشاء ملاحظة جديدة
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'task_type' => 'required|in:regular,template',
                'task_user_id' => 'nullable|exists:task_users,id',
                'template_task_user_id' => 'nullable|exists:template_task_user,id',
                'content' => 'required|string|max:1000',
            ]);

            // التحقق من صحة البيانات
            if ($request->task_type === 'regular' && !$request->task_user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'معرف المهمة العادية مطلوب'
                ], 400);
            }

            if ($request->task_type === 'template' && !$request->template_task_user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'معرف مهمة القالب مطلوب'
                ], 400);
            }

            // التحقق من أن المستخدم مخصص لهذه المهمة
            if ($request->task_type === 'regular') {
                $taskUser = TaskUser::where('id', $request->task_user_id)
                                   ->where('user_id', Auth::id())
                                   ->first();

                if (!$taskUser) {
                    return response()->json([
                        'success' => false,
                        'message' => 'غير مصرح لك بإضافة ملاحظات لهذه المهمة'
                    ], 403);
                }
            } else {
                $templateTaskUser = TemplateTaskUser::where('id', $request->template_task_user_id)
                                                   ->where('user_id', Auth::id())
                                                   ->first();

                if (!$templateTaskUser) {
                    return response()->json([
                        'success' => false,
                        'message' => 'غير مصرح لك بإضافة ملاحظات لهذه المهمة'
                    ], 403);
                }
            }

            // إنشاء الملاحظة
            $note = TaskNote::create([
                'task_type' => $request->task_type,
                'task_user_id' => $request->task_user_id,
                'template_task_user_id' => $request->template_task_user_id,
                'created_by' => Auth::id(),
                'content' => $request->content,
            ]);

            $note->load('creator:id,name');

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الملاحظة بنجاح',
                'note' => $note
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating task note', [
                'request_data' => $request->all(),
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة الملاحظة'
            ], 500);
        }
    }

    /**
     * تحديث ملاحظة موجودة
     */
    public function update(Request $request, TaskNote $taskNote)
    {
        try {
            // التحقق من أن المستخدم هو من أنشأ الملاحظة
            if ($taskNote->created_by !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بتعديل هذه الملاحظة'
                ], 403);
            }

            $request->validate([
                'content' => 'required|string|max:1000',
            ]);

            $taskNote->update([
                'content' => $request->content,
            ]);

            $taskNote->load('creator:id,name');

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الملاحظة بنجاح',
                'note' => $taskNote
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating task note', [
                'note_id' => $taskNote->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الملاحظة'
            ], 500);
        }
    }

    /**
     * حذف ملاحظة
     */
    public function destroy(TaskNote $taskNote)
    {
        try {
            // التحقق من أن المستخدم هو من أنشأ الملاحظة
            if ($taskNote->created_by !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بحذف هذه الملاحظة'
                ], 403);
            }

            $taskNote->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الملاحظة بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting task note', [
                'note_id' => $taskNote->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الملاحظة'
            ], 500);
        }
    }
}
