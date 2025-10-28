<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;


class  BroadNotificationController extends Controller
{
    public function create(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string',
            'data' => 'required|array',
            'related_id' => 'nullable|integer',
        ]);

        $notification = Notification::create($validated);

        return response()->json(['message' => 'Notification created successfully!', 'notification' => $notification]);
    }

    public function index()
    {
        $notifications = Notification::with('user')->get();

        return response()->json($notifications);
    }

    public function markAsRead(Notification $notification)
    {
        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read!', 'notification' => $notification]);
    }

    public function delete(Notification $notification)
    {
        $notification->delete();

        return response()->json(['message' => 'Notification deleted successfully!']);
    }

    public function notifyAll(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'data' => 'required|array',
            'related_id' => 'nullable|integer',
        ]);

        $users = User::all();

        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type' => $validated['type'],
                'data' => $validated['data'],
                'related_id' => $validated['related_id'] ?? null,
            ]);
        }

        return response()->json(['message' => 'Notification sent to all users!']);
    }
}
