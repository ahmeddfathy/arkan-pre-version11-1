<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RevisionAssignment extends Model
{
    protected $fillable = [
        'revision_id',
        'assignment_type',
        'from_user_id',
        'to_user_id',
        'assigned_by_user_id',
        'reason',
    ];

    /**
     * العلاقة مع التعديل
     */
    public function revision()
    {
        return $this->belongsTo(TaskRevision::class, 'revision_id');
    }

    /**
     * العلاقة مع المستخدم السابق
     */
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * العلاقة مع المستخدم الجديد
     */
    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    /**
     * العلاقة مع من قام بالتعيين
     */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    /**
     * Scopes for easier querying
     */
    public function scopeExecutor($query)
    {
        return $query->where('assignment_type', 'executor');
    }

    public function scopeReviewer($query)
    {
        return $query->where('assignment_type', 'reviewer');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('to_user_id', $userId);
    }

    public function scopeFromUser($query, $userId)
    {
        return $query->where('from_user_id', $userId);
    }
}
