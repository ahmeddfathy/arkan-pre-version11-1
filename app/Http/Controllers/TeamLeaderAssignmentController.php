<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectManagement\TeamLeaderAssignmentService;
use Illuminate\Http\Request;

class TeamLeaderAssignmentController extends Controller
{
    protected $assignmentService;

    public function __construct(TeamLeaderAssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    /**
     * عرض إحصائيات توزيع Team Leaders
     */
    public function statistics()
    {
        $stats = $this->assignmentService->getAssignmentStatistics();

        return view('admin.team-leader-assignments.statistics', compact('stats'));
    }

    /**
     * إعادة تعيين دورة التوزيع
     */
    public function resetRoundRobin()
    {
        $result = $this->assignmentService->resetRoundRobin();

        return response()->json($result);
    }

    /**
     * تعيين Team Leader محدد لمشروع
     */
    public function assignSpecific(Request $request, Project $project)
    {
        $request->validate([
            'team_leader_id' => 'required|exists:users,id'
        ]);

        $result = $this->assignmentService->assignSpecificTeamLeader(
            $project,
            $request->team_leader_id
        );

        if ($result['success']) {
            return response()->json($result);
        }

        return response()->json($result, 400);
    }

    /**
     * API: جلب إحصائيات توزيع Team Leaders
     */
    public function apiStatistics()
    {
        $stats = $this->assignmentService->getAssignmentStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
