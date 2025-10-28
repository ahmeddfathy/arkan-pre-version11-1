<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * عرض بروفايل المستخدم
     */
    public function show(User $user)
    {
        try {
            // جلب المنشورات مع العلاقات
            $posts = $user->posts()
                         ->with(['user:id,name,profile_photo_path'])
                         ->withCount(['likes', 'comments'])
                         ->where('is_active', true)
                         ->latest()
                         ->paginate(10);

            // إحصائيات البروفايل
            $stats = [
                'posts_count' => $user->posts()->where('is_active', true)->count(),
                'followers_count' => $user->followers()->count(),
                'following_count' => $user->following()->count(),
            ];

            // التحقق من المتابعة
            /** @var \App\Models\User $authUser */
            $authUser = Auth::user();
            $isFollowing = $authUser->isFollowing($user);
            $isOwnProfile = Auth::id() === $user->id;

            // جلب بعض المتابعين للعرض
            $recentFollowers = $user->followers()
                                   ->select('users.id', 'users.name', 'users.profile_photo_path')
                                   ->orderBy('followers.followed_at', 'desc')
                                   ->limit(6)
                                   ->get();

            return view('social.profile.show', compact(
                'user',
                'posts',
                'stats',
                'isFollowing',
                'isOwnProfile',
                'recentFollowers'
            ));

        } catch (\Exception $e) {
            Log::error('Error showing user profile: ' . $e->getMessage());
            return redirect()->route('social.index')->with('error', 'حدث خطأ في عرض البروفايل');
        }
    }

    /**
     * متابعة/إلغاء متابعة مستخدم
     */
    public function toggleFollow(Request $request, User $user)
    {
        try {
            /** @var \App\Models\User $currentUser */
            $currentUser = Auth::user();

            // منع المتابعة الذاتية
            if ($currentUser->id === $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكنك متابعة نفسك'
                ], 400);
            }

            $isFollowing = $currentUser->isFollowing($user);

            if ($isFollowing) {
                $currentUser->unfollow($user);
                $action = 'unfollowed';
                $message = 'تم إلغاء المتابعة';
                $buttonText = 'متابعة';
            } else {
                $currentUser->follow($user);
                $action = 'followed';
                $message = 'تم بدء المتابعة';
                $buttonText = 'إلغاء المتابعة';
            }

            // إحصائيات محدثة
            $followersCount = $user->fresh()->followers()->count();

            return response()->json([
                'success' => true,
                'action' => $action,
                'message' => $message,
                'button_text' => $buttonText,
                'followers_count' => $followersCount,
                'is_following' => !$isFollowing
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling follow: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء المتابعة'
            ], 500);
        }
    }

    /**
     * عرض قائمة المتابعين
     */
    public function followers(User $user)
    {
        try {
            $followers = $user->followers()
                             ->select('users.id', 'users.name', 'users.profile_photo_path', 'users.email')
                             ->withPivot('followed_at')
                             ->orderBy('followers.followed_at', 'desc')
                             ->paginate(20);

            return view('social.profile.followers', compact('user', 'followers'));

        } catch (\Exception $e) {
            Log::error('Error showing followers: ' . $e->getMessage());
            return redirect()->back()->with('error', 'حدث خطأ في عرض المتابعين');
        }
    }

    /**
     * عرض قائمة المتابَعين
     */
    public function following(User $user)
    {
        try {
            $following = $user->following()
                             ->select('users.id', 'users.name', 'users.profile_photo_path', 'users.email')
                             ->withPivot('followed_at')
                             ->orderBy('followers.followed_at', 'desc')
                             ->paginate(20);

            return view('social.profile.following', compact('user', 'following'));

        } catch (\Exception $e) {
            Log::error('Error showing following: ' . $e->getMessage());
            return redirect()->back()->with('error', 'حدث خطأ في عرض المتابَعين');
        }
    }

    /**
     * البحث عن المستخدمين
     */
    public function search(Request $request)
    {
        try {
            $query = $request->input('q');

            if (empty($query)) {
                return response()->json([
                    'success' => false,
                    'message' => 'يرجى إدخال اسم للبحث'
                ]);
            }

            $users = User::select('id', 'name', 'profile_photo_path', 'email')
                        ->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('email', 'LIKE', "%{$query}%")
                        ->where('id', '!=', Auth::id())
                        ->limit(10)
                        ->get()
                        ->map(function ($user) {
                            return [
                                'id' => $user->id,
                                'name' => $user->name,
                                'email' => $user->email,
                                'profile_photo_url' => $user->profile_photo_url,
                                'is_following' => (function() use ($user) {
                                    /** @var \App\Models\User $authUser */
                                    $authUser = Auth::user();
                                    return $authUser->isFollowing($user);
                                })(),
                                'profile_url' => route('social.profile.show', $user)
                            ];
                        });

            return response()->json([
                'success' => true,
                'users' => $users
            ]);

        } catch (\Exception $e) {
            Log::error('Error searching users: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء البحث'
            ], 500);
        }
    }

    /**
     * اقتراحات الأشخاص للمتابعة
     */
    public function suggestions()
    {
        try {
            /** @var \App\Models\User $currentUser */
            $currentUser = Auth::user();

            // جلب المستخدمين الذين لم يتم متابعتهم بعد
            $suggestions = User::select('id', 'name', 'profile_photo_path', 'email')
                              ->where('id', '!=', $currentUser->id)
                              ->whereNotIn('id', function($query) use ($currentUser) {
                                  $query->select('following_id')
                                        ->from('followers')
                                        ->where('follower_id', $currentUser->id);
                              })
                              ->withCount(['posts', 'followers'])
                              ->orderBy('followers_count', 'desc')
                              ->limit(5)
                              ->get()
                              ->map(function ($user) {
                                  return [
                                      'id' => $user->id,
                                      'name' => $user->name,
                                      'profile_photo_url' => $user->profile_photo_url,
                                      'posts_count' => $user->posts_count,
                                      'followers_count' => $user->followers_count,
                                      'profile_url' => route('social.profile.show', $user)
                                  ];
                              });

            return response()->json([
                'success' => true,
                'suggestions' => $suggestions
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting suggestions: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في جلب الاقتراحات'
            ], 500);
        }
    }
}
