<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;
use App\Casts\SafeEncryptedCast;
use App\Traits\HasSecureId;

use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\KpiEvaluation;

/**
 * @method bool hasRole($roles)
 * @method bool hasPermissionTo($permission)
 */
class User extends Authenticatable
{
  use HasApiTokens;
  use HasFactory;
  use HasProfilePhoto;
  use HasTeams;
  use HasRoles;
  use Notifiable;
  use TwoFactorAuthenticatable;
  use HasSecureId;
  use LogsActivity;


  protected $fillable = [
    'name',
    "employee_id",
    'email',
    'password',
    'date_of_birth',
    'national_id_number',
    'slack_user_id',
    'phone_number',
    'start_date_of_employment',
    'last_contract_start_date',
    'last_contract_end_date',
    'job_progression',
    'department',
    'gender',
    'address',
    'education_level',
    'marital_status',
    'number_of_children',
    'employee_status',
    'work_shift_id',
    'fcm_token',
  ];

  protected $hidden = [
    'password',
    'remember_token',
    'two_factor_recovery_codes',
    'two_factor_secret',
  ];

  protected $appends = [
    'profile_photo_url',
  ];

  protected $casts = [
    'email_verified_at' => 'datetime',
    'password' => 'hashed',
    'date_of_birth' => 'date',
    // Encrypted fields for security
    'national_id_number' => SafeEncryptedCast::class,
    'email' => SafeEncryptedCast::class,
    'phone_number' => SafeEncryptedCast::class,
    'address' => SafeEncryptedCast::class,
    'fcm_token' => SafeEncryptedCast::class,
  ];

  public function getActivitylogOptions(): LogOptions
  {
    return LogOptions::defaults()
      ->logOnly(['name', 'email', 'employee_id', 'department', 'employee_status'])
      ->logOnlyDirty()
      ->dontSubmitEmptyLogs();
  }

  /**
   * النشاطات التي قام بها المستخدم
   */
  public function activities()
  {
    return $this->hasMany(\Spatie\Activitylog\Models\Activity::class, 'causer_id');
  }

  /**
   * النشاطات التي تمت على المستخدم
   */
  public function subjectActivities()
  {
    return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
  }

  // علاقات التواصل الاجتماعي الجديدة
  public function posts()
  {
    return $this->hasMany(Post::class);
  }

  public function comments()
  {
    return $this->hasMany(Comment::class);
  }

  public function likes()
  {
    return $this->hasMany(Like::class);
  }

  // علاقات المتابعة
  public function followers()
  {
    return $this->belongsToMany(User::class, 'followers', 'following_id', 'follower_id')
      ->withPivot('followed_at')
      ->withTimestamps();
  }

  public function following()
  {
    return $this->belongsToMany(User::class, 'followers', 'follower_id', 'following_id')
      ->withPivot('followed_at')
      ->withTimestamps();
  }

  // وظائف مساعدة للمتابعة
  public function isFollowing(User $user)
  {
    return $this->following()->where('following_id', $user->id)->exists();
  }

  public function isFollowedBy(User $user)
  {
    return $this->followers()->where('follower_id', $user->id)->exists();
  }

  public function follow(User $user)
  {
    if (!$this->isFollowing($user) && $this->id !== $user->id) {
      $this->following()->attach($user->id, ['followed_at' => now()]);
      return true;
    }
    return false;
  }

  public function unfollow(User $user)
  {
    if ($this->isFollowing($user)) {
      $this->following()->detach($user->id);
      return true;
    }
    return false;
  }

  public function getFollowersCountAttribute()
  {
    return $this->followers()->count();
  }

  public function getFollowingCountAttribute()
  {
    return $this->following()->count();
  }

  public function getPostsCountAttribute()
  {
    return $this->posts()->count();
  }

  public function attendanceRecords()
  {
    return $this->hasMany(AttendanceRecord::class, 'employee_id', 'employee_id');
  }

  public function sentMessages()
  {
    return $this->hasMany(Message::class, 'sender_id');
  }

  public function receivedMessages()
  {
    return $this->hasMany(Message::class, 'receiver_id');
  }

  public function ownedTeams()
  {
    return $this->hasMany(Team::class, 'user_id');
  }

  public function teams()
  {
    return $this->belongsToMany(Team::class, 'team_user')
      ->withPivot('role')
      ->withTimestamps();
  }

