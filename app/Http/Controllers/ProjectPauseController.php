<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Client;
use App\Services\ProjectManagement\ProjectPauseService;
use Illuminate\Http\Request;

class ProjectPauseController extends Controller
{
    protected $projectPauseService;

    public function __construct(ProjectPauseService $projectPauseService)
    {
        $this->projectPauseService = $projectPauseService;
    }

    /**
     * عرض صفحة البحث وتوقيف المشاريع
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'search',
            'status',
            'client_id',
            'paused_only',
            'pause_reason',
            'manager',
            'client_date_from',
            'client_date_to',
            'team_date_from',
            'team_date_to'
        ]);

        $projects = $this->projectPauseService->searchProjects($filters);
        $clients = Client::all();
        $pauseReasons = Project::getPauseReasons();
        $stats = $this->projectPauseService->getPausedProjectsStats();

        return view('projects.pause.index', compact('projects', 'clients', 'pauseReasons', 'stats', 'filters'));
    }

    /**
     * توقيف مشروع واحد
     */
    public function pause(Request $request, Project $project)
    {
        $request->validate([
            'pause_reason' => 'required|in:واقف ع النموذج,واقف ع الأسئلة,واقف ع العميل,واقف ع مكالمة,موقوف',
            'pause_notes' => 'nullable|string'
        ]);

        $result = $this->projectPauseService->pauseProject(
            $project,
            $request->pause_reason,
            $request->pause_notes
        );

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * إلغاء توقيف مشروع
     */
    public function resume(Project $project)
    {
        $result = $this->projectPauseService->resumeProject($project);

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * توقيف مشاريع متعددة دفعة واحدة
     */
    public function pauseMultiple(Request $request)
    {
        // تحويل JSON إلى array إذا كان string
        $projectIds = $request->project_ids;
        if (is_string($projectIds)) {
            $projectIds = json_decode($projectIds, true);
        }

        $request->merge(['project_ids' => $projectIds]);

        $request->validate([
            'project_ids' => 'required|array',
            'project_ids.*' => 'exists:projects,id',
            'pause_reason' => 'required|in:واقف ع النموذج,واقف ع الأسئلة,واقف ع العميل,واقف ع مكالمة,موقوف',
            'pause_notes' => 'nullable|string'
        ]);

        $result = $this->projectPauseService->pauseMultipleProjects(
            $projectIds,
            $request->pause_reason,
            $request->pause_notes
        );

        if ($result['success']) {
            $message = $result['message'];
            if ($result['failed_count'] > 0) {
                $message .= '. فشل توقيف ' . $result['failed_count'] . ' مشروع.';
            }
            return redirect()->back()->with('success', $message);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * عرض صفحة إحصائيات المشاريع الموقوفة
     */
    public function stats()
    {
        $stats = $this->projectPauseService->getPausedProjectsStats();

        return view('projects.pause.stats', compact('stats'));
    }
}
