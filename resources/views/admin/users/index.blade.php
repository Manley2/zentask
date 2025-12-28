@extends('layouts.dashboard-layout')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-3xl lg:text-4xl font-bold text-white">Admin Users</h1>
            <p class="text-blue-100/70 mt-1">Daftar akun terdaftar dan status subscription.</p>
        </div>
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex items-center gap-2">
            <input type="text" name="q" value="{{ $query }}"
                   class="rounded-xl bg-white/5 border border-white/10 text-white placeholder:text-blue-100/40 px-4 py-2 text-sm"
                   placeholder="Cari nama/email...">
            <button class="px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">Search</button>
        </form>
    </div>

    <div class="glass-card rounded-2xl p-6 border border-white/10 overflow-x-auto">
        <table class="w-full text-sm text-blue-100/80">
            <thead>
                <tr class="text-left text-blue-100/60 border-b border-white/10">
                    <th class="py-3">Nama</th>
                    <th class="py-3">Email</th>
                    <th class="py-3">Role</th>
                    <th class="py-3">Plan</th>
                    <th class="py-3">Subscribed</th>
                    <th class="py-3">Expired</th>
                    <th class="py-3">Register</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr class="border-b border-white/5">
                        <td class="py-3 text-white font-semibold">{{ $user->name }}</td>
                        <td class="py-3">{{ $user->email }}</td>
                        <td class="py-3 uppercase text-xs">{{ $user->role ?? 'user' }}</td>
                        <td class="py-3 capitalize">{{ $user->subscription_plan ?? 'free' }}</td>
                        <td class="py-3">
                            @if($user->is_subscribed || in_array($user->subscription_plan, ['pro', 'plus']))
                                <span class="px-2 py-1 rounded-full text-xs bg-green-500/15 text-green-200 border border-green-400/20">Active</span>
                            @else
                                <span class="px-2 py-1 rounded-full text-xs bg-red-500/15 text-red-200 border border-red-400/20">No</span>
                            @endif
                        </td>
                        <td class="py-3 text-xs text-blue-100/60">
                            {{ optional($user->subscribed_until)->format('d M Y') ?? '-' }}
                        </td>
                        <td class="py-3 text-xs text-blue-100/60">
                            {{ $user->created_at->format('d M Y') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-6 text-center text-blue-100/60">Tidak ada user.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $users->links() }}
    </div>
</div>
@endsection
