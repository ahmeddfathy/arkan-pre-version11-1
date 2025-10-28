<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use App\Traits\HasSecureId;

class TaskAttachment extends Model implements Auditable
{
    use HasFactory, AuditableTrait, HasSecureId;

    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
    ];

    protected $auditInclude = [
        'task_user_id',
        'file_path',
        'file_name',
        'description',
        'uploaded_by',
        'is_uploaded',
        'file_size',
    ];

    protected $fillable = [
        'task_user_id',
        'file_path',
        'file_name',
        'original_name',
        'description',
        'uploaded_by',
        'is_uploaded',
        'file_size',
        'mime_type',
    ];

    protected $casts = [
        'is_uploaded' => 'boolean',
        'file_size' => 'integer',
    ];

    /**
     * العلاقة مع TaskUser
     */
    public function taskUser()
    {
        return $this->belongsTo(TaskUser::class);
    }

    /**
     * العلاقة مع المستخدم الذي رفع الملف
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * العلاقة مع المهمة
     */
    public function task()
    {
        return $this->hasOneThrough(Task::class, TaskUser::class, 'id', 'id', 'task_user_id', 'task_id');
    }

    /**
     * العلاقة مع المستخدم المكلف بالمهمة
     */
    public function assignedUser()
    {
        return $this->hasOneThrough(User::class, TaskUser::class, 'id', 'id', 'task_user_id', 'user_id');
    }

    /**
     * Get formatted file size
     */
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

    /**
     * Get file extension
     */
    public function getFileExtensionAttribute()
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }

    /**
     * Check if file is an image
     */
    public function getIsImageAttribute()
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
        return in_array(strtolower($this->file_extension), $imageExtensions);
    }

    /**
     * Check if file is a document
     */
    public function getIsDocumentAttribute()
    {
        $docExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];
        return in_array(strtolower($this->file_extension), $docExtensions);
    }

    /**
     * Get file icon based on extension
     */
    public function getFileIconAttribute()
    {
        $extension = strtolower($this->file_extension);

        $icons = [
            'pdf' => 'fas fa-file-pdf text-danger',
            'doc' => 'fas fa-file-word text-primary',
            'docx' => 'fas fa-file-word text-primary',
            'xls' => 'fas fa-file-excel text-success',
            'xlsx' => 'fas fa-file-excel text-success',
            'ppt' => 'fas fa-file-powerpoint text-warning',
            'pptx' => 'fas fa-file-powerpoint text-warning',
            'txt' => 'fas fa-file-alt text-secondary',
            'zip' => 'fas fa-file-archive text-info',
            'rar' => 'fas fa-file-archive text-info',
            'jpg' => 'fas fa-file-image text-success',
            'jpeg' => 'fas fa-file-image text-success',
            'png' => 'fas fa-file-image text-success',
            'gif' => 'fas fa-file-image text-success',
            'mp4' => 'fas fa-file-video text-info',
            'avi' => 'fas fa-file-video text-info',
            'mp3' => 'fas fa-file-audio text-warning',
            'wav' => 'fas fa-file-audio text-warning',
        ];

        return $icons[$extension] ?? 'fas fa-file text-secondary';
    }

    /**
     * التحقق من أن المهمة غير مرتبطة بمشروع
     */
    public function getIsStandaloneTaskAttribute()
    {
        return $this->taskUser && !$this->taskUser->project_id;
    }

    /**
     * إنشاء مسار تخزين الملف
     */
    public static function generateFilePath($taskUser, $fileName)
    {
        $user = $taskUser->user;
        $task = $taskUser->task;

        // التأكد من أن المهمة غير مرتبطة بمشروع
        if ($taskUser->project_id) {
            throw new \Exception('هذه المهمة مرتبطة بمشروع، استخدم نظام مرفقات المشاريع');
        }

        $employeeName = str_replace(' ', '_', $user->name);
        $taskTitle = str_replace(' ', '_', $task->title);
        $date = now()->format('Y-m-d');

        return "employees/{$employeeName}/{$taskTitle}_{$date}_{$fileName}";
    }

    /**
     * الحصول على المرفقات لمهمة معينة
     */
    public static function getAttachmentsForTask($taskUserId)
    {
        return self::where('task_user_id', $taskUserId)
                   ->where('is_uploaded', true)
                   ->with(['uploader', 'taskUser.task', 'taskUser.user'])
                   ->orderBy('created_at', 'desc')
                   ->get();
    }

    /**
     * حذف الملف من التخزين
     */
    public function deleteFile()
    {
        if ($this->file_path && \Illuminate\Support\Facades\Storage::disk('s3')->exists($this->file_path)) {
            \Illuminate\Support\Facades\Storage::disk('s3')->delete($this->file_path);
        }
    }

    /**
     * حدث حذف النموذج
     */
    protected static function booted()
    {
        static::deleting(function ($attachment) {
            $attachment->deleteFile();
        });
    }
}
