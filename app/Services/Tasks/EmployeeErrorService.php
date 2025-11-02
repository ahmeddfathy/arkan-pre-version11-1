<?php

namespace App\Services\Tasks;

use App\Models\EmployeeError;
use App\Models\TaskUser;
use App\Models\TemplateTaskUser;
use App\Models\ProjectServiceUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeErrorService
{
    public function createError($errorable, array $data): EmployeeError
    {
        if (!$this->isValidErrorable($errorable)) {
            throw new \Exception('نوع الكيان غير صالح');
        }

        $this->validateErrorData($data);

        return DB::transaction(function () use ($errorable, $data) {
            $error = new EmployeeError([
                'user_id' => $errorable->user_id,
                'title' => $data['title'],
                'description' => $data['description'],
                'error_category' => $data['error_category'] ?? 'other',
                'error_type' => $data['error_type'] ?? 'normal',
                'reported_by' => $data['reported_by'] ?? Auth::id(),
            ]);

            $errorable->errors()->save($error);

            // $this->notifyEmployee($error);

            return $error;
        });
    }

    public function updateError(EmployeeError $error, array $data): EmployeeError
    {
        $error->update([
            'title' => $data['title'] ?? $error->title,
            'description' => $data['description'] ?? $error->description,
            'error_category' => $data['error_category'] ?? $error->error_category,
            'error_type' => $data['error_type'] ?? $error->error_type,
        ]);

        return $error->fresh();
    }

    public function deleteError(EmployeeError $error): bool
    {
        return $error->delete();
    }

    public function getUserErrors(User $user, array $filters = [])
    {
        $query = EmployeeError::where('user_id', $user->id);

        if (isset($filters['error_type'])) {
            $query->where('error_type', $filters['error_type']);
        }

        if (isset($filters['error_category'])) {
            $query->where('error_category', $filters['error_category']);
        }

        if (isset($filters['errorable_type'])) {
            $query->where('errorable_type', $filters['errorable_type']);
        }

        return $query->with(['errorable', 'reportedBy'])
                    ->latest()
                    ->get();
    }

    public function getUserErrorStats(User $user): array
    {
        $errors = EmployeeError::where('user_id', $user->id);

        return [
            'total_errors' => $errors->count(),
            'critical_errors' => $errors->where('error_type', 'critical')->count(),
            'normal_errors' => $errors->where('error_type', 'normal')->count(),
            'by_category' => [
                'quality' => $errors->where('error_category', 'quality')->count(),
                'deadline' => $errors->where('error_category', 'deadline')->count(),
                'communication' => $errors->where('error_category', 'communication')->count(),
                'technical' => $errors->where('error_category', 'technical')->count(),
                'procedural' => $errors->where('error_category', 'procedural')->count(),
                'other' => $errors->where('error_category', 'other')->count(),
            ],
        ];
    }

    public function getTaskErrors($taskUser)
    {
        if (!$this->isValidErrorable($taskUser)) {
            return collect();
        }

        return $taskUser->errors()->with(['user', 'reportedBy'])->latest()->get();
    }

    private function isValidErrorable($errorable): bool
    {
        return $errorable instanceof TaskUser ||
               $errorable instanceof TemplateTaskUser ||
               $errorable instanceof ProjectServiceUser;
    }

    private function validateErrorData(array $data): void
    {
        if (empty($data['title'])) {
            throw new \Exception('عنوان الخطأ مطلوب');
        }

        if (empty($data['description'])) {
            throw new \Exception('وصف الخطأ مطلوب');
        }

        $validCategories = ['quality', 'deadline', 'communication', 'technical', 'procedural', 'other'];
        if (isset($data['error_category']) && !in_array($data['error_category'], $validCategories)) {
            throw new \Exception('تصنيف الخطأ غير صالح');
        }

        $validTypes = ['normal', 'critical'];
        if (isset($data['error_type']) && !in_array($data['error_type'], $validTypes)) {
            throw new \Exception('نوع الخطأ غير صالح');
        }
    }

    public function getProjectErrors($projectId, array $filters = [])
    {
        $query = EmployeeError::whereHasMorph('errorable', [ProjectServiceUser::class], function ($q) use ($projectId) {
            $q->where('project_id', $projectId);
        });

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['error_type'])) {
            $query->where('error_type', $filters['error_type']);
        }

        return $query->with(['user', 'errorable', 'reportedBy'])->latest()->get();
    }

    public function getTeamErrorStats($teamId = null): array
    {
        $query = EmployeeError::query();

        if ($teamId) {
            $query->whereHas('user', function ($q) use ($teamId) {
                $q->where('current_team_id', $teamId);
            });
        }

        $errors = $query->get();

        return [
            'total_errors' => $errors->count(),
            'critical_errors' => $errors->where('error_type', 'critical')->count(),
            'normal_errors' => $errors->where('error_type', 'normal')->count(),
            'top_users_with_errors' => $this->getTopUsersWithErrors($errors),
            'by_category' => $errors->groupBy('error_category')->map->count(),
        ];
    }

    private function getTopUsersWithErrors($errors, $limit = 10)
    {
        return $errors->groupBy('user_id')
                     ->map(function ($userErrors) {
                         return [
                             'user' => $userErrors->first()->user,
                             'total' => $userErrors->count(),
                             'critical' => $userErrors->where('error_type', 'critical')->count(),
                         ];
                     })
                     ->sortByDesc('total')
                     ->take($limit)
                     ->values();
    }
}

