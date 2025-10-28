<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class ProjectNote extends Model
{
    use HasFactory, HasSecureId, LogsActivity;

    protected $fillable = [
        'project_id',
        'user_id',
        'content',
        'mentions',
        'note_type',
        'is_important',
        'is_pinned',
        'attachments',
        'target_department',
    ];

    protected $casts = [
        'mentions' => 'array',
        'attachments' => 'array',
        'is_important' => 'boolean',
        'is_pinned' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'project_id', 'user_id', 'content', 'mentions', 'note_type',
                'is_important', 'is_pinned', 'attachments', 'target_department'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إضافة ملاحظة جديدة للمشروع',
                'updated' => 'تم تحديث ملاحظة المشروع',
                'deleted' => 'تم حذف ملاحظة المشروع',
                default => $eventName
            });
    }

    /**
     * العلاقة مع المشروع
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * العلاقة مع المستخدم
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * الحصول على المستخدمين المذكورين كـ accessor
     */
    public function getMentionedUsersAttribute()
    {
        if (!$this->mentions || empty($this->mentions)) {
            return collect();
        }

        return User::whereIn('id', $this->mentions)->get();
    }

    /**
     * استخراج @ mentions من النص
     */
    public static function extractMentions($text, $projectId)
    {
        // جلب مشاركي المشروع
        $projectUsers = DB::table('users')
            ->join('project_service_user', 'users.id', '=', 'project_service_user.user_id')
            ->where('project_service_user.project_id', $projectId)
            ->select('users.id', 'users.name')
            ->distinct()
            ->get();

        $mentions = [];
        foreach ($projectUsers as $user) {
            // البحث عن اسم المستخدم مسبوق بـ @
            if (preg_match('/@' . preg_quote($user->name, '/') . '(?=\s|$|[^\w])/', $text)) {
                $mentions[] = $user->id;
            }
        }

        return array_unique($mentions);
    }

    /**
     * تنسيق المحتوى مع إبراز المذكورين
     */
    public function getFormattedContentAttribute(): string
    {
        $content = $this->content;

        if ($this->mentions && !empty($this->mentions)) {
            $mentionedUsers = $this->mentioned_users;

            foreach ($mentionedUsers as $user) {
                $pattern = '/@' . preg_quote($user->name, '/') . '(?=\s|$|[^\w])/';
                $replacement = '<span class="mention" data-user-id="' . $user->id . '">@' . $user->name . '</span>';
                $content = preg_replace($pattern, $replacement, $content);
            }
        }

        // تحويل الروابط إلى روابط قابلة للنقر
        $content = preg_replace(
            '/(https?:\/\/[^\s]+)/',
            '<a href="$1" target="_blank" class="text-primary">$1</a>',
            $content
        );

        // تحويل أسطر جديدة إلى <br>
        $content = nl2br(e($content));

        return $content;
    }

    /**
     * الحصول على أيقونة نوع الملاحظة
     */
    public function getNoteTypeIconAttribute(): string
    {
        return match($this->note_type) {
            'update' => 'fas fa-sync-alt text-primary',
            'issue' => 'fas fa-exclamation-triangle text-danger',
            'question' => 'fas fa-question-circle text-info',
            'solution' => 'fas fa-lightbulb text-success',
            default => 'fas fa-sticky-note text-secondary'
        };
    }

    /**
     * الحصول على اسم نوع الملاحظة بالعربية
     */
    public function getNoteTypeArabicAttribute(): string
    {
        return match($this->note_type) {
            'update' => 'تحديث',
            'issue' => 'مشكلة',
            'question' => 'سؤال',
            'solution' => 'حل',
            default => 'عام'
        };
    }

    /**
     * الحصول على لون نوع الملاحظة
     */
    public function getNoteTypeColorAttribute(): string
    {
        return match($this->note_type) {
            'update' => 'primary',
            'issue' => 'danger',
            'question' => 'info',
            'solution' => 'success',
            default => 'secondary'
        };
    }

    /**
     * البحث في الملاحظات
     */
    public static function searchNotes($projectId, $query = null, $noteType = null, $userId = null, $targetDepartment = null)
    {
        $notesQuery = static::where('project_id', $projectId)
            ->with(['user'])
            ->when($query, function($q, $search) {
                $q->where('content', 'LIKE', "%{$search}%");
            })
            ->when($noteType, function($q, $type) {
                $q->where('note_type', $type);
            })
            ->when($userId, function($q, $user) {
                $q->where('user_id', $user);
            })
            ->when($targetDepartment, function($q, $department) {
                $q->where('target_department', $department);
            })
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc');

        return $notesQuery;
    }

    /**
     * الحصول على إحصائيات الملاحظات
     */
    public static function getNotesStats($projectId)
    {
        $stats = static::where('project_id', $projectId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN is_important = 1 THEN 1 ELSE 0 END) as important,
                SUM(CASE WHEN is_pinned = 1 THEN 1 ELSE 0 END) as pinned,
                SUM(CASE WHEN note_type = "issue" THEN 1 ELSE 0 END) as issues,
                SUM(CASE WHEN note_type = "solution" THEN 1 ELSE 0 END) as solutions
            ')
            ->first();

        return [
            'total' => $stats->total ?? 0,
            'important' => $stats->important ?? 0,
            'pinned' => $stats->pinned ?? 0,
            'issues' => $stats->issues ?? 0,
            'solutions' => $stats->solutions ?? 0,
            'recent' => static::where('project_id', $projectId)
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->count()
        ];
    }

    /**
     * تبديل حالة التثبيت
     */
    public function togglePin()
    {
        $this->update(['is_pinned' => !$this->is_pinned]);
        return $this;
    }

    /**
     * تبديل حالة الأهمية
     */
    public function toggleImportant()
    {
        $this->update(['is_important' => !$this->is_important]);
        return $this;
    }

    /**
     * الحصول على الملاحظات الأخيرة
     */
    public static function getRecentNotes($projectId, $limit = 5)
    {
        return static::where('project_id', $projectId)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * أنواع الملاحظات المتاحة
     */
    public static function getNoteTypes(): array
    {
        return [
            'general' => 'عام',
            'update' => 'تحديث',
            'issue' => 'مشكلة',
            'question' => 'سؤال',
            'solution' => 'حل'
        ];
    }
}