  // Override hasPermissionTo to check forbidden permissions
  public function hasPermissionTo($permission, $guardName = null): bool
  {
    // Get permission name
    $permissionName = is_string($permission) ? $permission : $permission->name;

    // أولاً نتحقق من إنها مش محظورة
    $isForbidden = DB::table('model_has_permissions')
      ->where([
        'model_type' => get_class($this),
        'model_id' => $this->id,
        'forbidden' => true
      ])
      ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
      ->where('permissions.name', $permissionName)
      ->exists();

    if ($isForbidden) {
      return false;
    }

    // استخدام الـ Spatie logic للتحقق من الصلاحية العادية
    $guardName = $guardName ?? $this->getDefaultGuardName();

    // Check if user has permission directly
    if ($this->permissions->where('name', $permissionName)->count()) {
      return true;
    }

    // Check if user has permission through roles
    foreach ($this->roles as $role) {
      if ($role->permissions->where('name', $permissionName)->count()) {
        return true;
      }
    }

    return false;
  }

  public function hasAnyRole($roles): bool
  {
    return $this->hasRole($roles);
  }

  public function overtimeRequests()
  {
    return $this->hasMany(OverTimeRequests::class);
  }

  public function getMaxAllowedAbsenceDays(): int
  {
    if ($this->date_of_birth) {
      $age = abs(now()->diffInYears($this->date_of_birth));

      if ($age >= 50) {
        return 45;
      }
    }
    return 21;
  }

  public function workShift()
  {
    return $this->belongsTo(WorkShift::class);
  }

  public function attendances()
  {
    return $this->hasMany(Attendance::class, 'employee_id', 'employee_id');
  }

  /**
   * Determine if the given team is the current team.
   *
   * @param  mixed  $team
   * @return bool
   */
  public function isCurrentTeam($team)
  {
    if (is_null($team) || !$team) {
      return false;
    }

    if (is_null($this->currentTeam)) {
      return false;
    }

    return $team->id === $this->currentTeam->id;
  }

  public function foodAllowances()
  {
    return $this->hasMany(FoodAllowance::class, 'user_id');
  }


  public function tasks()
  {
    return $this->belongsToMany(Task::class, 'task_users')
      ->withPivot(
        'role',
        'status',
        'estimated_hours',
        'estimated_minutes',
        'actual_hours',
        'actual_minutes',
        'start_date',
        'due_date',
        'completed_date'
      )
      ->withTimestamps();
  }




  /**
   * المشاريع التي يشارك فيها المستخدم
   */
  public function projects()
  {
    return $this->belongsToMany(Project::class, 'project_service_user', 'user_id', 'project_id')
      ->select('projects.id', 'projects.name', 'projects.code')
      ->distinct();
  }

  /**
   * مشاركات المستخدم في خدمات المشاريع
   */
  public function projectServiceUsers()
  {
    return $this->hasMany(ProjectServiceUser::class, 'user_id');
  }


  public function projectNotes()
  {
    return $this->hasMany(ProjectNote::class);
  }


  public function evaluations()
  {
    return $this->hasMany(EmployeeEvaluation::class);
  }

  public function kpiEvaluations()
  {
    return $this->hasMany(KpiEvaluation::class);
  }

  public function templateTasks()
  {
    return $this->belongsToMany(TemplateTask::class, 'template_task_user', 'user_id', 'template_task_id')
      ->withPivot(['status', 'started_at', 'paused_at', 'completed_at', 'actual_minutes'])
      ->withTimestamps();
  }


  public function createdMeetings()
  {
    return $this->hasMany(Meeting::class, 'created_by');
  }


  public function participatingMeetings()
  {
    return $this->belongsToMany(Meeting::class, 'meeting_participants')
      ->withPivot('attended')
      ->withTimestamps();
  }


  public function badges()
  {
    return $this->belongsToMany(Badge::class, 'user_badges')
      ->withPivot(['season_id', 'points_earned', 'earned_at', 'is_active', 'notes'])
      ->withTimestamps();
  }


  public function seasonPoints()
  {
    return $this->hasMany(UserSeasonPoint::class);
  }

  public function userSeasonPoints()
  {
    return $this->hasMany(UserSeasonPoint::class);
  }


  public function getSeasonPoints($seasonId)
  {
    return UserSeasonPoint::where('user_id', $this->id)
      ->where('season_id', $seasonId)
      ->first();
  }


  public function getCurrentBadgeForSeason($seasonId)
  {
    $seasonPoints = $this->getSeasonPoints($seasonId);
    return $seasonPoints ? $seasonPoints->currentBadge : null;
  }


  public function getHighestBadgeForSeason($seasonId)
  {
    $seasonPoints = $this->getSeasonPoints($seasonId);
    return $seasonPoints ? $seasonPoints->highestBadge : null;
  }


