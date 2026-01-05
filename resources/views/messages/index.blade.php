@extends('layouts.dashboard-layout')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-3xl lg:text-4xl font-bold text-white">Messages</h1>
        <p class="text-blue-100/70 mt-1">Invite friends, connect, and send messages.</p>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-green-500/30 bg-green-500/10 text-green-100 px-4 py-3">
            {{ session('success') }}
        </div>
    @endif
    @if(session('info'))
        <div class="rounded-xl border border-blue-500/30 bg-blue-500/10 text-blue-100 px-4 py-3">
            {{ session('info') }}
        </div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-red-500/30 bg-red-500/10 text-red-100 px-4 py-3">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Invite friend --}}
    <div class="glass-card rounded-2xl p-6 border border-white/10 space-y-4">
        <h2 class="text-xl font-semibold text-white">Invite a Friend</h2>
        <form action="{{ route('friends.invite') }}" method="POST" class="grid gap-4 md:grid-cols-[1.2fr_1fr_auto] items-end">
            @csrf
            <div>
                <label class="block text-sm text-blue-100/70 mb-1">Friend email</label>
                <input type="email" name="email" required placeholder="friend@email.com"
                       value="{{ old('email') }}"
                       class="w-full rounded-xl bg-white/5 border border-white/10 text-white placeholder:text-blue-100/40 focus:ring-0 focus:border-blue-300/40">
            </div>
            <div>
                <label class="block text-sm text-blue-100/70 mb-1">Note (optional)</label>
                <input type="text" name="note" placeholder="Let’s collaborate!"
                       value="{{ old('note') }}"
                       class="w-full rounded-xl bg-white/5 border border-white/10 text-white placeholder:text-blue-100/40 focus:ring-0 focus:border-blue-300/40">
            </div>
            <button type="submit"
                    class="px-5 py-3 rounded-xl bg-blue-500 hover:bg-blue-600 text-white font-semibold transition shadow-lg shadow-blue-500/20">
                Send Invite
            </button>
        </form>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-yellow-400/20 bg-yellow-400/5 p-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-yellow-200">Pending Invites</h3>
                    <span class="text-xs text-yellow-200/70">{{ $incomingInvites->count() }}</span>
                </div>
                @forelse($incomingInvites as $invite)
                    <div class="flex items-center justify-between py-2 border-t border-white/5 first:border-t-0">
                        <div>
                            <p class="text-white font-semibold">{{ $invite->requester->name ?? 'User' }}</p>
                            <p class="text-xs text-blue-100/70">{{ $invite->requester->email ?? '' }}</p>
                            @if($invite->note)
                                <p class="text-xs text-blue-100/60 mt-1">“{{ $invite->note }}”</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('friends.accept', $invite) }}">
                                @csrf
                                <button class="px-3 py-1.5 rounded-lg bg-green-500/20 border border-green-500/40 text-green-100 text-xs font-semibold hover:bg-green-500/30">
                                    Accept
                                </button>
                            </form>
                            <form method="POST" action="{{ route('friends.decline', $invite) }}">
                                @csrf
                                <button class="px-3 py-1.5 rounded-lg bg-red-500/10 border border-red-500/30 text-red-100 text-xs font-semibold hover:bg-red-500/20">
                                    Decline
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-blue-100/70">No new invites.</p>
                @endforelse
            </div>

            <div class="rounded-xl border border-blue-400/20 bg-blue-400/5 p-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-blue-200">Invites Sent</h3>
                    <span class="text-xs text-blue-200/70">{{ $sentInvites->count() }}</span>
                </div>
                @forelse($sentInvites as $invite)
                    <div class="py-2 border-t border-white/5 first:border-t-0">
                        <p class="text-white font-semibold">{{ $invite->recipient->name ?? 'User' }}</p>
                        <p class="text-xs text-blue-100/70">{{ $invite->recipient->email ?? '' }}</p>
                        @if($invite->note)
                            <p class="text-xs text-blue-100/60 mt-1">“{{ $invite->note }}”</p>
                        @endif
                        <p class="text-xs text-blue-100/50 mt-1">Status: {{ ucfirst($invite->status) }}</p>
                    </div>
                @empty
                    <p class="text-sm text-blue-100/70">No invites sent.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Messaging --}}
    <div class="grid gap-4 lg:grid-cols-[320px_minmax(0,1fr)]">
        <div class="glass-card rounded-2xl p-4 border border-white/10 space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-white font-semibold">Friends</h3>
                <span class="text-xs text-blue-100/70">{{ $friends->count() }} connected</span>
            </div>
            <div class="space-y-2 max-h-[420px] overflow-y-auto pr-1 custom-scrollbar">
                @forelse($friends as $friend)
                    @php $isActive = $activeFriend && $activeFriend->id === $friend->id; @endphp
                    <a href="{{ route('messages.index', ['friend' => $friend->id]) }}"
                       class="flex items-center gap-3 p-3 rounded-xl border {{ $isActive ? 'border-blue-500/50 bg-blue-500/10 text-white' : 'border-white/10 bg-white/5 text-blue-100' }} hover:border-blue-400/40 transition">
                        <div class="w-10 h-10 rounded-full bg-blue-500/20 flex items-center justify-center text-sm font-bold text-blue-200 uppercase">
                            {{ mb_substr($friend->name ?? 'U', 0, 2) }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold truncate">{{ $friend->name }}</p>
                            <p class="text-xs text-blue-100/60 truncate">{{ $friend->email }}</p>
                        </div>
                    </a>
                @empty
                    <p class="text-sm text-blue-100/70">No friends connected yet. Send an invite to start.</p>
                @endforelse
            </div>
        </div>

        <div class="glass-card rounded-2xl p-4 border border-white/10 min-h-[460px] flex flex-col">
            @if(!$activeFriend)
                <div class="flex-1 flex items-center justify-center text-center text-blue-100/70">
                    Select a friend to start chatting.
                </div>
            @else
                <div class="flex items-center justify-between pb-3 border-b border-white/10">
                    <div>
                        <p class="text-sm text-blue-100/60">Chat with</p>
                        <p class="text-white font-bold text-lg">{{ $activeFriend->name }}</p>
                    </div>
                    <span class="text-xs px-3 py-1 rounded-full bg-green-500/15 border border-green-400/30 text-green-100">
                        Connected
                    </span>
                </div>

                <div class="flex-1 mt-4 space-y-4 overflow-y-auto pr-1 custom-scrollbar">
                    @forelse($messages as $message)
                        @php $isMine = $message->sender_id === auth()->id(); @endphp
                        <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                            @if($message->trashed())
                                <div class="w-full flex justify-center">
                                    <div class="px-4 py-2 rounded-full bg-slate-800/80 border border-white/10 text-sm text-blue-100/80">
                                        Message recalled
                                    </div>
                                </div>
                            @else
                                <div class="max-w-[85%] md:max-w-[75%] min-w-[160px] rounded-2xl px-4 py-3 shadow-sm {{ $isMine ? 'bg-blue-600 text-white' : 'bg-slate-800 text-blue-100' }}">
                                    <div class="flex flex-col gap-2">
                                        <p class="text-sm leading-relaxed break-words whitespace-pre-wrap">{{ $message->body }}</p>
                                        <div class="flex items-center justify-between text-[11px] {{ $isMine ? 'text-blue-50/80' : 'text-blue-100/70' }}">
                                            <span>{{ $message->created_at->timezone(config('app.timezone'))->format('d M H:i') }}</span>
                                            @if($isMine && $message->read_at)
                                                <span>Read</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-blue-100/70">No messages yet. Start the conversation!</p>
                    @endforelse
                </div>

                <form action="{{ route('messages.send') }}" method="POST" class="mt-4 pt-3 border-t border-white/10 flex gap-2">
                    @csrf
                    <input type="hidden" name="friend_id" value="{{ $activeFriend->id }}">
                    <input type="text" name="body" placeholder="Type a message..."
                           class="flex-1 rounded-xl bg-white/5 border border-white/10 text-white placeholder:text-blue-100/40 focus:ring-0 focus:border-blue-300/40">
                    <button type="submit"
                            class="px-4 py-3 rounded-xl bg-blue-500 hover:bg-blue-600 text-white font-semibold transition">
                        Send
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
