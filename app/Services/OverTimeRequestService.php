<?php

namespace App\Services;

use App\Models\OverTimeRequests;
use App\Services\NotificationOvertimeService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Log;


class OverTimeRequestService
{
    protected $notificationService;

    public function __construct(
        NotificationOvertimeService $notificationService
    ) {
        $this->notificationService = $notificationService;
    }

    public function getAllRequests($filters = []): LengthAwarePaginator
    {
        $user = Auth::user();
        $query = OverTimeRequests::with('user');

        if (!empty($filters['employee_name'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['employee_name'] . '%');
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if ($user->hasRole('hr')) {
            return $query->whereHas('user', function ($q) {
                $q->whereDoesntHave('teams');
            })->latest()->paginate(10);
        } elseif ($user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager'])) {
            $team = $user->currentTeam;
            if ($team) {
                $teamMembers = $team->users->pluck('id')->toArray();
                return $query->whereIn('user_id', $teamMembers)->latest()->paginate(10);
            }
        }

        return $query->where('user_id', $user->id)
            ->latest()
            ->paginate(10);
    }

    public function getUserRequests(int $userId, ?string $startDate = null, ?string $endDate = null): LengthAwarePaginator
    {
        $query = OverTimeRequests::where('user_id', $userId);

        if ($startDate && $endDate) {
            $query->whereBetween('overtime_date', [$startDate, $endDate]);
        }

        return $query->latest()->paginate(10);
    }

    public function getTeamRequests(int $teamId, ?string $employeeName = null, ?string $status = null, ?string $startDate = null, ?string $endDate = null): LengthAwarePaginator
    {
        $query = OverTimeRequests::query()
            ->with('user')
            ->whereHas('user', function ($q) use ($teamId) {
                $q->whereHas('teams', function ($q) use ($teamId) {
                    $q->where('teams.id', $teamId);
                });
            });

        $this->applyFilters($query, $employeeName, $status, $startDate, $endDate);

        return $query->latest()->paginate(10);
    }

    public function getAllTeamRequests(?string $employeeName = null, ?string $status = null, ?string $startDate = null, ?string $endDate = null): LengthAwarePaginator
    {
        $query = OverTimeRequests::query()
            ->with('user')
            ->whereHas('user', function ($q) {
                $q->whereHas('teams');
            });

        $this->applyFilters($query, $employeeName, $status, $startDate, $endDate);

        return $query->latest()->paginate(10);
    }

    public function getNoTeamRequests(?string $employeeName = null, ?string $status = null, ?string $startDate = null, ?string $endDate = null): LengthAwarePaginator
    {
        $query = OverTimeRequests::query()
            ->with('user')
            ->whereHas('user', function ($q) {
                $q->whereDoesntHave('teams');
            });

        $this->applyFilters($query, $employeeName, $status, $startDate, $endDate);

        return $query->latest()->paginate(10);
    }

    private function applyFilters($query, ?string $employeeName, ?string $status, ?string $startDate, ?string $endDate): void
    {
        if ($employeeName) {
            $query->whereHas('user', function ($q) use ($employeeName) {
                $q->where('name', 'like', "%{$employeeName}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('overtime_date', [$startDate, $endDate]);
        }
    }

    public function getPendingRequestsCount(int $teamId): int
    {
        return OverTimeRequests::whereHas('user', function ($q) use ($teamId) {
            $q->whereHas('teams', function ($q) use ($teamId) {
                $q->where('teams.id', $teamId);
            });
        })->where('status', 'pending')->count();
    }

    public function getAllPendingRequestsCount(): int
    {
        return OverTimeRequests::where('status', 'pending')->count();
    }
    public function createRequest(array $data): OverTimeRequests
    {
        return DB::transaction(function () use ($data) {
            $userId = $data['user_id'] ?? Auth::id();
            $currentUser = Auth::user();

            $this->validateOverTimeRequest(
                $userId,
                $data['overtime_date'],
                $data['start_time'],
                $data['end_time']
            );

            $managerStatus = 'pending';
            $hrStatus = 'pending';

            $isRequestForSelf = $userId == $currentUser->id;

            if ($currentUser->hasRole('hr') && $currentUser->hasPermissionTo('manager_respond_overtime_request')) {
                $isInHrOwnedTeam = false;
                if ($currentUser->currentTeam && $currentUser->teams()->whereHas('users', function ($q) {
                    $q->whereHas('roles', function ($r) {
                        $r->where('name', 'hr');
                    });
                })->exists()) {
                    $isInHrOwnedTeam = true;
                }

                if ($isRequestForSelf && $isInHrOwnedTeam) {
                    $managerStatus = 'approved';
                    $hrStatus = 'approved';
                } elseif (!$isRequestForSelf) {
                    $hrStatus = 'approved';

                    $requestUser = User::find($userId);
                    $isRequestUserInCurrentTeam = false;

                    if ($requestUser && $currentUser->currentTeam) {
                        $isRequestUserInCurrentTeam = DB::table('team_user')
                            ->where('team_id', $currentUser->currentTeam->id)
                            ->where('user_id', $userId)
                            ->exists();
                    }

                    if ($isRequestUserInCurrentTeam) {
                        $managerStatus = 'approved';
                    }
                }
            } elseif ($currentUser->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager']) && !$isRequestForSelf && $currentUser->hasPermissionTo('manager_respond_overtime_request')) {
                $requestUser = User::find($userId);
                $isRequestUserInCurrentTeam = false;

                if ($requestUser && $currentUser->currentTeam) {
                    $isRequestUserInCurrentTeam = DB::table('team_user')
                        ->where('team_id', $currentUser->currentTeam->id)
                        ->where('user_id', $userId)
                        ->exists();
                }

                if ($isRequestUserInCurrentTeam) {
                    $managerStatus = 'approved';
                }
            } elseif ($currentUser->hasRole('hr')) {
                $hrStatus = 'approved';

                if (!$isRequestForSelf) {
                    $requestUser = User::find($userId);
                    $isRequestUserInCurrentTeam = false;

                    if ($requestUser && $currentUser->currentTeam) {
                        $isRequestUserInCurrentTeam = DB::table('team_user')
                            ->where('team_id', $currentUser->currentTeam->id)
                            ->where('user_id', $userId)
                            ->exists();
                    }

                    if ($isRequestUserInCurrentTeam && $currentUser->hasPermissionTo('manager_respond_overtime_request')) {
                        $managerStatus = 'approved';
                    }
                }
            } elseif (!$isRequestForSelf) {
                $isTeamOwner = false;

                if ($currentUser->currentTeam && $currentUser->currentTeam->user_id == $currentUser->id) {
                    $isTeamOwner = true;
                }

                $requestUser = User::find($userId);
                $isTeamMember = false;

                if ($requestUser && $currentUser->currentTeam) {
                    $isTeamMember = DB::table('team_user')
                        ->where('team_id', $currentUser->currentTeam->id)
                        ->where('user_id', $userId)
                        ->exists();
                }

                if ($isTeamOwner && $isTeamMember && $currentUser->hasPermissionTo('manager_respond_overtime_request')) {
                    $managerStatus = 'approved';
                }

                if ($currentUser->hasRole('hr')) {
                    if ($requestUser && !$requestUser->teams()->exists()) {
                        $hrStatus = 'approved';
                    }
                }
            }

            $request = new OverTimeRequests([
                'user_id' => $userId,
                'overtime_date' => $data['overtime_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'reason' => $data['reason'],
                'manager_status' => $managerStatus,
                'hr_status' => $hrStatus,
            ]);

            $request->user = User::find($userId);

            $request->updateFinalStatus();

            $request = OverTimeRequests::create([
                'user_id' => $userId,
                'overtime_date' => $data['overtime_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'reason' => $data['reason'],
                'manager_status' => $managerStatus,
                'hr_status' => $hrStatus,
                'status' => $request->status
            ]);

            $this->notificationService->createOvertimeRequestNotification($request);

            return $request;
        });
    }

    public function update(OverTimeRequests $request, array $data): bool
    {
        return DB::transaction(function () use ($request, $data) {
            $this->validateOverTimeRequest(
                $request->user_id,
                $data['overtime_date'],
                $data['start_time'],
                $data['end_time'],
                $request->id
            );

            $updated = $request->update([
                'overtime_date' => $data['overtime_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'reason' => $data['reason']
            ]);

            if ($updated) {
                $this->notificationService->notifyOvertimeModified($request);
            }

            return $updated;
        });
    }

    public function deleteRequest(OverTimeRequests $request): bool
    {
        return DB::transaction(function () use ($request) {
            try {
                $this->notificationService->notifyOvertimeDeleted($request);
                $deleted = $request->delete();

                if (!$deleted) {
                    throw new \Exception('Failed to delete overtime request.');
                }

                return true;
            } catch (\Exception $e) {
                Log::error('Error deleting overtime request: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    public function updateStatus(OverTimeRequests $request, array $data): bool
    {
        return DB::transaction(function () use ($request, $data) {
            $responseType = $data['response_type'];
            $status = $data['status'];
            $rejectionReason = $status === 'rejected' ? $data['rejection_reason'] : null;

            if ($responseType === 'manager') {
                $request->manager_status = $status;
                $request->manager_rejection_reason = $rejectionReason;
                $request->updateFinalStatus();
                $request->save();
                $this->notificationService->notifyManagerStatusUpdate($request);
            } elseif ($responseType === 'hr') {
                $request->hr_status = $status;
                $request->hr_rejection_reason = $rejectionReason;
                $request->updateFinalStatus();
                $request->save();
                $this->notificationService->notifyHRStatusUpdate($request);
            }

            return true;
        });
    }

    public function resetStatus(OverTimeRequests $request, string $type): bool
    {
        return DB::transaction(function () use ($request, $type) {
            if ($type === 'manager') {
                $request->manager_status = 'pending';
                $request->manager_rejection_reason = null;
            } else {
                $request->hr_status = 'pending';
                $request->hr_rejection_reason = null;
            }

            $request->updateFinalStatus();
            $updated = $request->save();

            if ($updated) {
                $this->notificationService->notifyStatusReset($request, $type);
            }

            return $updated;
        });
    }

    public function modifyResponse(OverTimeRequests $request, array $data): bool
    {
        return DB::transaction(function () use ($request, $data) {
            $this->notificationService->deleteExistingStatusNotifications($request);

            $updated = $request->update([
                'status' => $data['status'],
                'rejection_reason' => $data['status'] === 'rejected' ? $data['rejection_reason'] : null
            ]);

            if ($updated) {
                $this->notificationService->notifyStatusUpdate($request);
            }

            return $updated;
        });
    }

    public function calculateOvertimeHours($userId, $dateStart = null, $dateEnd = null, $status = null)
    {
        $query = OverTimeRequests::where('user_id', $userId)
            ->where('status', 'approved')
            ->when($dateStart && $dateEnd, function ($query) use ($dateStart, $dateEnd) {
                return $query->whereBetween('overtime_date', [$dateStart, $dateEnd]);
            })
            ->when($status, function ($query) use ($status) {
                return $query->where('status', $status);
            });

        return $query->selectRaw('COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))/3600), 0) as total_hours')
            ->value('total_hours');
    }

    public function updateManagerStatus(OverTimeRequests $request, array $data): bool
    {
        return DB::transaction(function () use ($request, $data) {
            $request->manager_status = $data['status'];
            $request->manager_rejection_reason = $data['status'] === 'rejected' ? $data['rejection_reason'] : null;
            $request->updateFinalStatus();
            $request->save();

            $this->notificationService->notifyManagerStatusUpdate($request);
            return true;
        });
    }

    public function updateHrStatus(OverTimeRequests $request, array $data): bool
    {
        return DB::transaction(function () use ($request, $data) {
            $request->hr_status = $data['status'];
            $request->hr_rejection_reason = $data['status'] === 'rejected' ? $data['rejection_reason'] : null;
            $request->updateFinalStatus();
            $request->save();

            $this->notificationService->notifyHRStatusUpdate($request);
            return true;
        });
    }

    protected function validateOverTimeRequest(
        int $userId,
        string $overtimeDate,
        string $startTime,
        string $endTime,
        ?int $excludeId = null
    ): void {
        $requestDate = Carbon::parse($overtimeDate);

        $query = OverTimeRequests::where('user_id', $userId)
            ->whereYear('overtime_date', $requestDate->year)
            ->whereMonth('overtime_date', $requestDate->month);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $overlappingRequest = $query->where(function ($q) use ($overtimeDate, $startTime, $endTime) {
            $q->where('overtime_date', $overtimeDate)
                ->where(function ($timeQuery) use ($startTime, $endTime) {
                    $timeQuery->where(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<=', $startTime)
                            ->where('end_time', '>', $startTime);
                    })
                        ->orWhere(function ($q) use ($startTime, $endTime) {
                            $q->where('start_time', '<', $endTime)
                                ->where('end_time', '>=', $endTime);
                        })
                        ->orWhere(function ($q) use ($startTime, $endTime) {
                            $q->where('start_time', '>=', $startTime)
                                ->where('end_time', '<=', $endTime);
                        });
                });
        })->first();

        if ($overlappingRequest) {
            throw new \Exception(
                'An overtime request already exists that overlaps with this time period. ' .
                    'Existing request: ' . $overlappingRequest->overtime_date .
                    ' (' . $overlappingRequest->start_time . ' - ' . $overlappingRequest->end_time . ')'
            );
        }
    }

    public function canRespond($user = null)
    {
        $user = $user ?? Auth::user();

        if (
            $user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager']) &&
            $user->hasPermissionTo('manager_respond_overtime_request')
        ) {
            return true;
        }

        if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_overtime_request')) {
            return true;
        }

        return false;
    }

    public function getAllowedUsers($user)
    {
        if ($user->hasRole('hr')) {
            return User::whereDoesntHave('roles', function ($q) {
                $q->whereIn('name', ['hr', 'company_manager']);
            })->get();
        }

        if (!$user->currentTeam) {
            return collect();
        }

        $allowedRoles = [];
        if ($user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader'])) {
            $allowedRoles = ['employee'];
        } elseif ($user->hasRole(['department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager'])) {
            $allowedRoles = ['employee', 'team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader'];
        } elseif ($user->hasRole(['project_manager', 'operations_manager'])) {
            $allowedRoles = ['employee', 'team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager'];
        } elseif ($user->hasRole('company_manager')) {
            $allowedRoles = ['employee', 'team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager'];
        }

        $users = $user->currentTeam->users()
            ->select('users.*')
            ->whereHas('roles', function ($q) use ($allowedRoles) {
                $q->whereIn('name', $allowedRoles);
            })
            ->whereDoesntHave('teams', function ($q) use ($user) {
                $q->where('teams.id', $user->currentTeam->id)
                    ->where(function ($q) {
                        $q->where('team_user.role', 'owner')
                            ->orWhere('team_user.role', 'admin');
                    });
            })
            ->get();

        return $users;
    }
}