  public function addPointsForSeason($seasonId, $points, $tasksCompleted = 0, $projectsCompleted = 0, $minutesWorked = 0)
  {
    $seasonPoints = UserSeasonPoint::getOrCreate($this->id, $seasonId);
    return $seasonPoints->addPoints($points, $tasksCompleted, $projectsCompleted, $minutesWorked);
  }


  public function additionalTasks()
  {
    return $this->belongsToMany(AdditionalTask::class, 'additional_task_users')
      ->withPivot(['status', 'started_at', 'completed_at', 'points_earned', 'user_notes', 'admin_notes', 'completion_data'])
      ->withTimestamps();
  }


  public function additionalTaskUsers()
  {
    return $this->hasMany(AdditionalTaskUser::class);
  }


  public function getAvailableAdditionalTasks()
  {
    $userId = $this->id;

    return AdditionalTask::active()
      ->forUser($this)
      ->where(function ($query) use ($userId) {
        // المهام التلقائية التي لم يتم تخصيصها بعد
        $query->where('assignment_type', 'auto_assign')
          ->whereDoesntHave('users', function ($subQuery) use ($userId) {
            $subQuery->where('user_id', $userId);
          });
      })
      ->orWhere(function ($query) use ($userId) {
        // المهام التي تتطلب تقديم ولم يتقدم عليها المستخدم
        $query->where('assignment_type', 'application_required')
          ->forUser($this)
          ->whereDoesntHave('users', function ($subQuery) use ($userId) {
            $subQuery->where('user_id', $userId);
          });
      })
      ->get();
  }


  public function getActiveAdditionalTasks()
  {
    return $this->additionalTaskUsers()
      ->whereIn('status', ['assigned', 'in_progress'])
      ->with('additionalTask')
      ->get();
  }


  public function getCompletedAdditionalTasks()
  {
    return $this->additionalTaskUsers()
      ->where('status', 'completed')
      ->with('additionalTask')
      ->get();
  }


  public function getPendingApplicationAdditionalTasks()
  {
    return $this->additionalTaskUsers()
      ->where('status', 'applied')
      ->with('additionalTask')
      ->get();
  }


  public function getRejectedAdditionalTasks()
  {
    return $this->additionalTaskUsers()
      ->where('status', 'rejected')
      ->with('additionalTask')
      ->get();
  }


  public function canApplyForAdditionalTask(AdditionalTask $task)
  {
    if (!$task->forUser($this)->exists()) {
      return false;
    }

    // التحقق من أن المهمة تتطلب تقديم
    if (!$task->requiresApplication()) {
      return false;
    }

    // التحقق من عدم التقديم مسبقاً
    if ($this->additionalTaskUsers()->where('additional_task_id', $task->id)->exists()) {
      return false;
    }

    // التحقق من عدم انتهاء المهمة وإمكانية قبول مشاركين جدد
    return $task->status === 'active' &&
      !$task->isExpired() &&
      $task->canAcceptMoreParticipants();
  }



  public static function getAvailableDepartments()
  {
    return self::whereNotNull('department')
      ->distinct()
      ->pluck('department')
      ->filter()
      ->sort()
      ->values();
  }

  // ========== علاقات نقل المهام ==========

  /**
   * المهام العادية التي كانت مخصصة لهذا المستخدم أصلاً ونُقلت لآخرين
   */
  public function originalTaskAssignments()
  {
    return $this->hasMany(TaskUser::class, 'original_user_id');
  }

  /**
   * مهام القوالب التي كانت مخصصة لهذا المستخدم أصلاً ونُقلت لآخرين
   */
  public function originalTemplateTaskAssignments()
  {
    return $this->hasMany(TemplateTaskUser::class, 'original_user_id');
  }

  /**
   * الحصول على إحصائيات النقل للمستخدم
   */
  public function getTransferStatistics(Season $season = null)
  {
    $transferService = app(\App\Services\Tasks\TaskTransferService::class);
    return $transferService->getUserTransferStatistics($this, $season);
  }

  /**
   * الحصول على المهام المنقولة من هذا المستخدم والتي لم تكتمل بعد
   */
  public function getUncompletedTransferredTasks(Season $season = null)
  {
    $transferService = app(\App\Services\Tasks\TaskTransferService::class);
    return $transferService->getUserUncompletedTransferredTasks($this, $season);
  }

