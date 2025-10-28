<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Casts\SafeEncryptedCast;
use App\Traits\HasSecureId;

class TicketComment extends Model
{
    use HasFactory, HasSecureId, LogsActivity;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'comment',
        'comment_type',
        'mentions',
        'attachments',
        'is_internal',
        'is_system_message',
        'hours_worked'
    ];

    protected $casts = [
        'mentions' => 'array',
        'attachments' => 'array',
        'is_internal' => 'boolean',
        'is_system_message' => 'boolean',
        'hours_worked' => 'decimal:2',
        'comment' => SafeEncryptedCast::class,
    ];

    /**
     * Configure Activity Log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'ticket_id', 'user_id', 'comment_type', 'is_internal',
                'is_system_message', 'hours_worked'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إضافة تعليق جديد للتذكرة',
                'updated' => 'تم تحديث تعليق التذكرة',
                'deleted' => 'تم حذف تعليق التذكرة',
                default => $eventName
            });
    }

    /**
     * Get the ticket this comment belongs to
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ClientTicket::class, 'ticket_id');
    }

    /**
     * Get the user who made the comment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get mentioned users
     */
    public function mentionedUsers()
    {
        if (!$this->mentions) {
            return collect();
        }

        return User::whereIn('id', $this->mentions)->get();
    }

    /**
     * Get comment type in Arabic
     */
    public function getCommentTypeArabicAttribute(): string
    {
        return match($this->comment_type) {
            'work_update' => 'تحديث العمل',
            'status_change' => 'تغيير الحالة',
            'question' => 'سؤال',
            'solution' => 'حل',
            'general' => 'عام',
            default => $this->comment_type
        };
    }

    /**
     * Get comment type icon
     */
    public function getCommentTypeIconAttribute(): string
    {
        return match($this->comment_type) {
            'work_update' => 'fas fa-tasks',
            'status_change' => 'fas fa-exchange-alt',
            'question' => 'fas fa-question-circle',
            'solution' => 'fas fa-lightbulb',
            'general' => 'fas fa-comment',
            default => 'fas fa-comment'
        };
    }

    /**
     * Get comment type color
     */
    public function getCommentTypeColorAttribute(): string
    {
        return match($this->comment_type) {
            'work_update' => 'primary',
            'status_change' => 'warning',
            'question' => 'info',
            'solution' => 'success',
            'general' => 'secondary',
            default => 'secondary'
        };
    }

    /**
     * Scope for public comments only
     */
    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }

    /**
     * Scope for internal comments only
     */
    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    /**
     * Scope for system messages
     */
    public function scopeSystemMessages($query)
    {
        return $query->where('is_system_message', true);
    }

    /**
     * Scope for user messages
     */
    public function scopeUserMessages($query)
    {
        return $query->where('is_system_message', false);
    }

    /**
     * Get time ago format
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Check if comment has attachments
     */
    public function hasAttachments(): bool
    {
        return !empty($this->attachments);
    }

    /**
     * Get formatted comment with mentions
     */
    public function getFormattedCommentAttribute(): string
    {
        $comment = $this->comment;

        // تنسيق منشن الجميع
        $comment = str_replace(
            '@everyone',
            '<span class="mention mention-everyone">@everyone</span>',
            $comment
        );

        $comment = str_replace(
            '@الجميع',
            '<span class="mention mention-everyone">@الجميع</span>',
            $comment
        );

        // تنسيق منشن الأفراد
        if ($this->mentions) {
            foreach ($this->mentionedUsers() as $user) {
                $comment = str_replace(
                    '@' . $user->name,
                    '<span class="mention">@' . $user->name . '</span>',
                    $comment
                );
            }
        }

        return $comment;
    }

        /**
     * Extract mentions from comment text
     */
    public static function extractMentions($text, $ticketId)
    {
        $ticket = ClientTicket::with(['activeAssignments.user', 'creator'])->find($ticketId);
        $availableUsers = collect();

        // إضافة أعضاء الفريق المعينين
        if ($ticket->activeAssignments) {
            $availableUsers = $availableUsers->merge($ticket->activeAssignments->pluck('user'));
        }

        // إضافة منشئ التذكرة
        if ($ticket->creator) {
            $availableUsers->push($ticket->creator);
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
     * Create system message
     */
    public static function createSystemMessage($ticketId, $message, $userId = null)
    {
        return static::create([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'comment' => $message,
            'comment_type' => 'status_change',
            'is_system_message' => true,
            'is_internal' => false
        ]);
    }

    /**
     * Get all comment types
     */
    public static function getCommentTypes(): array
    {
        return [
            'work_update' => 'تحديث العمل',
            'status_change' => 'تغيير الحالة',
            'question' => 'سؤال',
            'solution' => 'حل',
            'general' => 'عام'
        ];
    }
}
