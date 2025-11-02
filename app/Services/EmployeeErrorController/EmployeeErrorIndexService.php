<?php

namespace App\Services\EmployeeErrorController;

use App\Models\EmployeeError;
use App\Models\User;
use App\Models\RoleHierarchy;
use App\Models\DepartmentRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class EmployeeErrorIndexService
{
    protected $errorFilterService;
    protected $errorStatisticsService;

    public function __construct(
        EmployeeErrorFilterService $errorFilterService,
        EmployeeErrorStatisticsService $errorStatisticsService
    ) {
        $this->errorFilterService = $errorFilterService;
        $this->errorStatisticsService = $errorStatisticsService;
    }

    public function getErrorsForIndex(Request $request): array
    {
        $user = Auth::user();

        $filters = [
            'error_type' => $request->get('error_type'),
            'error_category' => $request->get('error_category'),
            'errorable_type' => $request->get('errorable_type'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'month' => $request->get('month'),
            'project_code' => $request->get('project_code'),
        ];

        $employeeErrors = $this->getErrorsBasedOnRole($user, array_filter($filters));

        $stats = $this->getStatsBasedOnRole($user);

        return [
            'employeeErrors' => $employeeErrors,
            'stats' => $stats,
            'filters' => $filters
        ];
    }


    private function getErrorsBasedOnRole(User $user, array $filters): Collection
    {
        $query = EmployeeError::query();

        $globalLevel = RoleHierarchy::getUserMaxHierarchyLevel($user);
        $departmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($user);

        if (($globalLevel && $globalLevel >= 2) || ($departmentLevel && $departmentLevel >= 2)) {
            $query->where('reported_by', $user->id);
        } else {
            $query->where('user_id', $user->id);
        }

        $query = $this->errorFilterService->applyFilters($query, $filters);

        return $query->with(['errorable', 'reportedBy', 'user'])
                     ->latest()
                     ->get();
    }


    private function getStatsBasedOnRole(User $user): array
    {
        $globalLevel = RoleHierarchy::getUserMaxHierarchyLevel($user);
        $departmentLevel = DepartmentRole::getUserDepartmentHierarchyLevel($user);

        if (($globalLevel && $globalLevel >= 2) || ($departmentLevel && $departmentLevel >= 2)) {
            return $this->errorStatisticsService->getReporterStats($user);
        } else {
            return $this->errorStatisticsService->getUserErrorStats($user);
        }
    }


    public function getUserErrors(User $user, array $filters = []): Collection
    {
        $query = EmployeeError::where('user_id', $user->id);


        $query = $this->errorFilterService->applyFilters($query, $filters);

        return $query->with(['errorable', 'reportedBy'])
                     ->latest()
                     ->get();
    }


    public function getProjectErrors($projectId, array $filters = []): Collection
    {
        $query = EmployeeError::whereHasMorph('errorable', [\App\Models\ProjectServiceUser::class], function ($q) use ($projectId) {
            $q->where('project_id', $projectId);
        });

        $query = $this->errorFilterService->applyFilters($query, $filters);

        return $query->with(['user', 'errorable', 'reportedBy'])
                     ->latest()
                     ->get();
    }


    public function getTaskErrors($taskId, array $filters = []): Collection
    {
        $query = EmployeeError::whereHasMorph('errorable', [\App\Models\TaskUser::class], function ($q) use ($taskId) {
            $q->where('task_id', $taskId);
        });


        $query = $this->errorFilterService->applyFilters($query, $filters);

        return $query->with(['user', 'errorable', 'reportedBy'])
                     ->latest()
                     ->get();
    }


    public function getDepartmentErrors(string $department, array $filters = []): Collection
    {
        $query = EmployeeError::whereHas('user', function ($q) use ($department) {
            $q->where('department', $department);
        });


        $query = $this->errorFilterService->applyFilters($query, $filters);

        return $query->with(['user', 'errorable', 'reportedBy'])
                     ->latest()
                     ->get();
    }


    public function searchErrors(string $searchTerm, array $filters = []): Collection
    {
        $query = EmployeeError::where(function ($q) use ($searchTerm) {
            $q->where('title', 'like', "%{$searchTerm}%")
              ->orWhere('description', 'like', "%{$searchTerm}%")
              ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                  $userQuery->where('name', 'like', "%{$searchTerm}%");
              });
        });


        $query = $this->errorFilterService->applyFilters($query, $filters);

        return $query->with(['user', 'errorable', 'reportedBy'])
                     ->latest()
                     ->get();
    }


    public function getMyErrors(User $user, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = EmployeeError::where('user_id', $user->id);


        $query = $this->errorFilterService->applyFilters($query, $filters);

        return $query->with(['user', 'errorable', 'reportedBy'])
                     ->latest()
                     ->get();
    }


    public function getReportedErrors(User $user, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = EmployeeError::where('reported_by', $user->id);


        $query = $this->errorFilterService->applyFilters($query, $filters);

        return $query->with(['user', 'errorable', 'reportedBy'])
                     ->latest()
                     ->get();
    }


    public function getAllErrors(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = EmployeeError::query();


        $query = $this->errorFilterService->applyFilters($query, $filters);

        return $query->with(['user', 'errorable', 'reportedBy'])
                     ->latest()
                     ->get();
    }


    public function getAllCriticalErrors(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = EmployeeError::where('error_type', 'critical');


        $query = $this->errorFilterService->applyFilters($query, $filters);

        return $query->with(['user', 'errorable', 'reportedBy'])
                     ->latest()
                     ->get();
    }
}

