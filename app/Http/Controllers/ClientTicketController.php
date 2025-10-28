<?php

namespace App\Http\Controllers;

use App\Models\ClientTicket;
use App\Models\User;
use App\Models\TicketComment;
use App\Models\TicketAssignment;
use App\Models\TicketWorkflowHistory;
use App\Services\Notifications\TicketNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ClientTicketController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $ticketNotificationService;

    public function __construct(TicketNotificationService $ticketNotificationService)
    {
        $this->ticketNotificationService = $ticketNotificationService;
    }

    /**
     * Get available departments from users
     */
    private function getDepartments()
    {
        return User::select('department')
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->pluck('department');
    }

    /**
     * Display a listing of tickets.
     */
    public function index(Request $request)
    {
        // تسجيل النشاط - دخول صفحة تذاكر العملاء
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'action_type' => 'view_index',
                    'page' => 'client_tickets_index',
                    'filters' => $request->all(),
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('دخل على صفحة تذاكر العملاء');
        }

        $query = ClientTicket::with(['project', 'assignedEmployee', 'creator']);

        // Filter by project
        if ($request->has('project_id') && $request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by priority
        if ($request->has('priority') && $request->priority) {
            $query->where('priority', $request->priority);
        }

        // Filter by department
        if ($request->has('department') && $request->department) {
            $query->where('department', $request->department);
        }

        // Filter by assigned employee
        if ($request->has('assigned_to') && $request->assigned_to) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(15);

        $projects = \App\Models\Project::orderBy('name')->get();
        $employees = User::where('employee_status', 'active')->orderBy('name')->get();
        $departments = $this->getDepartments();

        return view('client-tickets.index', compact('tickets', 'projects', 'employees', 'departments'));
    }

    /**
     * Show the form for creating a new ticket.
     */
    public function create(Request $request)
    {
        $projects = \App\Models\Project::orderBy('name')->get();
        $employees = User::where('employee_status', 'active')->orderBy('name')->get();
        $departments = $this->getDepartments();
        $selectedProject = null;

        if ($request->has('project_id')) {
            $selectedProject = \App\Models\Project::find($request->project_id);
        }

        return view('client-tickets.create', compact('projects', 'employees', 'departments', 'selectedProject'));
    }

    /**
     * Store a newly created ticket.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'priority' => 'required|in:low,medium,high',
            'department' => 'nullable|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
            'assigned_users' => 'nullable|array',
            'assigned_users.*' => 'exists:users,id'
        ]);

        $validated['created_by'] = Auth::id();
        $validated['status'] = 'open';

        $ticket = ClientTicket::create($validated);

        // Add to history
        TicketWorkflowHistory::createRecord(
            $ticket->id,
            'created',
            'تم إنشاء التذكرة بواسطة ' . Auth::user()->name
        );

        // Assign users if any
        if (!empty($validated['assigned_users'])) {
            foreach ($validated['assigned_users'] as $userId) {
                // Check if user was previously assigned to this ticket
                $existingAssignment = TicketAssignment::where('ticket_id', $ticket->id)
                    ->where('user_id', $userId)
                    ->first();

                if ($existingAssignment) {
                    // Reactivate existing assignment
                    $existingAssignment->update([
                        'is_active' => true,
                        'assigned_by' => Auth::id(),
                        'assigned_at' => now(),
                        'unassigned_at' => null
                    ]);
                } else {
                    // Create new assignment
                    TicketAssignment::create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $userId,
                        'assigned_by' => Auth::id(),
                        'assigned_at' => now(),
                        'is_active' => true
                    ]);
                }
            }

            $ticket->update(['status' => 'assigned']);

            // Add to history
            $assignedNames = User::whereIn('id', $validated['assigned_users'])->pluck('name')->implode(', ');
            TicketWorkflowHistory::createRecord(
                $ticket->id,
                'assigned',
                'تم تعيين التذكرة إلى: ' . $assignedNames
            );

            // Send notifications to assigned users
            $this->ticketNotificationService->notifyUsersAssigned($ticket, $validated['assigned_users'], Auth::user());
        }

        return redirect()->route('client-tickets.index')
            ->with('success', 'تم إنشاء التذكرة بنجاح');
    }

    /**
     * Display the specified ticket.
     */
        public function show(ClientTicket $clientTicket)
    {
        // تسجيل النشاط - عرض تفاصيل تذكرة العميل
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->performedOn($clientTicket)
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'ticket_id' => $clientTicket->id,
                    'ticket_title' => $clientTicket->title,
                    'ticket_status' => $clientTicket->status,
                    'ticket_priority' => $clientTicket->priority,
                    'project_id' => $clientTicket->project_id,
                    'project_name' => $clientTicket->project->name ?? null,
                    'action_type' => 'view',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('شاهد تفاصيل تذكرة العميل');
        }

        $clientTicket->load([
            'project',
            'assignedEmployee',
            'creator',
            'comments.user',
            'activeAssignments.user',
            'history.user'
        ]);

        $employees = User::where('employee_status', 'active')->orderBy('name')->get();

        return view('client-tickets.show', compact('clientTicket', 'employees'));
    }

    /**
     * Show the form for editing the specified ticket.
     */
    public function edit(ClientTicket $clientTicket)
    {
        $projects = \App\Models\Project::orderBy('name')->get();
        $employees = User::where('employee_status', 'active')->orderBy('name')->get();
        $departments = $this->getDepartments();

        return view('client-tickets.edit', compact('clientTicket', 'projects', 'employees', 'departments'));
    }

    /**
     * Update the specified ticket.
     */
    public function update(Request $request, ClientTicket $clientTicket)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'status' => 'required|in:open,assigned,resolved,closed',
            'priority' => 'required|in:low,medium,high',
            'department' => 'nullable|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
            'assigned_to' => 'nullable|exists:users,id',
            'resolution_notes' => 'nullable|string|max:1000'
        ]);

        // Auto-set resolved_at if status changed to resolved
        if ($validated['status'] === 'resolved' && $clientTicket->status !== 'resolved') {
            $validated['resolved_at'] = now();
        }

        // Clear resolved_at if status changed from resolved
        if ($validated['status'] !== 'resolved' && $clientTicket->status === 'resolved') {
            $validated['resolved_at'] = null;
        }

        $clientTicket->update($validated);

        return redirect()->route('client-tickets.show', $clientTicket)
            ->with('success', 'تم تحديث التذكرة بنجاح');
    }

    /**
     * Remove the specified ticket.
     */
    public function destroy(ClientTicket $clientTicket)
    {
        $clientTicket->delete();

        return redirect()->route('client-tickets.index')
            ->with('success', 'تم حذف التذكرة بنجاح');
    }

    /**
     * Assign ticket to employee(s)
     */
    public function assign(Request $request, ClientTicket $clientTicket)
    {
        $validated = $request->validate([
            'assigned_users' => 'required|array',
            'assigned_users.*' => 'exists:users,id',
            'assignment_notes' => 'nullable|string|max:500'
        ]);

        // Deactivate current assignments
        $clientTicket->activeAssignments()->update([
            'is_active' => false,
            'unassigned_at' => now()
        ]);

                // Add new assignments
        $assignedNames = [];
        foreach ($validated['assigned_users'] as $userId) {
            // Check if user was previously assigned to this ticket
            $existingAssignment = TicketAssignment::where('ticket_id', $clientTicket->id)
                ->where('user_id', $userId)
                ->first();

            if ($existingAssignment) {
                // Reactivate existing assignment
                $existingAssignment->update([
                    'is_active' => true,
                    'assigned_by' => Auth::id(),
                    'assigned_at' => now(),
                    'assignment_notes' => $validated['assignment_notes'],
                    'unassigned_at' => null
                ]);
            } else {
                // Create new assignment
                TicketAssignment::create([
                    'ticket_id' => $clientTicket->id,
                    'user_id' => $userId,
                    'assigned_by' => Auth::id(),
                    'assigned_at' => now(),
                    'assignment_notes' => $validated['assignment_notes'],
                    'is_active' => true
                ]);
            }

            $assignedNames[] = User::find($userId)->name;
        }

        $clientTicket->update(['status' => 'assigned']);

        // Add to history
        $description = 'تم تعيين التذكرة إلى: ' . implode(', ', $assignedNames);
        if (!empty($validated['assignment_notes'])) {
            $description .= "\nملاحظات: " . $validated['assignment_notes'];
        }

        TicketWorkflowHistory::createRecord(
            $clientTicket->id,
            'assigned',
            $description
        );

        // Send notifications to assigned users
        $this->ticketNotificationService->notifyUsersAssigned($clientTicket, $validated['assigned_users'], Auth::user());

        return redirect()->route('client-tickets.show', $clientTicket)
            ->with('success', 'تم تعيين التذكرة بنجاح');
    }

    /**
     * Resolve ticket
     */
    public function resolve(Request $request, ClientTicket $clientTicket)
    {
        $validated = $request->validate([
            'resolution_notes' => 'required|string|max:2000'
        ]);

        $oldStatus = $clientTicket->status;

        $clientTicket->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolution_notes' => $validated['resolution_notes']
        ]);

        // Add to history
        TicketWorkflowHistory::createRecord(
            $clientTicket->id,
            'resolved',
            'تم حل التذكرة بواسطة ' . Auth::user()->name . "\nملاحظات الحل: " . $validated['resolution_notes'],
            $oldStatus,
            'resolved'
        );

        // Send notifications about resolution
        $this->ticketNotificationService->notifyTicketResolved($clientTicket, Auth::user());

        return redirect()->route('client-tickets.show', $clientTicket)
            ->with('success', 'تم حل التذكرة بنجاح');
    }

    /**
     * Reopen ticket
     */
    public function reopen(ClientTicket $clientTicket)
    {
        $oldStatus = $clientTicket->status;
        $newStatus = $clientTicket->activeAssignments()->exists() ? 'assigned' : 'open';

        $clientTicket->update([
            'status' => $newStatus,
            'resolved_at' => null
        ]);

        // Add to history
        TicketWorkflowHistory::createRecord(
            $clientTicket->id,
            'reopened',
            'تم إعادة فتح التذكرة بواسطة ' . Auth::user()->name,
            $oldStatus,
            $newStatus
        );

        return redirect()->route('client-tickets.show', $clientTicket)
            ->with('success', 'تم إعادة فتح التذكرة بنجاح');
    }



    /**
     * Dashboard view for tickets
     */
    public function dashboard()
    {
        // تسجيل النشاط - دخول لوحة تحكم تذاكر العملاء
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'action_type' => 'view_dashboard',
                    'page' => 'client_tickets_dashboard',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('دخل على لوحة تحكم تذاكر العملاء');
        }

        $stats = [
            'open' => ClientTicket::where('status', 'open')->count(),
            'assigned' => ClientTicket::where('status', 'assigned')->count(),
            'in_progress' => ClientTicket::where('status', 'assigned')->count(), // نفس assigned
            'resolved' => ClientTicket::where('status', 'resolved')->count(),
            'high_priority' => ClientTicket::where('priority', 'high')->open()->count()
        ];

        $recentTickets = ClientTicket::with(['project', 'assignedEmployee'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $highPriorityTickets = ClientTicket::with(['project', 'assignedEmployee'])
            ->where('priority', 'high')
            ->open()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('client-tickets.dashboard', compact('stats', 'recentTickets', 'highPriorityTickets'));
    }

    /**
     * Add simple comment to ticket
     */
    public function addComment(Request $request, ClientTicket $clientTicket)
    {
        $validated = $request->validate([
            'comment' => 'required|string|max:2000',
            'comment_type' => 'nullable|string|in:work_update,status_change,question,solution,general'
        ]);

        // Extract mentions from comment
        $mentions = TicketComment::extractMentions($validated['comment'], $clientTicket->id);

        $comment = TicketComment::create([
            'ticket_id' => $clientTicket->id,
            'user_id' => Auth::id(),
            'comment' => $validated['comment'],
            'comment_type' => $validated['comment_type'] ?? 'general',
            'mentions' => $mentions,
            'is_internal' => false,
            'is_system_message' => false
        ]);

        // Add to history
        TicketWorkflowHistory::createRecord(
            $clientTicket->id,
            'comment_added',
            'أضاف ' . Auth::user()->name . ' تعليقاً جديداً'
        );

        // Send notifications about new comment
        $this->ticketNotificationService->notifyOnComment($clientTicket, $comment);

        // Send mention notifications
        if (!empty($mentions)) {
            $this->ticketNotificationService->notifyMentionedUsers($clientTicket, $comment);
        }

        return redirect()->route('client-tickets.show', $clientTicket)
            ->with('success', 'تم إضافة التعليق بنجاح');
    }

    /**
     * Add user to ticket
     */
    public function addUser(Request $request, ClientTicket $clientTicket)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'assignment_notes' => 'nullable|string|max:500'
        ]);

        // Check if user is already actively assigned
        $existingActiveAssignment = $clientTicket->activeAssignments()
            ->where('user_id', $validated['user_id'])
            ->first();

        if ($existingActiveAssignment) {
            return redirect()->route('client-tickets.show', $clientTicket)
                ->with('error', 'هذا المستخدم معين للتذكرة بالفعل');
        }

        // Check if user was previously assigned (inactive)
        $existingAssignment = TicketAssignment::where('ticket_id', $clientTicket->id)
            ->where('user_id', $validated['user_id'])
            ->first();

        if ($existingAssignment) {
            // Reactivate existing assignment
            $existingAssignment->update([
                'is_active' => true,
                'assigned_by' => Auth::id(),
                'assigned_at' => now(),
                'assignment_notes' => $validated['assignment_notes'],
                'unassigned_at' => null
            ]);
        } else {
            // Create new assignment
            TicketAssignment::create([
                'ticket_id' => $clientTicket->id,
                'user_id' => $validated['user_id'],
                'assigned_by' => Auth::id(),
                'assigned_at' => now(),
                'assignment_notes' => $validated['assignment_notes'],
                'is_active' => true
            ]);
        }

        $clientTicket->update(['status' => 'assigned']);

        $addedUser = User::find($validated['user_id']);
        $userName = $addedUser->name;

        // Add to history
        TicketWorkflowHistory::createRecord(
            $clientTicket->id,
            'assigned',
            'تم إضافة ' . $userName . ' للتذكرة بواسطة ' . Auth::user()->name
        );

        // Send notification to added user
        $this->ticketNotificationService->notifyUserAdded($clientTicket, $addedUser, Auth::user());

        return redirect()->route('client-tickets.show', $clientTicket)
            ->with('success', 'تم إضافة المستخدم للتذكرة بنجاح');
    }

    /**
     * Remove user from ticket
     */
    public function removeUser(Request $request, ClientTicket $clientTicket)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $assignment = $clientTicket->activeAssignments()
            ->where('user_id', $validated['user_id'])
            ->first();

        if (!$assignment) {
            return redirect()->route('client-tickets.show', $clientTicket)
                ->with('error', 'المستخدم غير معين للتذكرة');
        }

        $assignment->update([
            'is_active' => false,
            'unassigned_at' => now()
        ]);

        // Update ticket status if no more active assignments
        if (!$clientTicket->activeAssignments()->exists()) {
            $clientTicket->update(['status' => 'open']);
        }

        $userName = User::find($validated['user_id'])->name;

        // Add to history
        TicketWorkflowHistory::createRecord(
            $clientTicket->id,
            'unassigned',
            'تم إزالة ' . $userName . ' من التذكرة بواسطة ' . Auth::user()->name
        );

        return redirect()->route('client-tickets.show', $clientTicket)
            ->with('success', 'تم إزالة المستخدم من التذكرة بنجاح');
    }

        /**
     * Get team members for mentions
     */
    public function getTeamMembers(ClientTicket $clientTicket)
    {
        $availableUsers = collect();

        // إضافة أعضاء الفريق المعينين
        $assignedUsers = $clientTicket->activeAssignments()
            ->with('user')
            ->get()
            ->pluck('user');

        $availableUsers = $availableUsers->merge($assignedUsers);

        // إضافة منشئ التذكرة
        if ($clientTicket->creator) {
            $availableUsers->push($clientTicket->creator);
        }

        // إزالة التكرارات وتنسيق البيانات
        $teamMembers = $availableUsers->unique('id')->map(function ($user) use ($clientTicket) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar ?? '/avatars/man.gif',
                'role' => $user->id === $clientTicket->created_by ? 'منشئ التذكرة' : 'عضو فريق'
            ];
        })->values(); // إعادة ترقيم المفاتيح

        return response()->json($teamMembers);
    }
}
