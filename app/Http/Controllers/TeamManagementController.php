<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Jetstream\Jetstream;

class TeamManagementController extends Controller
{
    /**
     * عرض جميع الفرق
     */
    public function index()
    {
        // فقط HR يمكنه رؤية جميع الفرق
        if (!auth()->user()->hasRole('hr')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $teams = Team::with(['owner', 'users'])
            ->orderBy('name')
            ->get();

        return view('teams.index', compact('teams'));
    }

    /**
     * عرض صفحة إنشاء فريق جديد
     */
    public function create()
    {
        // فقط HR يمكنه إنشاء فرق
        if (!auth()->user()->hasRole('hr')) {
            abort(403, 'غير مصرح لك بإنشاء فرق');
        }

        // جلب جميع المستخدمين النشطين لاختيار مالك الفريق
        $users = User::where('employee_status', 'active')
            ->orderBy('name')
            ->get();

        return view('teams.create', compact('users'));
    }

    /**
     * حفظ فريق جديد
     */
    public function store(Request $request)
    {
        // فقط HR يمكنه إنشاء فرق
        if (!auth()->user()->hasRole('hr')) {
            abort(403, 'غير مصرح لك بإنشاء فرق');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'user_id' => 'required|exists:users,id',
        ]);

        $owner = User::findOrFail($request->user_id);

        // إنشاء الفريق
        $team = $owner->ownedTeams()->create([
            'name' => $request->name,
            'personal_team' => false,
        ]);

        // إضافة المالك كعضو في الفريق
        $team->users()->attach($owner->id, [
            'role' => 'admin',
        ]);

        return redirect()->route('admin.teams.index')
            ->with('success', 'تم إنشاء الفريق بنجاح');
    }

    /**
     * عرض تفاصيل فريق معين
     */
    public function show(Team $team)
    {
        // التحقق من الصلاحيات
        if (!auth()->user()->hasRole('hr') && !auth()->user()->belongsToTeam($team)) {
            abort(403, 'غير مصرح لك بالوصول إلى هذا الفريق');
        }

        $team->load(['owner', 'users', 'teamInvitations']);

        // إزالة المستخدمين المحذوفين (null) من المجموعة
        $team->users = $team->users->filter(function($user) {
            return $user !== null;
        });

        // جلب جميع المستخدمين المتاحين لإضافتهم للفريق (النشطين فقط)
        $availableUsers = User::whereNotIn('id', $team->users->pluck('id'))
            ->where('employee_status', 'active')
            ->orderBy('name')
            ->get();

        // جلب الأدوار المتاحة من Jetstream
        $roles = [];
        if (Jetstream::hasRoles()) {
            // الحصول على الأدوار من Jetstream
            $roles = collect(Jetstream::$roles ?? [])->map(function ($role, $key) {
                return [
                    'key' => $key,
                    'name' => $role->name ?? $key,
                    'description' => $role->description ?? '',
                ];
            })->values()->all();
        } else {
            // إذا لم تكن هناك أدوار، نستخدم الأدوار الافتراضية
            $roles = [
                ['key' => 'editor', 'name' => 'Member', 'description' => 'Member users have the ability to read, create, and update.'],
            ];
        }

        return view('teams.manage', compact('team', 'availableUsers', 'roles'));
    }

    /**
     * إضافة عضو إلى الفريق
     */
    public function addMember(Request $request, Team $team)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('addTeamMember', $team) && !auth()->user()->hasRole('hr')) {
            abort(403, 'غير مصرح لك بإضافة أعضاء لهذا الفريق');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'nullable|string',
        ]);

        $user = User::findOrFail($request->user_id);

        // التحقق من أن المستخدم ليس في الفريق بالفعل
        if ($team->users->contains($user->id)) {
            return back()->withErrors(['user_id' => 'هذا المستخدم موجود بالفعل في الفريق']);
        }

        // إضافة المستخدم للفريق
        $team->users()->attach($user->id, [
            'role' => $request->role ?? 'editor',
        ]);

        return back()->with('success', 'تم إضافة العضو بنجاح');
    }

    /**
     * إزالة عضو من الفريق
     */
    public function removeMember(Request $request, Team $team, User $user)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('removeTeamMember', $team) && !auth()->user()->hasRole('hr')) {
            abort(403, 'غير مصرح لك بإزالة أعضاء من هذا الفريق');
        }

        // منع إزالة مالك الفريق
        if ($team->user_id === $user->id) {
            return back()->withErrors(['error' => 'لا يمكن إزالة مالك الفريق']);
        }

        // إزالة المستخدم من الفريق
        $team->users()->detach($user->id);

        return back()->with('success', 'تم إزالة العضو بنجاح');
    }

    /**
     * نقل ملكية الفريق
     */
    public function transferOwnership(Request $request, Team $team)
    {
        // التحقق من الصلاحيات
        if (!Gate::allows('transferTeamOwnership', $team) && !auth()->user()->hasRole('hr')) {
            abort(403, 'غير مصرح لك بنقل ملكية هذا الفريق');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $newOwner = User::findOrFail($request->user_id);

        // التحقق من أن المستخدم الجديد في الفريق
        if (!$team->users->contains($newOwner->id)) {
            // إضافة المستخدم الجديد للفريق أولاً
            $team->users()->attach($newOwner->id, [
                'role' => 'owner',
            ]);
        }

        // نقل الملكية
        $team->transferOwnership($newOwner);

        // إعادة تحميل العلاقات
        $team->load('owner');

        return back()->with('success', 'تم نقل ملكية الفريق بنجاح');
    }
}