  /**
   * الحصول على عدد المهام المنقولة إليه
   */
  public function getTransferredToMeCount(Season $season = null): int
  {
    $season = $season ?: Season::where('is_active', true)->first();

    if (!$season) {
      return 0;
    }

    $regularTasks = TaskUser::where('user_id', $this->id)
      ->where('is_transferred', true)
      ->where('season_id', $season->id)
      ->count();

    $templateTasks = TemplateTaskUser::where('user_id', $this->id)
      ->where('is_transferred', true)
      ->where('season_id', $season->id)
      ->count();

    return $regularTasks + $templateTasks;
  }

  /**
   * الحصول على عدد المهام المنقولة منه
   */
  public function getTransferredFromMeCount(Season $season = null): int
  {
    $season = $season ?: Season::where('is_active', true)->first();

    if (!$season) {
      return 0;
    }

    $regularTasks = TaskUser::where('original_user_id', $this->id)
      ->where('is_transferred', true)
      ->where('season_id', $season->id)
      ->count();

    $templateTasks = TemplateTaskUser::where('original_user_id', $this->id)
      ->where('is_transferred', true)
      ->where('season_id', $season->id)
      ->count();

    return $regularTasks + $templateTasks;
  }

  /**
   * البحث عن مستخدم بالإيميل المشفر
   */
  public static function findByEmail($email)
  {
    $users = self::all();

    foreach ($users as $user) {
      if ($user->email === $email) {
        return $user;
      }
    }

    return null;
  }

  /**
   * البحث عن مستخدم بالرقم القومي المشفر
   */
  public static function findByNationalId($nationalId)
  {
    $users = self::all();

    foreach ($users as $user) {
      if ($user->national_id_number === $nationalId) {
        return $user;
      }
    }

    return null;
  }

  /**
   * البحث عن مستخدم برقم الهاتف المشفر
   */
  public static function findByPhone($phone)
  {
    $users = self::all();

    foreach ($users as $user) {
      if ($user->phone_number === $phone) {
        return $user;
      }
    }

    return null;
  }

  /**
   * الحصول على إحصائيات الإنجاز المعدلة (تأخذ في الحسبان المهام المنقولة)
   */
  public function getAdjustedCompletionStats(Season $season = null): array
  {
    $season = $season ?: Season::where('is_active', true)->first();

    if (!$season) {
      return [
        'completed_tasks' => 0,
        'transferred_incomplete_tasks' => 0,
        'net_completion_rate' => 0,
        'original_assignments' => 0
      ];
    }

    // المهام المكتملة فعلاً
    $completedTasks = TaskUser::where('user_id', $this->id)
      ->where('status', 'completed')
      ->where('season_id', $season->id)
      ->count();

    $completedTemplateTasks = TemplateTaskUser::where('user_id', $this->id)
      ->where('status', 'completed')
      ->where('season_id', $season->id)
      ->count();

    // المهام المنقولة وغير مكتملة (تحسب كخصم)
    $transferredIncompleteTasks = TaskUser::where('original_user_id', $this->id)
      ->where('is_transferred', true)
      ->whereNotIn('status', ['completed', 'cancelled'])
      ->where('season_id', $season->id)
      ->count();

    $transferredIncompleteTemplateTasks = TemplateTaskUser::where('original_user_id', $this->id)
      ->where('is_transferred', true)
      ->whereNotIn('status', ['completed', 'cancelled'])
      ->where('season_id', $season->id)
      ->count();

    // إجمالي المهام الأصلية
    $originalAssignments = TaskUser::where(function ($query) {
      $query->where('user_id', $this->id)
        ->where('is_transferred', false);
    })
      ->orWhere('original_user_id', $this->id)
      ->where('season_id', $season->id)
      ->count();

    $originalTemplateAssignments = TemplateTaskUser::where(function ($query) {
      $query->where('user_id', $this->id)
        ->where('is_transferred', false);
    })
      ->orWhere('original_user_id', $this->id)
      ->where('season_id', $season->id)
      ->count();

    $totalCompleted = $completedTasks + $completedTemplateTasks;
    $totalTransferredIncomplete = $transferredIncompleteTasks + $transferredIncompleteTemplateTasks;
    $totalOriginal = $originalAssignments + $originalTemplateAssignments;

    $netCompletionRate = $totalOriginal > 0
      ? (($totalCompleted - $totalTransferredIncomplete) / $totalOriginal) * 100
      : 0;

    return [
      'completed_tasks' => $totalCompleted,
      'transferred_incomplete_tasks' => $totalTransferredIncomplete,
      'net_completion_rate' => round($netCompletionRate, 2),
      'original_assignments' => $totalOriginal
    ];
  }
}
