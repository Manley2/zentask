<?php

namespace App\Http\Controllers;

use App\Models\FriendRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FriendController extends Controller
{
    public function invite(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'note' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $recipient = User::where('email', $validated['email'])->first();

        if (!$recipient || $recipient->id === $user->id) {
            return back()->withErrors(['email' => 'Tidak bisa mengundang diri sendiri.'])->withInput();
        }

        $existing = FriendRequest::where(function ($q) use ($user, $recipient) {
            $q->where('requester_id', $user->id)->where('recipient_id', $recipient->id);
        })->orWhere(function ($q) use ($user, $recipient) {
            $q->where('requester_id', $recipient->id)->where('recipient_id', $user->id);
        })->first();

        if ($existing) {
            if ($existing->status === 'accepted') {
                return back()->with('info', 'Kalian sudah berteman.');
            }

            $existing->update([
                'status' => 'pending',
                'note' => $validated['note'] ?? $existing->note,
            ]);

            return back()->with('success', 'Undangan diperbarui.');
        }

        FriendRequest::create([
            'requester_id' => $user->id,
            'recipient_id' => $recipient->id,
            'note' => $validated['note'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Undangan teman dikirim.');
    }

    public function accept(FriendRequest $friendRequest)
    {
        $user = Auth::user();
        if ($friendRequest->recipient_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $friendRequest->update(['status' => 'accepted']);

        return back()->with('success', 'Undangan diterima.');
    }

    public function decline(FriendRequest $friendRequest)
    {
        $user = Auth::user();
        if ($friendRequest->recipient_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        $friendRequest->update(['status' => 'declined']);

        return back()->with('success', 'Undangan ditolak.');
    }
}
