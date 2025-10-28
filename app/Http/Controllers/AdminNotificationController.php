<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use App\Models\AdministrativeDecision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminNotificationController extends Controller
{
    public function index(Request $request)
    {
        // تسجيل النشاط - دخول صفحة الإشعارات الإدارية
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'action_type' => 'view_index',
                    'page' => 'admin_notifications_index',
                    'filters' => $request->all(),
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('دخل على صفحة الإشعارات الإدارية');
        }

        $query = Notification::where('type', 'admin_broadcast')
            ->orWhere('type', 'administrative_decision');

        // تطبيق الفلتر
        if ($request->filled('read_status')) {
            if ($request->read_status === 'read') {
                $query->whereHas('administrativeDecisions', function ($q) {
                    $q->whereNotNull('acknowledged_at');
                });
            } elseif ($request->read_status === 'unread') {
                $query->whereHas('administrativeDecisions', function ($q) {
                    $q->whereNull('acknowledged_at');
                });
            }
        }

        // فلتر نوع الإشعار
        if ($request->filled('type')) {
            $query = Notification::query(); // إعادة تعيين الاستعلام
            if ($request->type === 'administrative_decision') {
                $query->where('type', 'administrative_decision');
            } elseif ($request->type === 'admin_broadcast') {
                $query->where('type', 'admin_broadcast');
            }
        }

        // نسخة من الاستعلام للإحصائيات
        $statsQuery = clone $query;

        // إحصائيات عامة
        $totalNotifications = $statsQuery->count();

        $readNotifications = (clone $statsQuery)
            ->whereHas('administrativeDecisions', function ($q) {
                $q->whereNotNull('acknowledged_at');
            })->count();

        $unreadNotifications = (clone $statsQuery)
            ->whereHas('administrativeDecisions', function ($q) {
                $q->whereNull('acknowledged_at');
            })->count();

        // جلب الإشعارات مع إحصائيات القراءة
        $notifications = $query
            ->withCount(['administrativeDecisions as total_recipients'])
            ->withCount(['administrativeDecisions as read_count' => function ($query) {
                $query->whereNotNull('acknowledged_at');
            }])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.notifications.index', compact(
            'notifications',
            'totalNotifications',
            'readNotifications',
            'unreadNotifications'
        ));
    }

    public function create()
    {
        return view('admin.notifications.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'is_administrative' => 'required|boolean',
            'requires_acknowledgment' => 'required|boolean',
        ]);

        // إنشاء الإشعار
        $notification = Notification::create([
            'user_id' => Auth::id(),
            'type' => $request->is_administrative ? 'administrative_decision' : 'admin_broadcast',
            'data' => [
                'title' => $request->title,
                'message' => $request->message,
                'sender_name' => Auth::user()->name,
                'requires_acknowledgment' => $request->requires_acknowledgment,
                'created_at' => now()
            ],
            'read_at' => null
        ]);

        // إنشاء سجلات للمستخدمين مباشرة (باستثناء منشئ الإشعار)
        $users = User::where('id', '!=', Auth::id())->get();
        $administrativeDecisions = $users->map(function ($user) use ($notification) {
            return [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now()
            ];
        })->toArray();


        AdministrativeDecision::insert($administrativeDecisions);

        return redirect()->route('admin.notifications.index')
            ->with('success', 'تم إرسال الإشعار بنجاح');
    }

    public function edit(Notification $notification)
    {
        return view('admin.notifications.edit', compact('notification'));
    }

    public function update(Request $request, Notification $notification)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'is_administrative' => 'required|boolean',
            'requires_acknowledgment' => 'required|boolean',
        ]);


        $notification->update([
            'type' => $request->is_administrative ? 'administrative_decision' : 'admin_broadcast',
            'data' => [
                'title' => $request->title,
                'message' => $request->message,
                'sender_name' => Auth::user()->name,
                'requires_acknowledgment' => $request->requires_acknowledgment,
                'updated_at' => now()
            ]
        ]);


        $existingUserIds = $notification->administrativeDecisions()
            ->pluck('user_id')
            ->toArray();

        $newUsers = User::whereNotIn('id', $existingUserIds)
            ->where('id', '!=', Auth::id())
            ->get();


        if ($newUsers->isNotEmpty()) {
            $newDecisions = $newUsers->map(function ($user) use ($notification) {
                return [
                    'notification_id' => $notification->id,
                    'user_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            })->toArray();

            AdministrativeDecision::insert($newDecisions);
        }

        return redirect()->route('admin.notifications.index')
            ->with('success', 'تم تحديث الإشعار بنجاح');
    }

    public function destroy(Notification $notification)
    {
        $notification->delete();

        return redirect()->route('admin.notifications.index')
            ->with('success', 'تم حذف الإشعار بنجاح');
    }

    public function show(Notification $notification)
    {
        // تسجيل النشاط - عرض تفاصيل الإشعار الإداري
        if (\Illuminate\Support\Facades\Auth::check()) {
            activity()
                ->performedOn($notification)
                ->causedBy(\Illuminate\Support\Facades\Auth::user())
                ->withProperties([
                    'notification_id' => $notification->id,
                    'notification_type' => $notification->type,
                    'action_type' => 'view',
                    'viewed_at' => now()->toDateTimeString(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip()
                ])
                ->log('شاهد تفاصيل الإشعار الإداري');
        }

        $recipients = User::leftJoin('administrative_decisions', function ($join) use ($notification) {
            $join->on('users.id', '=', 'administrative_decisions.user_id')
                ->where('administrative_decisions.notification_id', '=', $notification->id);
        })
            ->select([
                'users.*',
                'administrative_decisions.acknowledged_at as read_at'
            ])
            ->get()
            ->map(function ($user) {

                if ($user->read_at) {
                    $user->read_at = \Carbon\Carbon::parse($user->read_at);
                }
                return $user;
            });

        $readCount = $recipients->whereNotNull('read_at')->count();
        $totalRecipients = $recipients->count();
        $unreadCount = $totalRecipients - $readCount;

        return view('admin.notifications.show', compact(
            'notification',
            'recipients',
            'readCount',
            'unreadCount',
            'totalRecipients'
        ));
    }
}
