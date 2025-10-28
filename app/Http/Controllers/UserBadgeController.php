<?php

namespace App\Http\Controllers;

use App\Models\UserBadge;
use App\Models\Badge;
use App\Models\User;
use App\Models\Season;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class UserBadgeController extends Controller
{

    public function index(Request $request)
    {
        $query = UserBadge::with(['user', 'badge', 'season']);


        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }


        if ($request->has('badge_id') && $request->badge_id) {
            $query->where('badge_id', $request->badge_id);
        }


        if ($request->has('season_id') && $request->season_id) {
            $query->where('season_id', $request->season_id);
        }


        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $userBadges = $query->orderBy('earned_at', 'desc')->paginate(15);


        $users = User::orderBy('name')->get();
        $badges = Badge::orderBy('level')->get();
        $seasons = Season::orderBy('created_at', 'desc')->get();

        return view('user-badges.index', compact('userBadges', 'users', 'badges', 'seasons'));
    }


    public function create()
    {
        $users = User::orderBy('name')->get();
        $badges = Badge::orderBy('level')->get();
        $seasons = Season::orderBy('created_at', 'desc')->get();

        return view('user-badges.create', compact('users', 'badges', 'seasons'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'badge_id' => 'required|exists:badges,id',
            'season_id' => 'required|exists:seasons,id',
            'points_earned' => 'required|integer|min:0',
            'earned_at' => 'required|date',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);


        $existingBadge = UserBadge::where('user_id', $request->user_id)
                                 ->where('badge_id', $request->badge_id)
                                 ->where('season_id', $request->season_id)
                                 ->first();

        if ($existingBadge) {
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'المستخدم لديه هذه الشارة بالفعل في هذا الموسم');
        }


        if ($request->has('is_active') && $request->is_active) {
            UserBadge::where('user_id', $request->user_id)
                     ->where('season_id', $request->season_id)
                     ->update(['is_active' => false]);
        }

        UserBadge::create([
            'user_id' => $request->user_id,
            'badge_id' => $request->badge_id,
            'season_id' => $request->season_id,
            'points_earned' => $request->points_earned,
            'earned_at' => Carbon::parse($request->earned_at),
            'is_active' => $request->has('is_active'),
            'notes' => $request->notes,
        ]);

        return redirect()->route('user-badges.index')
                        ->with('success', 'تم منح الشارة للمستخدم بنجاح');
    }


    public function show(UserBadge $userBadge)
    {
        $userBadge->load(['user', 'badge', 'season']);

        return view('user-badges.show', compact('userBadge'));
    }


    public function edit(UserBadge $userBadge)
    {
        $users = User::orderBy('name')->get();
        $badges = Badge::orderBy('level')->get();
        $seasons = Season::orderBy('created_at', 'desc')->get();

        return view('user-badges.edit', compact('userBadge', 'users', 'badges', 'seasons'));
    }


    public function update(Request $request, UserBadge $userBadge)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'badge_id' => 'required|exists:badges,id',
            'season_id' => 'required|exists:seasons,id',
            'points_earned' => 'required|integer|min:0',
            'earned_at' => 'required|date',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);


        $existingBadge = UserBadge::where('user_id', $request->user_id)
                                 ->where('badge_id', $request->badge_id)
                                 ->where('season_id', $request->season_id)
                                 ->where('id', '!=', $userBadge->id)
                                 ->first();

        if ($existingBadge) {
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'المستخدم لديه هذه الشارة بالفعل في هذا الموسم');
        }


        if ($request->has('is_active') && $request->is_active) {
            UserBadge::where('user_id', $request->user_id)
                     ->where('season_id', $request->season_id)
                     ->where('id', '!=', $userBadge->id)
                     ->update(['is_active' => false]);
        }

        $userBadge->update([
            'user_id' => $request->user_id,
            'badge_id' => $request->badge_id,
            'season_id' => $request->season_id,
            'points_earned' => $request->points_earned,
            'earned_at' => Carbon::parse($request->earned_at),
            'is_active' => $request->has('is_active'),
            'notes' => $request->notes,
        ]);

        return redirect()->route('user-badges.index')
                        ->with('success', 'تم تحديث شارة المستخدم بنجاح');
    }


    public function destroy(UserBadge $userBadge)
    {
        $userBadge->delete();

        return redirect()->route('user-badges.index')
                        ->with('success', 'تم حذف شارة المستخدم بنجاح');
    }


    public function toggleActive(UserBadge $userBadge)
    {

        if (!$userBadge->is_active) {
            UserBadge::where('user_id', $userBadge->user_id)
                     ->where('season_id', $userBadge->season_id)
                     ->where('id', '!=', $userBadge->id)
                     ->update(['is_active' => false]);
        }

        $userBadge->update(['is_active' => !$userBadge->is_active]);

        $status = $userBadge->is_active ? 'تم تفعيل' : 'تم إلغاء تفعيل';

        return redirect()->back()
                        ->with('success', $status . ' الشارة بنجاح');
    }


    public function userBadges(User $user)
    {
        $userBadges = UserBadge::where('user_id', $user->id)
                              ->with(['badge', 'season'])
                              ->orderBy('season_id', 'desc')
                              ->orderBy('earned_at', 'desc')
                              ->get()
                              ->groupBy('season_id');

        return view('user-badges.user-badges', compact('user', 'userBadges'));
    }


    public function badgeUsers(Badge $badge)
    {
        $userBadges = UserBadge::where('badge_id', $badge->id)
                              ->with(['user', 'season'])
                              ->orderBy('season_id', 'desc')
                              ->orderBy('earned_at', 'desc')
                              ->paginate(15);

        return view('user-badges.badge-users', compact('badge', 'userBadges'));
    }

                
    public function statistics()
    {
        $stats = [
            'total_badges_earned' => UserBadge::count(),
            'active_badges' => UserBadge::where('is_active', true)->count(),
            'users_with_badges' => UserBadge::distinct('user_id')->count(),
            'most_earned_badge' => UserBadge::select('badge_id')
                                           ->groupBy('badge_id')
                                           ->orderByRaw('count(*) DESC')
                                           ->with('badge')
                                           ->first(),
        ];

        return view('user-badges.statistics', compact('stats'));
    }
}
