<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\Auth\RoleCheckService;
use App\Services\Notifications\MeetingNotificationService;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MeetingController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public $roleCheckService;
    public $meetingNotificationService;

    public function __construct(RoleCheckService $roleCheckService, MeetingNotificationService $meetingNotificationService)
    {
        $this->roleCheckService = $roleCheckService;
        $this->meetingNotificationService = $meetingNotificationService;
    }

    /**
     * Display a listing of the meetings.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Check if user is Technical Support or HR - they can see all meetings
        $canSeeAllMeetings = $this->roleCheckService->userHasRole('technical_support') ||
                            $this->roleCheckService->userHasRole('hr') ||
                            $this->roleCheckService->userHasRole('admin');

        if ($canSeeAllMeetings) {
            // Technical Support, HR, and Admin can see ALL meetings
            $query = Meeting::query();
        } else {
            // Regular users can only see meetings where they are creator or participant
            $query = Meeting::where(function ($q) use ($user) {
                    $q->where('created_by', $user->id)
                      ->orWhereHas('participants', function ($sub) use ($user) {
                          $sub->where('user_id', $user->id);
                      });
                });
        }

        // Apply optional filter: today | week | exact date
        $filter = $request->query('filter');
        if ($filter === 'today') {
            $query->whereBetween('start_time', [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()]);
        } elseif ($filter === 'week') {
            $query->whereBetween('start_time', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        }

        // Exact date filter (YYYY-MM-DD)
        if ($request->filled('date')) {
            $date = Carbon::parse($request->query('date'));
            $query->whereBetween('start_time', [$date->copy()->startOfDay(), $date->copy()->endOfDay()]);
        }

        $meetings = $query
            ->with(['creator', 'client', 'participants'])
            ->orderBy('start_time', 'desc')
            ->get();

        return view('meetings.index', compact('meetings'));
    }

    /**
     * Show the form for creating a new meeting.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create()
    {
        $users = User::all();

        // فقط موظفو المبيعات يمكنهم رؤية العملاء
        $canViewClients = $this->roleCheckService->userHasRole('sales_employee');
        $clients = $canViewClients ? Client::all() : collect();

        // المشاريع - إخفاء أسماء العملاء للموظفين العاديين
        if ($canViewClients) {
            $projects = \App\Models\Project::with('client')->orderBy('name')->get();
        } else {
            $projects = \App\Models\Project::orderBy('name')->get();
        }

        // جميع الموظفين يمكنهم طلب اجتماعات عميل (مع نظام الموافقة)
        $canCreateClientMeetings = true;
        $hasDirectPermission = $this->roleCheckService->hasPermission(Auth::user(), 'schedule_client_meetings');

        return view('meetings.create', compact('users', 'clients', 'projects', 'canCreateClientMeetings', 'hasDirectPermission', 'canViewClients'));
    }

    /**
     * Store a newly created meeting in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'location' => 'nullable|string|max:255',
            'type' => 'required|in:internal,client',
            'status' => 'nullable|in:scheduled,cancelled,completed',
            'client_id' => 'nullable|exists:clients,id',
            'project_id' => 'nullable|exists:projects,id',
            'participants' => 'required|array',
            'participants.*' => 'exists:users,id'
        ]);

        // Determine approval status
        $approvalStatus = 'auto_approved'; // Default for internal meetings
        $meetingType = $request->type;

        // Only client meetings may need approval
        if ($meetingType === 'client') {
            // فقط technical_support يملكون auto_approved
            // بقية الموظفين يجب أن تنتظر الموافقة من Technical Support
            $isTechnicalSupport = $this->roleCheckService->userHasRole('technical_support');

            if (!$isTechnicalSupport) {
                // If user is NOT technical support, set status to pending (needs approval)
                $approvalStatus = 'pending';
            } else {
                // User is technical_support, so auto-approve
                $approvalStatus = 'auto_approved';
            }
        }

        // Create meeting with determined approval status
        $meeting = Meeting::create([
            'title' => $request->title,
            'description' => $request->description,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'location' => $request->location,
            'type' => $meetingType,
            'status' => $request->status ?? 'scheduled',
            'created_by' => Auth::id(),
            'client_id' => ($meetingType === 'client') ? $request->client_id : null,
            'project_id' => $request->project_id,
            'approval_status' => $approvalStatus,
        ]);

        // Attach participants
        $meeting->participants()->attach($request->participants);

        // Send appropriate notifications
        if ($approvalStatus === 'pending') {
            // Notify technical support for approval
            $this->meetingNotificationService->notifyClientMeetingApprovers($meeting, Auth::user());

            return redirect()->route('meetings.show', $meeting)
                ->with('info', 'تم إنشاء الاجتماع وإرسال طلب موافقة للـ Technical Support');
        } else {
            // Auto-approved, notify participants
            $this->meetingNotificationService->notifyMeetingParticipants(
                $meeting,
                $request->participants,
                Auth::user()
            );

            return redirect()->route('meetings.show', $meeting)
                ->with('success', 'تم إنشاء الاجتماع بنجاح!');
        }
    }


    public function show(Meeting $meeting)
    {
        $meeting->load(['creator', 'client', 'participants', 'project.client']);
        $userIsParticipant = $meeting->participants->contains('id', Auth::id());
        $userIsCreator = $meeting->created_by === Auth::id();
        $isTechnicalSupport = $this->roleCheckService->userHasRole('technical_support');
        $isHR = $this->roleCheckService->userHasRole('hr');
        $isAdmin = $this->roleCheckService->userHasRole('admin');

        // Check if user has permission to view this meeting
        // Allowed: creator, participants, technical_support, hr, or admin
        if (!$userIsParticipant && !$userIsCreator && !$isTechnicalSupport && !$isHR && !$isAdmin) {
            abort(403, 'You do not have permission to view this meeting.');
        }

        // Check if user can view client data
        $canViewClientData = $this->roleCheckService->userHasRole('sales_employee') ||
                            $this->roleCheckService->userHasRole('technical_support');

        return view('meetings.show', compact('meeting', 'canViewClientData', 'isTechnicalSupport'));
    }


    public function edit(Meeting $meeting)
    {
        // Only creator or admin can edit a meeting
        if ($meeting->created_by !== Auth::id() && !$this->roleCheckService->userHasRole('admin')) {
            abort(403, 'You do not have permission to edit this meeting.');
        }

        $users = User::all();

        // فقط موظفو المبيعات يمكنهم رؤية العملاء
        $canViewClients = $this->roleCheckService->userHasRole('sales_employee');
        $clients = $canViewClients ? Client::all() : collect();

        // المشاريع - إخفاء أسماء العملاء للموظفين العاديين
        if ($canViewClients) {
            $projects = \App\Models\Project::with('client')->orderBy('name')->get();
        } else {
            $projects = \App\Models\Project::orderBy('name')->get();
        }

        $selectedParticipants = $meeting->participants->pluck('id')->toArray();
        $canCreateClientMeetings = $this->roleCheckService->hasPermission(Auth::user(), 'schedule_client_meetings');
        $hasDirectPermission = $this->roleCheckService->hasPermission(Auth::user(), 'schedule_client_meetings');

        return view('meetings.edit', compact('meeting', 'users', 'clients', 'projects', 'selectedParticipants', 'canCreateClientMeetings', 'hasDirectPermission', 'canViewClients'));
    }

    /**
     * Update the specified meeting in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Meeting  $meeting
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Meeting $meeting)
    {
        // Only creator or admin can update a meeting
        if ($meeting->created_by !== Auth::id() && !$this->roleCheckService->userHasRole('admin')) {
            abort(403, 'You do not have permission to update this meeting.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'location' => 'nullable|string|max:255',
            'type' => 'required|in:internal,client',
            'status' => 'nullable|in:scheduled,cancelled,completed',
            'client_id' => 'nullable|exists:clients,id',
            'project_id' => 'nullable|exists:projects,id',
            'participants' => 'required|array',
            'participants.*' => 'exists:users,id'
        ]);

        // Update meeting
        $meeting->update([
            'title' => $request->title,
            'description' => $request->description,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'location' => $request->location,
            'type' => $request->type,
            'status' => $request->status ?? $meeting->status,
            'client_id' => $request->type === 'client' ? $request->client_id : null,
            'project_id' => $request->project_id,
        ]);

        // Sync participants
        $meeting->participants()->sync($request->participants);

        return redirect()->route('meetings.show', $meeting)
            ->with('success', 'Meeting updated successfully.');
    }

    /**
     * Remove the specified meeting from storage.
     *
     * @param  \App\Models\Meeting  $meeting
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Meeting $meeting)
    {
        // Only creator or admin can delete a meeting
        if ($meeting->created_by !== Auth::id() && !$this->roleCheckService->userHasRole('admin')) {
            abort(403, 'You do not have permission to delete this meeting.');
        }

        $meeting->participants()->detach();
        $meeting->delete();

        return redirect()->route('meetings.index')
            ->with('success', 'Meeting deleted successfully.');
    }

    /**
     * Add a note to the meeting.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Meeting  $meeting
     * @return \Illuminate\Http\RedirectResponse
     */
    public function addNote(Request $request, Meeting $meeting)
    {
        $request->validate([
            'content' => 'required|string'
        ]);

        // Check if user is a participant or creator
        $userIsParticipant = $meeting->participants->contains('id', Auth::id());
        $userIsCreator = $meeting->created_by === Auth::id();

        if (!$userIsParticipant && !$userIsCreator) {
            abort(403, 'Only meeting participants can add notes.');
        }

        // Extract mentions from note content
        $mentions = $this->extractNoteMentions($request->content, $meeting);

        $this->addNoteToMeeting($meeting, Auth::id(), $request->content, $mentions);

        // Send mention notifications
        if (!empty($mentions)) {
            $this->meetingNotificationService->notifyMentionedUsersInNote($meeting, $mentions, Auth::user(), $request->content);
        }

        return redirect()->back()->with('success', 'Note added successfully.');
    }

    /**
     * Approve a client meeting.
     * Only Technical Support can approve meetings
     */
    public function approve(Request $request, Meeting $meeting)
    {
        // Check if user is Technical Support
        if (!$this->roleCheckService->userHasRole('technical_support')) {
            abort(403, 'فقط الـ Technical Support يمكنهم الموافقة على اجتماعات العملاء.');
        }

        // Reload the meeting from database to get latest data
        $meeting = $meeting->fresh();

        // Verify meeting exists and is valid
        if (!$meeting) {
            return redirect()->back()->with('error', 'الاجتماع غير موجود.');
        }

        // Check if meeting needs approval - approval_status must be pending
        if ($meeting->approval_status !== 'pending') {
            return redirect()->back()->with('error', 'هذا الاجتماع تمت معالجته بالفعل (الحالة الحالية: ' . $meeting->approval_status . ').');
        }

        // Check if meeting is client type - type must be client
        if ($meeting->type !== 'client') {
            return redirect()->back()->with('error', 'يمكن الموافقة فقط على اجتماعات العملاء. هذا الاجتماع نوعه: ' . $meeting->type);
        }

        $request->validate([
            'approval_notes' => 'nullable|string|max:500'
        ]);

        $meeting->update([
            'approval_status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'approval_notes' => $request->approval_notes,
        ]);

        // Send notifications to participants
        $participantIds = $meeting->participants->pluck('id')->toArray();
        $this->meetingNotificationService->notifyMeetingParticipants(
            $meeting,
            $participantIds,
            Auth::user()
        );

        // Notify creator
        $this->meetingNotificationService->notifyMeetingApprovalResult($meeting, 'approved', Auth::user());

        return redirect()->back()->with('success', 'تم الموافقة على الاجتماع وإرسال الإشعارات للمشاركين.');
    }

    /**
     * Reject a client meeting.
     * Only Technical Support can reject meetings
     */
    public function reject(Request $request, Meeting $meeting)
    {
        // Check if user is Technical Support
        if (!$this->roleCheckService->userHasRole('technical_support')) {
            abort(403, 'فقط الـ Technical Support يمكنهم رفض اجتماعات العملاء.');
        }

        // Reload the meeting from database to get latest data
        $meeting = $meeting->fresh();

        // Verify meeting exists and is valid
        if (!$meeting) {
            return redirect()->back()->with('error', 'الاجتماع غير موجود.');
        }

        // Check if meeting needs approval - approval_status must be pending
        if ($meeting->approval_status !== 'pending') {
            return redirect()->back()->with('error', 'هذا الاجتماع تمت معالجته بالفعل (الحالة الحالية: ' . $meeting->approval_status . ').');
        }

        // Check if meeting is client type - type must be client
        if ($meeting->type !== 'client') {
            return redirect()->back()->with('error', 'يمكن رفض فقط اجتماعات العملاء. هذا الاجتماع نوعه: ' . $meeting->type);
        }

        $request->validate([
            'approval_notes' => 'required|string|max:500'
        ]);

        $meeting->update([
            'approval_status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'approval_notes' => $request->approval_notes,
        ]);

        // Notify creator
        $this->meetingNotificationService->notifyMeetingApprovalResult($meeting, 'rejected', Auth::user());

        return redirect()->back()->with('success', 'تم رفض الاجتماع وإشعار منشئ الاجتماع.');
    }

    /**
     * Update meeting time by Technical Support.
     * Only Technical Support can modify meeting times
     */
    public function updateTime(Request $request, Meeting $meeting)
    {
        // Check if user is Technical Support
        if (!$this->roleCheckService->userHasRole('technical_support')) {
            abort(403, 'فقط الـ Technical Support يمكنهم تعديل اجتماعات العملاء.');
        }

        // Reload the meeting from database to get latest data
        $meeting = $meeting->fresh();

        // Verify meeting exists and is valid
        if (!$meeting) {
            return redirect()->back()->with('error', 'الاجتماع غير موجود.');
        }

        // Check if meeting needs approval - approval_status must be pending
        if ($meeting->approval_status !== 'pending') {
            return redirect()->back()->with('error', 'هذا الاجتماع تمت معالجته بالفعل (الحالة الحالية: ' . $meeting->approval_status . ').');
        }

        // Check if meeting is client type - type must be client
        if ($meeting->type !== 'client') {
            return redirect()->back()->with('error', 'يمكن تعديل وقت فقط اجتماعات العملاء. هذا الاجتماع نوعه: ' . $meeting->type);
        }

        $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'approval_notes' => 'nullable|string|max:500'
        ]);

        $meeting->update([
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'approval_status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'approval_notes' => $request->approval_notes,
        ]);

        // Send notifications to participants with new time
        $participantIds = $meeting->participants->pluck('id')->toArray();
        $this->meetingNotificationService->notifyMeetingTimeUpdated(
            $meeting,
            $participantIds,
            Auth::user()
        );

        // Notify creator about time change
        $this->meetingNotificationService->notifyMeetingApprovalResult($meeting, 'time_updated', Auth::user());

        return redirect()->back()->with('success', 'تم تحديث وقت الاجتماع والموافقة عليه من قبل الـ Technical Support.');
    }

    /**
     * Mark attendance for a participant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Meeting  $meeting
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAttendance(Request $request, Meeting $meeting)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'attended' => 'required|boolean'
        ]);

        // Only meeting creator or admin can mark attendance
        if ($meeting->created_by !== Auth::id() && !$this->roleCheckService->userHasRole('admin')) {
            abort(403, 'Only the meeting organizer can mark attendance.');
        }

        // Update the pivot
        $meeting->participants()->updateExistingPivot(
            $request->user_id,
            ['attended' => $request->attended]
        );

        return redirect()->back()->with('success', 'Attendance updated.');
    }

    /**
     * Mark a meeting as completed.
     *
     * @param  \App\Models\Meeting  $meeting
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markCompleted(Meeting $meeting)
    {
        // Only meeting creator or admin can mark as completed
        if ($meeting->created_by !== Auth::id() && !$this->roleCheckService->userHasRole('admin')) {
            abort(403, 'Only the meeting organizer can mark the meeting as completed.');
        }

        $meeting->markAsCompleted();

        return redirect()->back()->with('success', 'Meeting marked as completed.');
    }

    /**
     * Cancel a meeting.
     *
     * @param  \App\Models\Meeting  $meeting
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel(Meeting $meeting)
    {
        // Only meeting creator or admin can cancel a meeting
        if ($meeting->created_by !== Auth::id() && !$this->roleCheckService->userHasRole('admin')) {
            abort(403, 'Only the meeting organizer can cancel the meeting.');
        }

        // Can't cancel an already completed or cancelled meeting
        if ($meeting->isCompleted() || $meeting->isCancelled()) {
            return redirect()->back()->with('error', 'لا يمكن إلغاء اجتماع مكتمل أو ملغي بالفعل.');
        }

        $meeting->cancel();

        // Send notifications to participants about cancellation
        $participantIds = $meeting->participants->pluck('id')->toArray();
        $this->meetingNotificationService->notifyMeetingCancelled(
            $meeting,
            $participantIds,
            Auth::user()
        );

        return redirect()->back()->with('success', 'تم إلغاء الاجتماع وإشعار المشاركين.');
    }

    /**
     * Reset meeting status to scheduled.
     *
     * @param  \App\Models\Meeting  $meeting
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetStatus(Meeting $meeting)
    {
        // Only meeting creator or admin can reset status
        if ($meeting->created_by !== Auth::id() && !$this->roleCheckService->userHasRole('admin')) {
            abort(403, 'Only the meeting organizer can reset the meeting status.');
        }

        // Reset status to scheduled
        $meeting->update(['status' => 'scheduled']);

        return redirect()->back()->with('success', 'تم إعادة تعيين حالة الاجتماع إلى مجدول.');
    }

    /**
     * Reset approval status back to pending
     * Allows technical_support to undo an approval and go back to pending status
     *
     * @param Meeting $meeting
     * @return \Illuminate\Http\RedirectResponse
     */
    public function undoApproval(Meeting $meeting)
    {
        // Check if user is Technical Support
        if (!$this->roleCheckService->userHasRole('technical_support')) {
            abort(403, 'فقط الـ Technical Support يمكنهم إلغاء الموافقة.');
        }

        // Reload the meeting from database to get latest data
        $meeting = $meeting->fresh();

        // Verify meeting exists and is valid
        if (!$meeting) {
            return redirect()->back()->with('error', 'الاجتماع غير موجود.');
        }

        // Check if meeting is client type
        if ($meeting->type !== 'client') {
            return redirect()->back()->with('error', 'يمكن فقط إلغاء موافقة اجتماعات العملاء.');
        }

        // Check if meeting is approved (can only undo approved meetings)
        if (!in_array($meeting->approval_status, ['approved', 'auto_approved'])) {
            return redirect()->back()->with('error', 'الاجتماع غير موافق عليه (الحالة الحالية: ' . $meeting->approval_status . ').');
        }

        // Reset approval status back to pending
        $meeting->update([
            'approval_status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'approval_notes' => null,
        ]);

        return redirect()->back()->with('success', 'تم إلغاء الموافقة. الاجتماع الآن في انتظار الموافقة مجدداً.');
    }

    /**
     * Extract mentions from note content
     *
     * @param string $text
     * @param Meeting $meeting
     * @return array
     */
    public function extractNoteMentions($text, $meeting)
    {
        $availableUsers = collect();

        // إضافة المشاركين في الاجتماع
        if ($meeting->participants) {
            $availableUsers = $availableUsers->merge($meeting->participants);
        }

        // إضافة منشئ الاجتماع
        if ($meeting->creator) {
            $availableUsers->push($meeting->creator);
        }

        // إزالة التكرارات
        $users = $availableUsers->unique('id');

        $mentions = [];

        // التحقق من منشن الجميع (@everyone أو @الجميع)
        if (strpos($text, '@everyone') !== false || strpos($text, '@الجميع') !== false) {
            // إضافة جميع المستخدمين المتاحين
            $mentions = $users->pluck('id')->toArray();
        } else {
            // البحث عن منشن الأفراد
            $userNames = $users->mapWithKeys(function ($user) {
                return [$user->id => $user->name];
            });

            foreach ($userNames as $userId => $userName) {
                if (strpos($text, '@' . $userName) !== false) {
                    $mentions[] = $userId;
                }
            }
        }

        return array_unique($mentions);
    }

    /**
     * Add a note to the meeting
     *
     * @param Meeting $meeting
     * @param int $userId
     * @param string $content
     * @param array $mentions
     * @return void
     */
    public function addNoteToMeeting($meeting, $userId, $content, $mentions = [])
    {
        $notes = $meeting->notes ?? [];
        $notes[] = [
            'user_id' => $userId,
            'content' => $content,
            'mentions' => $mentions,
            'created_at' => now()->toIso8601String(),
        ];

        $meeting->notes = $notes;
        $meeting->save();
    }
}
