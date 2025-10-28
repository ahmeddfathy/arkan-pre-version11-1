<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    protected $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function index()
    {
        $user = Auth::user();
        $chats = $this->chatService->getUserChats($user);

        // Get all users except the current user
        $users = User::where('id', '!=', $user->id)->get();

        // Get the manager for backwards compatibility
        $manager = User::where('role', 'manager')->first();

        return view('chat.index', compact('chats', 'manager', 'users'));
    }

    public function getMessages(User $receiver)
    {
        $sender = Auth::user();

        $messages = Message::where(function($query) use ($sender, $receiver) {
            $query->where('sender_id', $sender->id)
                  ->where('receiver_id', $receiver->id);
        })->orWhere(function($query) use ($sender, $receiver) {
            $query->where('sender_id', $receiver->id)
                  ->where('receiver_id', $sender->id);
        })->orderBy('created_at', 'asc')
          ->with(['sender', 'receiver'])
          ->get();

        $this->chatService->markMessagesAsSeen($receiver, $sender);

        return response()->json($messages);
    }

    public function getNewMessages(Request $request, User $receiver)
    {
        $sender = Auth::user();
        $lastTimestamp = $request->query('after');

        $query = Message::where(function($query) use ($sender, $receiver) {
            $query->where('sender_id', $sender->id)
                  ->where('receiver_id', $receiver->id);
        })->orWhere(function($query) use ($sender, $receiver) {
            $query->where('sender_id', $receiver->id)
                  ->where('receiver_id', $sender->id);
        });

        if ($lastTimestamp) {
            // Convert timestamp to proper MySQL datetime format
            $date = date('Y-m-d H:i:s.u', $lastTimestamp / 1000);
            $query->where('created_at', '>', $date);
        }

        $messages = $query->orderBy('created_at', 'asc')
            ->with(['sender', 'receiver'])
            ->get();

        // Mark messages as seen only if there are new ones
        if ($messages->count() > 0) {
            $this->chatService->markMessagesAsSeen($receiver, $sender);
        }

        return response()->json($messages);
    }

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content' => 'required|string'
        ]);

        $message = new Message();
        $message->sender_id = Auth::id();
        $message->receiver_id = $validated['receiver_id'];
        $message->content = $validated['content'];
        $message->is_seen = false;
        $message->save();

        // Load relationships
        $message->load(['sender', 'receiver']);

        return response()->json($message);
    }

    public function markAsSeen(Request $request)
    {
        $request->validate([
            'sender_id' => 'required|exists:users,id'
        ]);

        $receiver = Auth::user();
        $sender = User::findOrFail($request->sender_id);

        $count = $this->chatService->markMessagesAsSeen($sender, $receiver);

        return response()->json(['success' => true, 'count' => $count]);
    }

    public function getUserStatus(User $user)
    {
        return response()->json([
            'is_online' => $user->is_online ?? false,
            'last_seen_at' => $user->last_seen_at ?? now()
        ]);
    }
}
