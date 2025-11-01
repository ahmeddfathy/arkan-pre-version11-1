<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use App\Traits\HasSecureId;

class ProjectAttachment extends Model implements Auditable
{
    use HasFactory, AuditableTrait, HasSecureId;

    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
    ];

    protected $auditInclude = [
        'project_id',
        'service_type',
        'file_path',
        'file_name',
        'description',
        'uploaded_by',
        'is_uploaded',
        'task_type',
        'template_task_user_id',
        'task_user_id',
        'parent_attachment_id',
    ];

    protected $fillable = [
        'project_id',
        'service_type',
        'file_path',
        'file_name',
        'description',
        'uploaded_by',
        'is_uploaded',
        'task_type',
        'template_task_user_id',
        'task_user_id',
        'parent_attachment_id',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function templateTaskUser()
    {
        return $this->belongsTo(TemplateTaskUser::class);
    }

    public function taskUser()
    {
        return $this->belongsTo(TaskUser::class);
    }


    public function parentAttachment()
    {
        return $this->belongsTo(ProjectAttachment::class, 'parent_attachment_id');
    }


    public function replies()
    {
        return $this->hasMany(ProjectAttachment::class, 'parent_attachment_id')->orderBy('created_at', 'desc');
    }


    public function threaded_replies()
    {
        return $this->replies()->with('replies');
    }


    public function getTaskAttribute()
    {
        if ($this->task_type === 'template_task' && $this->template_task_user_id) {
            return $this->templateTaskUser;
        } elseif ($this->task_type === 'regular_task' && $this->task_user_id) {
            return $this->taskUser;
        }

        return null;
    }


    public function getFileSizeAttribute()
    {
        $filePath = storage_path('app/' . $this->file_path);
        return file_exists($filePath) ? filesize($filePath) : 0;
    }


    public function getFileSizeKbAttribute()
    {
        return round($this->file_size / 1024, 1);
    }


    public function getFileSizeMbAttribute()
    {
        return round($this->file_size / 1024 / 1024, 1);
    }


    public function getFormattedFileSizeAttribute()
    {
        $size = $this->file_size;

        if ($size == 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 1) . ' ' . $units[$unitIndex];
    }


    public function fileExists()
    {
        $filePath = storage_path('app/' . $this->file_path);
        return file_exists($filePath);
    }


    public function getOriginalNameAttribute()
    {
        return $this->file_name ?: basename($this->file_path);
    }


    public function isReply()
    {
        return !is_null($this->parent_attachment_id);
    }


    public function hasReplies()
    {
        return $this->replies()->count() > 0;
    }


    public static function getMainAttachmentsForTask($taskType, $taskId, $serviceType = null)
    {
        $query = self::whereNull('parent_attachment_id');

        if ($taskType === 'template_task') {
            $query->where('template_task_user_id', $taskId);
        } elseif ($taskType === 'regular_task') {
            $query->where('task_user_id', $taskId);
        }

        if ($serviceType) {
            $query->where('service_type', $serviceType);
        }

        return $query->with('replies.user', 'user')->orderBy('created_at', 'desc')->get();
    }
}
