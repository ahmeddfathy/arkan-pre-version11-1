<?php

namespace App\Http\Controllers;

use App\Models\CallLog;
use App\Models\Client;
use App\Models\User;
use App\Services\Auth\RoleCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CallLogController extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $roleCheckService;

    public function __construct(RoleCheckService $roleCheckService)
    {
        $this->roleCheckService = $roleCheckService;
    }

    /**
     * Display a listing of call logs.
     */
    public function index(Request $request)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        // تسجيل النشاط - دخول صفحة سجلات المكالمات
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'action_type' => 'view_index',
                    'page' => 'call_logs_index',
                    'filters' => $request->all(),
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('دخل على صفحة سجلات المكالمات');
        }

        $query = CallLog::with(['client', 'employee']);

        // Filter by client if specified
        if ($request->has('client_id') && $request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        // Filter by employee if specified
        if ($request->has('employee_id') && $request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by contact type
        if ($request->has('contact_type') && $request->contact_type) {
            $query->where('contact_type', $request->contact_type);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('call_date', '>=', $request->date_from);
        }
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('call_date', '<=', $request->date_to);
        }

        // Search in outcome text
        if ($request->has('outcome') && $request->outcome) {
            $query->where('outcome', 'like', '%' . $request->outcome . '%');
        }

        $callLogs = $query->orderBy('call_date', 'desc')->paginate(15);

        $clients = Client::orderBy('name')->get();
        $employees = User::orderBy('name')->get();

        return view('call-logs.index', compact('callLogs', 'clients', 'employees'));
    }

    /**
     * Show the form for creating a new call log.
     */
    public function create(Request $request)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        $clients = Client::orderBy('name')->get();
        $selectedClient = null;

        if ($request->has('client_id')) {
            $selectedClient = Client::find($request->client_id);
        }

        return view('call-logs.create', compact('clients', 'selectedClient'));
    }

    /**
     * Store a newly created call log.
     */
    public function store(Request $request)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'call_date' => 'required|date',
            'contact_type' => 'required|in:call,email,whatsapp,meeting,other',
            'call_summary' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'duration_minutes' => 'nullable|integer|min:1|max:600',
            'outcome' => 'nullable|string|max:500',
            'status' => 'required|in:successful,failed,needs_followup'
        ]);

        $validated['employee_id'] = Auth::id();
        $validated['created_by'] = Auth::id();

        CallLog::create($validated);

        return redirect()->route('call-logs.index')
            ->with('success', 'تم حفظ سجل المكالمة بنجاح');
    }

    /**
     * Display the specified call log.
     */
    public function show(CallLog $callLog)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        // تسجيل النشاط - عرض تفاصيل سجل المكالمة
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->performedOn($callLog)
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'call_log_id' => $callLog->id,
                    'client_id' => $callLog->client_id,
                    'client_name' => $callLog->client->name ?? null,
                    'contact_type' => $callLog->contact_type,
                    'call_date' => $callLog->call_date,
                    'action_type' => 'view',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('شاهد تفاصيل سجل المكالمة');
        }

        $callLog->load(['client', 'employee', 'creator']);

        return view('call-logs.show', compact('callLog'));
    }

    /**
     * Show the form for editing the specified call log.
     */
    public function edit(CallLog $callLog)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        $clients = Client::orderBy('name')->get();

        return view('call-logs.edit', compact('callLog', 'clients'));
    }

    /**
     * Update the specified call log.
     */
    public function update(Request $request, CallLog $callLog)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'call_date' => 'required|date',
            'contact_type' => 'required|in:call,email,whatsapp,meeting,other',
            'call_summary' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'duration_minutes' => 'nullable|integer|min:1|max:600',
            'outcome' => 'nullable|string|max:500',
            'status' => 'required|in:successful,failed,needs_followup'
        ]);

        $callLog->update($validated);

        return redirect()->route('call-logs.show', $callLog)
            ->with('success', 'تم تحديث سجل المكالمة بنجاح');
    }

    /**
     * Remove the specified call log.
     */
    public function destroy(CallLog $callLog)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        $callLog->delete();

        return redirect()->route('call-logs.index')
            ->with('success', 'تم حذف سجل المكالمة بنجاح');
    }




}
