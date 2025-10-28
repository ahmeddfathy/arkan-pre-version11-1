<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasSecureId;
use Illuminate\Support\Facades\Auth;

class Post extends Model
{
    use HasFactory, SoftDeletes, HasSecureId;

    protected $fillable = [
        'user_id',
        'content',
        'image',
        'video',
        'privacy',
        'is_active',
        'likes_count',
        'comments_count',
        'shares_count'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // علاقة مع المستخدم الذي نشر المنشور
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // علاقة مع التعليقات
    public function comments()
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id')->latest();
    }

    // كل التعليقات (تشمل الردود)
    public function allComments()
    {
        return $this->hasMany(Comment::class)->latest();
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

    // زيادة عدد التعليقات
    public function incrementCommentsCount()
    {
        $this->increment('comments_count');
    }

    // تقليل عدد التعليقات
    public function decrementCommentsCount()
    {
        $this->decrement('comments_count');
    }

    // Scope للمنشورات النشطة
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope للمنشورات العامة
    public function scopePublic($query)
    {
        return $query->where('privacy', 'public');
    }

    // Scope للمنشورات مع المستخدمين
    public function scopeWithUserAndCounts($query)
    {
        return $query->with(['user:id,name,profile_photo_path'])
                    ->withCount(['likes', 'comments']);
    }

    // Format المحتوى للعرض
    public function getFormattedContentAttribute()
    {
        return nl2br(e($this->content));
    }

    // التحقق من وجود وسائط
    public function hasMedia()
    {
        return !empty($this->image) || !empty($this->video);
    }

    // الحصول على رابط الصورة
    public function getImageUrl()
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    // الحصول على رابط الفيديو
    public function getVideoUrl()
    {
        return $this->video ? asset('storage/' . $this->video) : null;
    }

    // الحصول على وقت النشر منسق
    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }
}
