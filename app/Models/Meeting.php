<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;

class Meeting extends Model
{
    use HasFactory, SoftDeletes, HasSecureId, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'description',
        'start_time',
        'end_time',
        'location',
        'type',
        'created_by',
        'client_id',
        'project_id',
        'notes',
        'is_completed',
        'status',
        'approval_status',
        'approved_by',
        'approved_at',
        'approval_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'approved_at' => 'datetime',
        'notes' => 'array',
        'is_completed' => 'boolean',
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'title', 'description', 'start_time', 'end_time', 'location', 'type',
                'created_by', 'client_id', 'project_id', 'is_completed', 'status',
                'approval_status', 'approved_by', 'approved_at'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إنشاء اجتماع جديد',
                'updated' => 'تم تحديث بيانات الاجتماع',
                'deleted' => 'تم حذف الاجتماع',
                default => $eventName
            });
    }

    /**
     * Get the creator of the meeting.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the client associated with the meeting.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the project associated with the meeting.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who approved the meeting.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the participants of the meeting.
     */
    public function participants()
    {
        return $this->belongsToMany(User::class, 'meeting_participants')
            ->withPivot('attended')
            ->withTimestamps();
    }

    /**
     * Check if the meeting is internal.
     */
    public function isInternal()
    {
        return $this->type === 'internal';
    }

    /**
     * Check if the meeting is for a client.
     */
    public function isClientMeeting()
    {
        return $this->type === 'client';
    }

    /**
     * Check if the meeting needs approval.
     */
    public function needsApproval()
    {
        return $this->isClientMeeting() && $this->approval_status === 'pending';
    }

    /**
     * Check if the meeting is approved.
     */
    public function isApproved()
    {
        return in_array($this->approval_status, ['approved', 'auto_approved']);
    }

    /**
     * Check if the meeting is rejected.
     */
    public function isRejected()
    {
        return $this->approval_status === 'rejected';
    }

    /**
     * Check if the meeting is scheduled.
     */
    public function isScheduled()
    {
        return $this->status === 'scheduled';
    }

    /**
     * Check if the meeting is cancelled.
     */
    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if the meeting is completed.
     */
    public function isCompleted()
    {
        return $this->status === 'completed' || $this->is_completed;
    }

    /**
     * Cancel the meeting.
     */
    public function cancel()
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Mark meeting as completed.
     */
    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'is_completed' => true
        ]);
    }

    /**
     * Get status in Arabic.
     */
    public function getStatusArabicAttribute(): string
    {
        return match($this->status) {
            'scheduled' => 'مجدول',
            'cancelled' => 'ملغي',
            'completed' => 'مكتمل',
            default => $this->status
        };
    }

    /**
     * Get approval status in Arabic.
     */
    public function getApprovalStatusArabicAttribute(): string
    {
        return match($this->approval_status) {
            'pending' => 'في انتظار الموافقة',
            'approved' => 'موافق عليه',
            'rejected' => 'مرفوض',
            'auto_approved' => 'معتمد تلقائياً',
            default => $this->approval_status
        };
    }

    /**
     * Add a note to the meeting.
     *
     * @param int $userId
     * @param string $content
     * @param array $mentions
     * @return void
     */
    public function addNote($userId, $content, $mentions = [])
    {
        $notes = $this->notes ?? [];
        $notes[] = [
            'user_id' => $userId,
            'content' => $content,
            'mentions' => $mentions,
            'created_at' => now()->toIso8601String(),
        ];

        $this->notes = $notes;
        $this->save();
    }

    /**
     * Extract mentions from note content
     */
    public static function extractNoteMentions($text, $meetingId)
    {
        $meeting = self::with(['participants', 'creator'])->find($meetingId);
        $availableUsers = collect();

        // إضافة المشاركين في الاجتماع
        if ($meeting->participants) {
            $availableUsers = $availableUsers->merge($meeting->participants);
        }

        // إضافة منشئ الاجتماع
        if ($meeting->creator) {
            $availableUsers->push($meeting->creator);
        }

        // إزالة التكرارات
        $users = $availableUsers->unique('id');

        $mentions = [];

        // التحقق من منشن الجميع (@everyone أو @الجميع)
        if (strpos($text, '@everyone') !== false || strpos($text, '@الجميع') !== false) {
            // إضافة جميع المستخدمين المتاحين
            $mentions = $users->pluck('id')->toArray();
        } else {
            // البحث عن منشن الأفراد
            $userNames = $users->mapWithKeys(function ($user) {
                return [$user->id => $user->name];
            });

            foreach ($userNames as $userId => $userName) {
                if (strpos($text, '@' . $userName) !== false) {
                    $mentions[] = $userId;
                }
            }
        }

        return array_unique($mentions);
    }

    /**
     * Get formatted note content with mentions
     */
    public function getFormattedNoteContent($noteContent): string
    {
        $content = $noteContent;

        // تنسيق منشن الجميع
        $content = str_replace(
            '@everyone',
            '<span class="mention mention-everyone">@everyone</span>',
            $content
        );

        $content = str_replace(
            '@الجميع',
            '<span class="mention mention-everyone">@الجميع</span>',
            $content
        );

        // تنسيق منشن الأفراد (نحتاج لمعرفة المنشن في الملاحظة)
        $users = User::all();
        foreach ($users as $user) {
            $content = str_replace(
                '@' . $user->name,
                '<span class="mention">@' . $user->name . '</span>',
                $content
            );
        }

        return $content;
    }

}
