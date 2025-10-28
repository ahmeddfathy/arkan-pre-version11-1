<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasSecureId;

class Like extends Model
{
    use HasFactory, HasSecureId;

    protected $fillable = [
        'user_id',
        'likeable_type',
        'likeable_id',
        'type'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // علاقة مع المستخدم
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // علاقة polymorphic مع المنشورات والتعليقات
    public function likeable()
    {
        return $this->morphTo();
    }

    // Scope للحصول على الإعجابات بنوع معين
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Scope للحصول على إعجابات المنشورات
    public function scopePosts($query)
    {
        return $query->where('likeable_type', Post::class);
    }

    // Scope للحصول على إعجابات التعليقات
    public function scopeComments($query)
    {
        return $query->where('likeable_type', Comment::class);
    }

    // الحصول على emoji الإعجاب
    public function getEmojiAttribute()
    {
        $emojis = [
            'like' => '👍',
            'love' => '❤️',
            'haha' => '😂',
            'wow' => '😮',
            'sad' => '😢',
            'angry' => '😡'
        ];

        return $emojis[$this->type] ?? '👍';
    }

    // الحصول على اسم الإعجاب
    public function getNameAttribute()
    {
        $names = [
            'like' => 'إعجاب',
            'love' => 'حب',
            'haha' => 'ضحك',
            'wow' => 'واو',
            'sad' => 'حزين',
            'angry' => 'غاضب'
        ];

        return $names[$this->type] ?? 'إعجاب';
    }
}
