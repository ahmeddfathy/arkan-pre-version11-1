<?php

namespace App\Services\ProjectManagement;

use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProjectNotesService
{
    /**
     * جلب ملاحظات المشروع
     */
    public function getNotes($projectId, $query = null, $noteType = null, $userId = null, $perPage = 20, $targetDepartment = null)
    {
        $notes = ProjectNote::searchNotes($projectId, $query, $noteType, $userId, $targetDepartment)
            ->paginate($perPage);

        $notes->getCollection()->transform(function ($note) {
            return [
                'id' => $note->id,
                'content' => $note->content,
                'formatted_content' => $note->formatted_content,
                'note_type' => $note->note_type,
                'note_type_arabic' => $note->note_type_arabic,
                'note_type_icon' => $note->note_type_icon,
                'note_type_color' => $note->note_type_color,
                'is_important' => $note->is_important,
                'is_pinned' => $note->is_pinned,
                'mentions' => $note->mentions,
                'mentioned_users' => $note->mentionedUsers->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'avatar' => $user->profile_photo_path ?? asset('avatars/man.gif'),
                    ];
                }),
                'attachments' => $note->attachments,
                'created_at' => $note->created_at,
                'created_at_human' => $note->created_at->diffForHumans(),
                'created_at_formatted' => $note->created_at->format('d/m/Y - H:i'),
                'user' => [
                    'id' => $note->user->id,
                    'name' => $note->user->name,
                    'avatar' => $note->user->profile_photo_path ?? asset('avatars/man.gif'),
                    'department' => $note->user->department,
                ]
            ];
        });

        return $notes;
    }

    /**
     * إضافة ملاحظة جديدة
     */
    public function storeNote(Project $project, array $data)
    {
        try {
            // إنشاء الملاحظة أولاً بدون mentions لتسريع العملية
            $note = ProjectNote::create([
                'project_id' => $project->id,
                'user_id' => Auth::id(),
                'content' => $data['content'],
                'note_type' => $data['note_type'] ?? 'general',
                'mentions' => [], // سيتم تحديثها لاحقاً
                'is_important' => $data['is_important'] ?? false,
                'is_pinned' => $data['is_pinned'] ?? false,
                'target_department' => $data['target_department'] ?? null,
            ]);

            // تحميل العلاقات
            $note->load(['user']);

            // معالجة المستخدمين المذكورين في background فقط
            $this->processMentionsAsync($note, $data['content']);

            return $this->formatNoteData($note);

        } catch (\Exception $e) {
            Log::error('Error storing note: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * تحديث ملاحظة
     */
    public function updateNote(ProjectNote $note, array $data)
    {
        // استخراج المستخدمين المذكورين الجدد
        $newMentions = ProjectNote::extractMentions($data['content'], $note->project_id);
        $oldMentions = $note->mentions ?? [];

        $note->update([
            'content' => $data['content'],
            'note_type' => $data['note_type'] ?? $note->note_type,
            'mentions' => $newMentions,
            'is_important' => $data['is_important'] ?? $note->is_important,
            'is_pinned' => $data['is_pinned'] ?? $note->is_pinned,
        ]);

        // إرسال إشعارات للمستخدمين الجدد المذكورين
        $newlyMentioned = array_diff($newMentions, $oldMentions);
        if (!empty($newlyMentioned)) {
            $this->sendMentionNotifications($note, $newlyMentioned);
        }

        $note->load(['user']);

        return [
            'id' => $note->id,
            'formatted_content' => $note->formatted_content,
            'note_type_arabic' => $note->note_type_arabic,
            'is_important' => $note->is_important,
            'is_pinned' => $note->is_pinned,
            'updated_at' => $note->updated_at->diffForHumans(),
        ];
    }

    /**
     * حذف ملاحظة
     */
    public function deleteNote(ProjectNote $note)
    {
        $note->delete();
        return true;
    }

    /**
     * تبديل حالة تثبيت الملاحظة
     */
    public function toggleNotePin(ProjectNote $note)
    {
        $note->togglePin();
        return $note->is_pinned;
    }

    /**
     * تبديل حالة أهمية الملاحظة
     */
    public function toggleNoteImportant(ProjectNote $note)
    {
        $note->toggleImportant();
        return $note->is_important;
    }

    /**
     * جلب إحصائيات الملاحظات
     */
    public function getNotesStats($projectId)
    {
        return ProjectNote::getNotesStats($projectId);
    }

    /**
     * جلب مستخدمي المشروع للـ mentions
     */
    public function getProjectUsersForMentions(Project $project)
    {
        // جلب المشاركين في المشروع باستخدام DB query مباشرة
        return \Illuminate\Support\Facades\DB::table('users')
            ->join('project_service_user', 'users.id', '=', 'project_service_user.user_id')
            ->where('project_service_user.project_id', $project->id)
            ->select('users.id', 'users.name', 'users.profile_photo_path', 'users.department')
            ->orderBy('users.name')
            ->distinct()
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->profile_photo_path ?? asset('avatars/man.gif'),
                    'department' => $user->department ?? '',
                ];
            });
    }

    /**
     * إعداد البيانات للاستجابة
     */
    private function formatNoteData(ProjectNote $note)
    {
        return [
            'id' => $note->id,
            'content' => $note->content,
            'formatted_content' => $note->formatted_content,
            'note_type' => $note->note_type,
            'note_type_arabic' => $note->note_type_arabic,
            'note_type_icon' => $note->note_type_icon,
            'note_type_color' => $note->note_type_color,
            'is_important' => $note->is_important,
            'is_pinned' => $note->is_pinned,
            'mentions' => $note->mentions,
            'mentioned_users' => $note->mentionedUsers->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->profile_photo_path ?? asset('avatars/man.gif')
                ];
            }),
            'created_at' => $note->created_at,
            'created_at_human' => $note->created_at->diffForHumans(),
            'created_at_formatted' => $note->created_at->format('d/m/Y - H:i'),
            'user' => [
                'id' => $note->user->id,
                'name' => $note->user->name,
                'avatar' => $note->user->profile_photo_path ?? asset('avatars/man.gif'),
                'department' => $note->user->department,
            ]
        ];
    }

    /**
     * معالجة المذكورين بطريقة غير متزامنة
     */
    private function processMentionsAsync(ProjectNote $note, string $content)
    {
        // استخدام timeout محدود للـ mentions
        try {
            // استخراج المستخدمين المذكورين بسرعة (يشمل المستخدمين الفرديين والأقسام)
            $mentions = $this->extractMentionsFast($content, $note->project_id);

            if (!empty($mentions)) {
                // تحديث الملاحظة بالـ mentions
                $note->update(['mentions' => $mentions]);

                // إرسال الإشعارات في background (بدون انتظار)
                $this->sendMentionNotificationsAsync($note, $mentions);
            }
        } catch (\Exception $e) {
            Log::error('Error processing mentions: ' . $e->getMessage(), [
                'note_id' => $note->id
            ]);
        }
    }

    /**
     * استخراج المذكورين بطريقة سريعة (المستخدمين الفرديين والأقسام)
     */
    private function extractMentionsFast(string $text, int $projectId): array
    {
        // فحص سريع: إذا لم يكن هناك @ فلا داعي للـ query
        if (!str_contains($text, '@')) {
            return [];
        }

        // query محسن للمشاركين
        $projectUsers = \Illuminate\Support\Facades\Cache::remember(
            "project_users_{$projectId}",
            300, // 5 دقائق cache
            function () use ($projectId) {
                return \Illuminate\Support\Facades\DB::table('users')
                    ->join('project_service_user', 'users.id', '=', 'project_service_user.user_id')
                    ->where('project_service_user.project_id', $projectId)
                    ->select('users.id', 'users.name', 'users.department')
                    ->distinct()
                    ->get()
                    ->toArray();
            }
        );

        $mentions = [];

        // البحث عن المستخدمين الفرديين
        foreach ($projectUsers as $user) {
            // البحث عن اسم المستخدم مسبوق بـ @
            if (preg_match('/@' . preg_quote($user->name, '/') . '(?=\s|$|[^\w])/', $text)) {
                $mentions[] = $user->id;
            }
        }

        // البحث عن mentions الأقسام
        $departments = array_unique(array_filter(array_column($projectUsers, 'department')));
        foreach ($departments as $department) {
            if (preg_match('/@' . preg_quote($department, '/') . '(?=\s|$|[^\w])/', $text)) {
                // إضافة كل المستخدمين من هذا القسم
                foreach ($projectUsers as $user) {
                    if ($user->department === $department) {
                        $mentions[] = $user->id;
                    }
                }
            }
        }

        return array_unique($mentions);
    }

    /**
     * إرسال إشعارات المذكورين بطريقة غير متزامنة
     */
    private function sendMentionNotificationsAsync(ProjectNote $note, array $mentionedUserIds)
    {
        // استخدام setTimeout للتأكد من عدم تعليق العملية
        try {
            // إجراء العملية في background باستخدام process fork
            if (function_exists('pcntl_fork')) {
                $pid = pcntl_fork();
                if ($pid == 0) {
                    // Child process - إرسال الإشعارات
                    $this->sendMentionNotifications($note, $mentionedUserIds);
                    exit(0);
                }
                // Parent process - إرجاع فوري
                return;
            }

            // Fallback: إرسال مباشر مع timeout
            $this->sendMentionNotifications($note, $mentionedUserIds);

        } catch (\Exception $e) {
            Log::error('Error in async notifications: ' . $e->getMessage());
        }
    }

    /**
     * إرسال إشعارات للمستخدمين المذكورين
     */
    private function sendMentionNotifications(ProjectNote $note, array $mentionedUserIds)
    {
        try {
            foreach ($mentionedUserIds as $userId) {
                // تجنب إرسال إشعار للمستخدم نفسه
                if ($userId == $note->user_id) {
                    continue;
                }

                // إنشاء إشعار في قاعدة البيانات
                $notification = new \App\Models\Notification();
                $notification->user_id = $userId;
                $notification->type = 'project_mention';
                $notification->data = json_encode([
                    'project_id' => $note->project_id,
                    'project_name' => $note->project->name,
                    'note_id' => $note->id,
                    'mentioned_by_id' => $note->user_id,
                    'mentioned_by_name' => $note->user->name,
                    'note_preview' => substr(strip_tags($note->content), 0, 100) . '...',
                    'note_type' => $note->note_type_arabic,
                    'created_at' => $note->created_at->format('Y-m-d H:i:s'),
                    'url' => route('projects.show', $note->project_id) . '#note-' . $note->id
                ]);
                $notification->save();

                // إرسال إشعار سلاك بدون انتظار
                try {
                    $user = User::find($userId);
                    if ($user && $user->slack_user_id) {
                        $author = Auth::user();
                        app(\App\Services\SlackNotificationService::class)
                            ->sendProjectNoteMention($note, $user, $author);
                    }
                } catch (\Exception $slackError) {
                    // تجاهل أخطاء Slack
                    Log::warning('Slack notification failed: ' . $slackError->getMessage());
                }
            }

        } catch (\Exception $e) {
            // تسجيل الخطأ ولكن عدم إيقاف العملية
            Log::error('Failed to send mention notifications: ' . $e->getMessage(), [
                'note_id' => $note->id,
                'mentioned_users' => $mentionedUserIds
            ]);
        }
    }

}
