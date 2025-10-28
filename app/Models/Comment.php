<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasSecureId;
use Illuminate\Support\Facades\Auth;

class Comment extends Model
{
    use HasFactory, HasSecureId, LogsActivity;

    protected $fillable = [
        'user_id',
        'post_id',
        'parent_id',
        'content',
        'image',
        'is_active',
        'likes_count'
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
                'user_id', 'post_id', 'parent_id', 'content', 'image',
                'is_active', 'likes_count'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
                'created' => 'تم إضافة تعليق جديد',
                'updated' => 'تم تحديث التعليق',
                'deleted' => 'تم حذف التعليق',
                default => $eventName
            });
    }

    // علاقة مع المستخدم
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // علاقة مع المنشور
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    // علاقة مع التعليق الأب (للردود)
    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    // علاقة مع الردود
    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id')->latest();
    }

    // علاقة مع الإعجابات
    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    // التحقق من إعجاب المستخدم الحالي
    public function isLikedBy($user = null)
    {
        if (!$user) {
            $user = Auth::user();
        }

        if (!$user) {
            return false;
        }

        return $this->likes()->where('user_id', $user->id)->exists();
    }

    // الحصول على نوع الإعجاب للمستخدم الحالي
    public function getLikeTypeBy($user = null)
    {
        if (!$user) {
            $user = Auth::user();
        }

        if (!$user) {
            return null;
        }

        $like = $this->likes()->where('user_id', $user->id)->first();
        return $like ? $like->type : null;
    }

    // إضافة أو إزالة الإعجاب
    public function toggleLike($user = null, $type = 'like')
    {
        if (!$user) {
            $user = Auth::user();
        }

        $like = $this->likes()->where('user_id', $user->id)->first();

        if ($like) {
            if ($like->type == $type) {
                // إزالة الإعجاب
                $like->delete();
                $this->decrement('likes_count');
                return 'removed';
            } else {
                // تغيير نوع الإعجاب
                $like->update(['type' => $type]);
                return 'updated';
            }
        } else {
            // إضافة إعجاب جديد
            $this->likes()->create([
                'user_id' => $user->id,
                'type' => $type
            ]);
            $this->increment('likes_count');
            return 'added';
        }
    }

    // Scope للتعليقات النشطة
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope للتعليقات الرئيسية (ليست ردود)
    public function scopeMainComments($query)
    {
        return $query->whereNull('parent_id');
    }

    // Scope للردود
    public function scopeReplies($query)
    {
        return $query->whereNotNull('parent_id');
    }

    // Format المحتوى للعرض
    public function getFormattedContentAttribute()
    {
        return nl2br(e($this->content));
    }

    // الحصول على رابط الصورة
    public function getImageUrl()
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    // الحصول على وقت النشر منسق
    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    // التحقق من كونه رد
    public function isReply()
    {
        return !is_null($this->parent_id);
    }

    // Boot method لتحديث عداد التعليقات في المنشور
    protected static function boot()
    {
        parent::boot();

        static::created(function ($comment) {
            // زيادة عداد التعليقات في المنشور فقط للتعليقات الرئيسية
            if (!$comment->parent_id) {
                $comment->post->incrementCommentsCount();
            }
        });

        static::deleted(function ($comment) {
            // تقليل عداد التعليقات في المنشور فقط للتعليقات الرئيسية
            if (!$comment->parent_id) {
                $comment->post->decrementCommentsCount();
            }
        });
    }
}
