<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Comment;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * عرض صفحة التواصل الاجتماعي الرئيسية
     */
    public function index()
    {
        $posts = Post::with(['user:id,name,profile_photo_path', 'comments.user:id,name,profile_photo_path'])
                    ->withCount(['likes', 'comments'])
                    ->active()
                    ->public()
                    ->latest()
                    ->paginate(10);

        return view('social.index', compact('posts'));
    }

    /**
     * إنشاء منشور جديد
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'content' => 'required|string|max:5000',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'video' => 'nullable|mimes:mp4,avi,mov,wmv|max:10240',
                'privacy' => 'in:public,friends,private'
            ]);

            $post = new Post();
            $post->user_id = Auth::id();
            $post->content = $request->content;
            $post->privacy = $request->privacy ?? 'public';

            // رفع الصورة
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('posts/images', 'public');
                $post->image = $imagePath;
            }

            // رفع الفيديو
            if ($request->hasFile('video')) {
                $videoPath = $request->file('video')->store('posts/videos', 'public');
                $post->video = $videoPath;
            }

            $post->save();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم نشر المنشور بنجاح',
                    'post' => $post->load('user:id,name,profile_photo_path')
                ]);
            }

            return redirect()->route('social.index')->with('success', 'تم نشر المنشور بنجاح');

        } catch (\Exception $e) {
            Log::error('Error creating post: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء نشر المنشور: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'حدث خطأ أثناء نشر المنشور');
        }
    }

    /**
     * عرض منشور للتعديل
     */
    public function edit(Post $post)
    {
        // التحقق من الصلاحية
        if ($post->user_id !== Auth::id()) {
            abort(403, 'غير مصرح لك بتعديل هذا المنشور');
        }

        return response()->json([
            'success' => true,
            'post' => $post
        ]);
    }

    /**
     * تحديث منشور
     */
    public function update(Request $request, Post $post)
    {
        try {
            // التحقق من الصلاحية
            if ($post->user_id !== Auth::id()) {
                abort(403, 'غير مصرح لك بتعديل هذا المنشور');
            }

            $request->validate([
                'content' => 'required|string|max:5000',
                'privacy' => 'in:public,friends,private'
            ]);

            $post->content = $request->content;
            $post->privacy = $request->privacy ?? $post->privacy;
            $post->save();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم تحديث المنشور بنجاح',
                    'post' => $post
                ]);
            }

            return redirect()->route('social.index')->with('success', 'تم تحديث المنشور بنجاح');

        } catch (\Exception $e) {
            Log::error('Error updating post: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء تحديث المنشور'
                ], 500);
            }

            return redirect()->back()->with('error', 'حدث خطأ أثناء تحديث المنشور');
        }
    }

    /**
     * حذف منشور
     */
    public function destroy(Post $post)
    {
        try {
            // التحقق من الصلاحية - صاحب المنشور أو HR
            if ($post->user_id !== Auth::id() && !Auth::user()->hasRole('hr')) {
                abort(403, 'غير مصرح لك بحذف هذا المنشور');
            }

            // حذف الملفات المرفقة
            if ($post->image) {
                Storage::disk('public')->delete($post->image);
            }
            if ($post->video) {
                Storage::disk('public')->delete($post->video);
            }

            $post->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المنشور بنجاح'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting post: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف المنشور'
            ], 500);
        }
    }

    /**
     * إضافة/إزالة إعجاب
     */
    public function toggleLike(Request $request, Post $post)
    {
        try {
            $type = $request->input('type', 'like');
            $result = $post->toggleLike(Auth::user(), $type);

            // احصائيات الإعجابات لعرضها
            $reactionsSummary = $post->likes()
                ->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->orderBy('count', 'desc')
                ->limit(3)
                ->pluck('count', 'type')
                ->toArray();

            return response()->json([
                'success' => true,
                'result' => $result,
                'likes_count' => $post->fresh()->likes_count,
                'user_liked' => $post->isLikedBy(Auth::user()),
                'user_like_type' => $post->getLikeTypeBy(Auth::user()),
                'reactions_summary' => $reactionsSummary
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling like: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الإعجاب'
            ], 500);
        }
    }

    /**
     * إضافة تعليق
     */
    public function addComment(Request $request, Post $post)
    {
        try {
            $request->validate([
                'content' => 'required|string|max:1000',
                'parent_id' => 'nullable|exists:comments,id',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:1024'
            ]);

            $comment = new Comment();
            $comment->user_id = Auth::id();
            $comment->post_id = $post->id;
            $comment->parent_id = $request->parent_id;
            $comment->content = $request->content;

            // رفع صورة التعليق
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('comments/images', 'public');
                $comment->image = $imagePath;
            }

            $comment->save();
            $comment->load('user:id,name,profile_photo_path');

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة التعليق بنجاح',
                'comment' => $comment,
                'comments_count' => $post->fresh()->comments_count
            ]);

        } catch (\Exception $e) {
            Log::error('Error adding comment: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة التعليق'
            ], 500);
        }
    }

    /**
     * حذف تعليق
     */
    public function deleteComment(Comment $comment)
    {
        try {
            // التحقق من الصلاحية - صاحب التعليق أو HR
            if ($comment->user_id !== Auth::id() && !Auth::user()->hasRole('hr')) {
                abort(403, 'غير مصرح لك بحذف هذا التعليق');
            }

            // حذف صورة التعليق إن وجدت
            if ($comment->image) {
                Storage::disk('public')->delete($comment->image);
            }

            $post = $comment->post;
            $comment->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف التعليق بنجاح',
                'comments_count' => $post->fresh()->comments_count
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting comment: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف التعليق'
            ], 500);
        }
    }

    /**
     * إضافة/إزالة إعجاب التعليق
     */
    public function toggleCommentLike(Request $request, Comment $comment)
    {
        try {
            $type = $request->input('type', 'like');
            $result = $comment->toggleLike(Auth::user(), $type);

            return response()->json([
                'success' => true,
                'result' => $result,
                'likes_count' => $comment->fresh()->likes_count,
                'user_liked' => $comment->isLikedBy(Auth::user()),
                'user_like_type' => $comment->getLikeTypeBy(Auth::user())
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling comment like: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الإعجاب'
            ], 500);
        }
    }

    /**
     * الحصول على التعليقات
     */
    public function getComments(Post $post)
    {
        try {
            $comments = $post->comments()
                            ->with(['user:id,name,profile_photo_path', 'replies.user:id,name,profile_photo_path'])
                            ->withCount('likes')
                            ->get();

            return response()->json([
                'success' => true,
                'comments' => $comments
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting comments: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب التعليقات'
            ], 500);
        }
    }

    /**
     * البحث في المنشورات
     */
    public function search(Request $request)
    {
        try {
            $query = $request->input('q');

            $posts = Post::with(['user:id,name,profile_photo_path'])
                        ->withCount(['likes', 'comments'])
                        ->where('content', 'LIKE', "%{$query}%")
                        ->active()
                        ->public()
                        ->latest()
                        ->paginate(10);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'posts' => $posts
                ]);
            }

            return view('social.index', compact('posts', 'query'));

        } catch (\Exception $e) {
            Log::error('Error searching posts: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء البحث'
                ], 500);
            }

            return redirect()->back()->with('error', 'حدث خطأ أثناء البحث');
        }
    }
}
