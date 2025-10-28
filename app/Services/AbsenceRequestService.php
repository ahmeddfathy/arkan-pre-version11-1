<?php

namespace App\Services;

use App\Models\AbsenceRequest;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AbsenceRequestService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function getAllRequests()
    {
        $user = Auth::user();

        if ($user->role === 'manager') {
            return AbsenceRequest::with('user')
                ->latest()
                ->paginate(10);
        }

        return AbsenceRequest::where('user_id', $user->id)
            ->latest()
            ->paginate(10);
    }

    public function getUserRequests()
    {
        $user = Auth::user();

        if ($user->role === 'manager') {
            return AbsenceRequest::with('user')
                ->latest()
                ->paginate(10);
        }

        return AbsenceRequest::where('user_id', $user->id)
            ->latest()
            ->paginate(10);
    }

    public function createRequest(array $data)
    {
        $userId = Auth::id();
        $user = Auth::user();
        $existingRequest = AbsenceRequest::where('user_id', $userId)
            ->where('absence_date', $data['absence_date'])
            ->first();

        if ($existingRequest) {
            return redirect()->back()->withErrors(['absence_date' => 'You have already requested this day off.']);
        }

        $hrStatus = 'pending';
        $managerStatus = 'pending';

        if ($user->hasRole('hr')) {
            $hrStatus = 'approved';
        }

        if ($user->hasRole('hr') && $user->hasPermissionTo('manager_respond_absence_request')) {
            $managerStatus = 'approved';
        }

        $request = AbsenceRequest::create([
            'user_id' => $userId,
            'absence_date' => $data['absence_date'],
            'reason' => $data['reason'],
            'status' => 'pending',
            'hr_status' => $hrStatus,
            'manager_status' => $managerStatus
        ]);

        $request->updateFinalStatus();
        $request->save();

        $this->notificationService->createLeaveRequestNotification($request);

        return $request;
    }

    public function createRequestForUser(int $userId, array $data)
    {
        $currentUser = Auth::user();

        if (!($currentUser->hasRole('team_leader') ||
            $currentUser->hasRole('technical_team_leader') ||
            $currentUser->hasRole('marketing_team_leader') ||
            $currentUser->hasRole('customer_service_team_leader') ||
            $currentUser->hasRole('coordination_team_leader') ||
            $currentUser->hasRole('department_manager') ||
            $currentUser->hasRole('technical_department_manager') ||
            $currentUser->hasRole('marketing_department_manager') ||
            $currentUser->hasRole('customer_service_department_manager') ||
            $currentUser->hasRole('coordination_department_manager') ||
            $currentUser->hasRole('project_manager') ||
            $currentUser->hasRole('operations_manager') ||
            $currentUser->hasRole('company_manager') ||
            $currentUser->hasRole('hr'))) {
            throw new \Illuminate\Auth\Access\AuthorizationException('You are not authorized to create requests for other users.');
        }

        if ($userId === $currentUser->id) {
            return $this->createRequest($data);
        }

        $existingRequest = AbsenceRequest::where('user_id', $userId)
            ->where('absence_date', $data['absence_date'])
            ->first();
        if ($existingRequest) {
            return redirect()->back()->withErrors(['absence_date' => 'This employee already has a request for this day off.']);
        }

        $targetUser = \App\Models\User::find($userId);

        $hasTeam = DB::table('team_user')->where('user_id', $userId)->exists();

        $hrStatus = 'pending';
        $managerStatus = 'pending';

        if ($currentUser->hasRole('hr')) {
            $hrStatus = 'approved';
        }

        if ($hasTeam) {
            if ($currentUser->hasAnyRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager'])) {
                $managerStatus = 'approved';
            } elseif ($currentUser->hasRole('hr') && $currentUser->hasPermissionTo('manager_respond_absence_request')) {
                $isTeamOwner = false;
                foreach ($currentUser->ownedTeams as $team) {
                    if ($team->users->contains('id', $userId)) {
                        $isTeamOwner = true;
                        break;
                    }
                }

                if ($isTeamOwner) {
                    $managerStatus = 'approved';
                }
            }
        }

        if ($targetUser && $targetUser->hasRole('hr')) {
            $hrStatus = 'approved';
        }

        $request = AbsenceRequest::create([
            'user_id' => $userId,
            'absence_date' => $data['absence_date'],
            'reason' => $data['reason'],
            'manager_status' => $managerStatus,
            'hr_status' => $hrStatus,
            'status' => 'pending'
        ]);

        $request->updateFinalStatus();
        $request->save();

        $this->notificationService->createLeaveRequestNotification($request);

        return $request;
    }

    public function updateRequest(AbsenceRequest $request, array $data)
    {
        $user = Auth::user();
        $existingRequest = AbsenceRequest::where('user_id', $request->user_id)
            ->where('absence_date', $data['absence_date'])
            ->where('id', '!=', $request->id)
            ->first();

        if ($existingRequest) {
            return redirect()->back()->withErrors(['absence_date' => 'You have already requested this day off.']);
        }

        $currentHrStatus = $request->hr_status;
        $currentManagerStatus = $request->manager_status;

        $request->update([
            'absence_date' => $data['absence_date'],
            'reason' => $data['reason']
        ]);

        if ($user->hasRole('hr') && $currentHrStatus === 'approved') {
            $request->hr_status = 'approved';
            $request->save();
        }

        $request->updateFinalStatus();
        $request->save();

        $this->notificationService->notifyRequestModified($request);

        return $request;
    }

    public function deleteRequest(AbsenceRequest $request)
    {
        $this->notificationService->notifyRequestDeleted($request);
        return $request->delete();
    }

    public function updateStatus(AbsenceRequest $request, array $data)
    {
        if (!$this->canRespond()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('You are not authorized to respond to absence requests.');
        }

        DB::transaction(function () use ($request, $data) {
            $oldStatus = [
                'manager_status' => $request->manager_status,
                'hr_status' => $request->hr_status
            ];

            if ($data['response_type'] === 'manager') {
                $request->manager_status = $data['status'];
                $request->manager_rejection_reason = $data['status'] === 'rejected' ? $data['rejection_reason'] : null;
            } elseif ($data['response_type'] === 'hr') {
                $request->hr_status = $data['status'];
                $request->hr_rejection_reason = $data['status'] === 'rejected' ? $data['rejection_reason'] : null;
            }

            $request->updateFinalStatus();
            $request->save();

            if (
                $oldStatus['manager_status'] !== $request->manager_status ||
                $oldStatus['hr_status'] !== $request->hr_status
            ) {
                $this->notificationService->createStatusUpdateNotification($request);
            }
        });

        return $request;
    }

    public function resetStatus(AbsenceRequest $request, string $responseType): AbsenceRequest
    {
        return DB::transaction(function () use ($request, $responseType) {
            if ($responseType === 'manager') {
                $request->manager_status = 'pending';
                $request->manager_rejection_reason = null;
            } elseif ($responseType === 'hr') {
                $request->hr_status = 'pending';
                $request->hr_rejection_reason = null;
            }

            $request->updateFinalStatus();
            $request->save();

            $this->notificationService->createStatusResetNotification($request, $responseType);

            return $request;
        });
    }

    public function modifyResponse(AbsenceRequest $request, array $data): AbsenceRequest
    {
        return DB::transaction(function () use ($request, $data) {
            $oldStatus = [
                'manager_status' => $request->manager_status,
                'hr_status' => $request->hr_status
            ];

            if ($data['response_type'] === 'manager') {
                $request->manager_status = $data['status'];
                $request->manager_rejection_reason = $data['status'] === 'rejected' ? $data['rejection_reason'] : null;
            } elseif ($data['response_type'] === 'hr') {
                $request->hr_status = $data['status'];
                $request->hr_rejection_reason = $data['status'] === 'rejected' ? $data['rejection_reason'] : null;
            }

            $request->updateFinalStatus();
            $request->save();

            $this->notificationService->createResponseModificationNotification($request, $data['response_type']);

            return $request;
        });
    }

    public function canRespond($user = null)
    {
        $user = $user ?? Auth::user();

        if (
            $user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager']) &&
            $user->hasPermissionTo('manager_respond_absence_request')
        ) {
            return true;
        }

        if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_absence_request')) {
            return true;
        }

        if ($user->hasRole('hr') && $user->hasPermissionTo('manager_respond_absence_request') && $user->ownedTeams->count() > 0) {
            return true;
        }

        return false;
    }

    public function canModifyRequest(AbsenceRequest $request, $user = null)
    {
        $user = $user ?? Auth::user();

        if ($user->id === $request->user_id && $request->status === 'pending') {
            return true;
        }

        if (
            $user->hasRole(['team_leader', 'technical_team_leader', 'marketing_team_leader', 'customer_service_team_leader', 'coordination_team_leader', 'department_manager', 'technical_department_manager', 'marketing_department_manager', 'customer_service_department_manager', 'coordination_department_manager', 'project_manager', 'operations_manager', 'company_manager']) &&
            $user->hasPermissionTo('manager_respond_absence_request')
        ) {
            return true;
        }

        if ($user->hasRole('hr') && $user->hasPermissionTo('hr_respond_absence_request')) {
            return true;
        }

        return false;
    }
}
