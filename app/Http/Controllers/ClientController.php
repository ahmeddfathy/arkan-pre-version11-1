<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CallLog;
use App\Models\ClientTicket;
use App\Services\Auth\RoleCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    protected $roleCheckService;

    public function __construct(RoleCheckService $roleCheckService)
    {
        $this->roleCheckService = $roleCheckService;
    }

    public function index()
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        // تسجيل النشاط - دخول صفحة العملاء
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'action_type' => 'view_index',
                    'page' => 'clients_index',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('دخل على صفحة العملاء');
        }

        $clients = Client::with(['callLogs', 'tickets'])
            ->withCount(['callLogs', 'tickets as open_tickets_count' => function($query) {
                $query->whereIn('client_tickets.status', ['open', 'in_progress']);
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        return view('clients.create');
    }

    public function store(Request $request)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'emails' => 'required|array|min:1',
            'emails.*' => 'required|email|max:255',
            'phones' => 'required|array|min:1',
            'phones.*' => 'required|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $emails = array_filter(array_map('trim', $request->emails));
        $phones = array_filter(array_map('trim', $request->phones));

        // Check for existing client with same name, emails, or phones
        $exists = Client::where('name', $request->name)
            ->orWhere(function($q) use ($emails) {
                foreach ($emails as $email) {
                    $q->orWhereJsonContains('emails', $email);
                }
            })
            ->orWhere(function($q) use ($phones) {
                foreach ($phones as $phone) {
                    $q->orWhereJsonContains('phones', $phone);
                }
            })
            ->first();

        if ($exists) {
            return redirect()->back()
                ->withErrors(['client_exists' => 'هذا العميل موجود بالفعل (بالاسم أو أحد الإيميلات أو الأرقام).'])
                ->withInput();
        }

        Client::create([
            'name' => $request->name,
            'emails' => $emails,
            'phones' => $phones,
            'company_name' => $request->company_name,
            'source' => $request->source,
        ]);

        return redirect()->route('clients.index')->with('success', 'تم إضافة العميل بنجاح');
    }

    public function show(Client $client)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        // تسجيل النشاط - عرض تفاصيل العميل
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->performedOn($client)
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'client_id' => $client->id,
                    'client_name' => $client->name,
                    'client_company' => $client->company_name,
                    'client_source' => $client->source,
                    'action_type' => 'view',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('شاهد تفاصيل العميل');
        }

        $client->load(['projects', 'meetings', 'callLogs.employee', 'tickets.assignedEmployee']);

        // Get recent call logs (last 10)
        $recentCallLogs = $client->callLogs()
            ->with('employee')
            ->orderBy('call_date', 'desc')
            ->limit(10)
            ->get();

        // Get open tickets
        $openTickets = $client->tickets()
            ->with('assignedEmployee')
            ->whereIn('client_tickets.status', ['open', 'in_progress'])
            ->orderBy('client_tickets.created_at', 'desc')
            ->get();

        // Get statistics
        $stats = [
            'total_calls' => $client->callLogs()->count(),
            'successful_calls' => $client->callLogs()->where('outcome', 'successful')->count(),
            'follow_up_needed' => $client->callLogs()->where('outcome', 'follow_up_needed')->count(),
            'total_tickets' => $client->tickets()->count(),
            'open_tickets' => $client->tickets()->whereIn('client_tickets.status', ['open', 'in_progress'])->count(),
            'resolved_tickets' => $client->tickets()->where('client_tickets.status', 'resolved')->count(),
        ];

        return view('clients.show', compact('client', 'recentCallLogs', 'openTickets', 'stats'));
    }

    public function edit(Client $client)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'emails' => 'required|array|min:1',
            'emails.*' => 'required|email|max:255',
            'phones' => 'required|array|min:1',
            'phones.*' => 'required|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'interests' => 'nullable|array',
            'interests.*' => 'string|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $emails = array_filter(array_map('trim', $request->emails));
        $phones = array_filter(array_map('trim', $request->phones));

        // Check for existing client with same data (excluding current client)
        $exists = Client::where('id', '!=', $client->id)
            ->where(function($q) use ($request, $emails, $phones) {
                $q->where('name', $request->name);
                foreach ($emails as $email) {
                    $q->orWhereJsonContains('emails', $email);
                }
                foreach ($phones as $phone) {
                    $q->orWhereJsonContains('phones', $phone);
                }
            })
            ->first();

        if ($exists) {
            return redirect()->back()
                ->withErrors(['client_exists' => 'هذا العميل موجود بالفعل (بالاسم أو أحد الإيميلات أو الأرقام).'])
                ->withInput();
        }

        $client->update([
            'name' => $request->name,
            'emails' => $emails,
            'phones' => $phones,
            'company_name' => $request->company_name,
            'source' => $request->source,
            'interests' => $request->interests ? array_filter($request->interests) : null,
        ]);

        return redirect()->route('clients.show', $client)->with('success', 'تم تحديث بيانات العميل بنجاح');
    }

    public function destroy(Client $client)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        $client->delete();
        return redirect()->route('clients.index')->with('success', 'تم حذف العميل بنجاح');
    }

    /**
     * Add interest to client
     */
    public function addInterest(Request $request, Client $client)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        $request->validate([
            'interest' => 'required|string|max:100'
        ]);

        $client->addInterest($request->interest);

        return redirect()->back()->with('success', 'تم إضافة الاهتمام بنجاح');
    }

    /**
     * Remove interest from client
     */
    public function removeInterest(Request $request, Client $client)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        $request->validate([
            'interest' => 'required|string'
        ]);

        $client->removeInterest($request->interest);

        return redirect()->back()->with('success', 'تم حذف الاهتمام بنجاح');
    }

    /**
     * Client CRM dashboard
     */
    public function crmDashboard()
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        // تسجيل النشاط - دخول لوحة تحكم CRM العملاء
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'action_type' => 'view_dashboard',
                    'page' => 'clients_crm_dashboard',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('دخل على لوحة تحكم CRM العملاء');
        }

        $stats = [
            'total_clients' => Client::count(),
            'total_calls' => CallLog::count(),
            'calls_needing_followup' => CallLog::where('outcome', 'follow_up_needed')->count(),
            'open_tickets' => ClientTicket::whereIn('client_tickets.status', ['open', 'in_progress'])->count(),
            'successful_calls' => CallLog::where('outcome', 'successful')->count(),
            'success_rate' => CallLog::count() > 0 ? (CallLog::where('outcome', 'successful')->count() / CallLog::count()) * 100 : 0,
            'today_calls' => CallLog::whereDate('call_date', today())->count(),
        ];

        $recentClients = Client::orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentCalls = CallLog::with(['client', 'employee'])
            ->orderBy('call_date', 'desc')
            ->limit(5)
            ->get();

        $recentTickets = ClientTicket::with(['project.client', 'assignedEmployee'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('clients.crm-dashboard', compact('stats', 'recentClients', 'recentCalls', 'recentTickets'));
    }

    /**
     * Export clients data
     */
    public function export(Request $request)
    {
        if (!$this->roleCheckService->userHasRole('sales_employee')) {
            abort(403, 'غير مسموح لك بتنفيذ هذا الإجراء. يجب أن تكون موظف مبيعات.');
        }

        // This can be implemented later for CSV/Excel export
        // For now, return JSON
        $clients = Client::with(['callLogs', 'tickets'])->get();

        return response()->json($clients);
    }
}
