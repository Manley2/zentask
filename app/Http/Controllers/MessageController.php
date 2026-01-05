<?php

namespace App\Http\Controllers;

use App\Models\FriendRequest;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $friends = $this->getFriends($user->id);

        $activeFriendId = $request->query('friend');
        $activeFriend = $friends->firstWhere('id', (int) $activeFriendId) ?? $friends->first();

        $messages = collect();
        if ($activeFriend) {
            $messages = Message::withTrashed()
                ->where(function ($q) use ($user, $activeFriend) {
                    $q->where('sender_id', $user->id)->where('receiver_id', $activeFriend->id);
                })->orWhere(function ($q) use ($user, $activeFriend) {
                    $q->where('sender_id', $activeFriend->id)->where('receiver_id', $user->id);
            })->orderBy('created_at', 'asc')->get();

            // Mark as read
            Message::where('sender_id', $activeFriend->id)
                ->where('receiver_id', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);
        }

        $incomingInvites = FriendRequest::where('recipient_id', $user->id)
            ->where('status', 'pending')
            ->with('requester')
            ->get();

        $sentInvites = FriendRequest::where('requester_id', $user->id)
            ->where('status', 'pending')
            ->with('recipient')
            ->get();

        return view('messages.index', compact(
            'friends',
            'activeFriend',
            'messages',
            'incomingInvites',
            'sentInvites'
        ));
    }

    public function send(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'friend_id' => 'required|integer|exists:users,id',
            'body' => 'required|string|max:2000',
        ]);

        $friend = User::findOrFail($validated['friend_id']);
        if (!$user->isFriendWith($friend)) {
            return back()->withErrors(['body' => 'Kamu hanya bisa mengirim pesan ke teman yang sudah terhubung.']);
        }

        Message::create([
            'sender_id' => $user->id,
            'receiver_id' => $friend->id,
            'body' => $validated['body'],
        ]);

        return back()->with('success', 'Pesan terkirim.');
    }

    public function update(Request $request, Message $message)
    {
        $user = Auth::user();
        if ($message->sender_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        if (!$this->canModify($message)) {
            return back()->withErrors(['body' => 'This message is locked and cannot be edited.']);
        }

        $validated = $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $message->update(['body' => $validated['body']]);

        return back()->with('success', 'Message updated.');
    }

    public function recall(Message $message)
    {
        $user = Auth::user();
        if ($message->sender_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        if (!$this->canModify($message)) {
            return back()->withErrors(['body' => 'This message is locked and cannot be recalled.']);
        }

        $message->delete(); // soft delete

        return back()->with('success', 'Message recalled for everyone.');
    }

    private function getFriends(int $userId)
    {
        $accepted = FriendRequest::where(function ($q) use ($userId) {
            $q->where('requester_id', $userId)
              ->orWhere('recipient_id', $userId);
        })->where('status', 'accepted')->get();

        $friendIds = $accepted->map(function ($fr) use ($userId) {
            return $fr->requester_id === $userId ? $fr->recipient_id : $fr->requester_id;
        })->unique()->values();

        return User::whereIn('id', $friendIds)->get();
    }

    private function canModify(Message $message): bool
    {
        if ($message->trashed()) {
            return false;
        }
        $minutes = now()->diffInMinutes($message->created_at);
        return $minutes < 15;
    }
}
