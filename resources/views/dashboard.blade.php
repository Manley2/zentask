@extends('layouts.dashboard')

@section('content')

    <!-- Header Section dengan Gradient Text -->
    <div class="mb-10">
        <h1 class="text-6xl font-black mb-3 gradient-text">
            ✨ ZenTask Dashboard
        </h1>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mt-6">
            <div>
                <p class="text-3xl font-bold text-white mb-1">
                    Halo, {{ Auth::user()->name }} 
                </p>
                <p class="text-blue-200 text-base">{{ now()->format('l, d F Y') }}</p>
            </div>
            <div class="premium-card px-8 py-4">
                <p class="text-white/80 text-sm font-medium mb-1">Total Produktivitas</p>
                <p class="text-5xl font-black gradient-text-green">
                    {{ $tasks->count() > 0 ? round(($tasks->where('status', 'completed')->count() / $tasks->count()) * 100) : 0 }}%
                </p>
            </div>
        </div>
    </div>

    {{-- STATISTICS CARDS dengan Style Premium --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">

        <!-- Total Tasks Card -->
        <div class="premium-card p-7 hover-lift group">
            <div class="flex items-center justify-between mb-5">
                <div class="icon-wrapper bg-blue-500/20">
                    <svg class="w-9 h-9 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <span class="badge badge-blue">+{{ $tasks->where('created_at', '>=', now()->subDays(7))->count() }} minggu ini</span>
            </div>
            <p class="text-blue-200 text-sm font-semibold mb-2 uppercase tracking-wide">Total Kegiatan</p>
            <p class="text-6xl font-black text-white mb-2">{{ $tasks->count() }}</p>
            <div class="h-1 bg-blue-500/20 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-blue-400 to-blue-600 w-full animate-shimmer"></div>
            </div>
        </div>

        <!-- Completed Tasks Card -->
        <div class="premium-card p-7 hover-lift group">
            <div class="flex items-center justify-between mb-5">
                <div class="icon-wrapper bg-green-500/20">
                    <svg class="w-9 h-9 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="badge badge-green">Selesai</span>
            </div>
            <p class="text-green-200 text-sm font-semibold mb-2 uppercase tracking-wide">Task Completed</p>
            <p class="text-6xl font-black text-white mb-3">{{ $tasks->where('status', 'completed')->count() }}</p>
            <div class="h-3 bg-slate-700/50 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-green-400 via-emerald-500 to-green-600 transition-all duration-700 rounded-full shadow-lg shadow-green-500/50"
                     style="width: {{ $tasks->count() > 0 ? ($tasks->where('status', 'completed')->count() / $tasks->count()) * 100 : 0 }}%">
                </div>
            </div>
        </div>

        <!-- In Progress Card -->
        <div class="premium-card p-7 hover-lift group">
            <div class="flex items-center justify-between mb-5">
                <div class="icon-wrapper bg-yellow-500/20">
                    <svg class="w-9 h-9 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="badge badge-yellow">Berjalan</span>
            </div>
            <p class="text-yellow-200 text-sm font-semibold mb-2 uppercase tracking-wide">In Progress</p>
            <p class="text-6xl font-black text-white mb-2">{{ $tasks->where('status', 'in_progress')->count() }}</p>
            <div class="h-1 bg-yellow-500/20 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-yellow-400 to-yellow-600 w-2/3 animate-shimmer"></div>
            </div>
        </div>

        <!-- Deadline Card -->
        <div class="premium-card p-7 hover-lift group">
            <div class="flex items-center justify-between mb-5">
                <div class="icon-wrapper bg-red-500/20">
                    <svg class="w-9 h-9 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <span class="badge badge-red">Urgent</span>
            </div>
            <p class="text-red-200 text-sm font-semibold mb-2 uppercase tracking-wide">Deadline Terdekat</p>
            @php
                $nearestTask = $tasks->whereNotNull('due_date')->sortBy('due_date')->first();
            @endphp
            <p class="text-3xl font-black text-white mb-1">
                {{ $nearestTask ? \Carbon\Carbon::parse($nearestTask->due_date)->format('d M Y') : 'Tidak ada' }}
            </p>
            @if($nearestTask)
                <p class="text-sm text-red-300/80 mt-2 font-medium truncate">{{ $nearestTask->title }}</p>
            @endif
        </div>

    </div>

    {{-- TASK LIST & CALENDAR --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Upcoming Tasks --}}
        <div class="lg:col-span-2">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-3xl font-black text-white flex items-center gap-3">
                    <span class="text-4xl">📋</span>
                    Upcoming Tasks
                </h3>
            </div>

            <div class="space-y-4">
                @forelse ($tasks->where('status', 'pending')->take(5) as $task)
                    <div class="task-card group">
                        <div class="flex items-start gap-4">
                            <input type="checkbox" class="task-checkbox">
                            <div class="flex-1">
                                <h4 class="text-white font-bold text-xl mb-2 group-hover:text-blue-300 transition-colors">
                                    {{ $task->title }}
                                </h4>
                                @if(isset($task->description) && $task->description)
                                    <p class="text-slate-300 text-sm mb-3 leading-relaxed">{{ Str::limit($task->description, 80) }}</p>
                                @endif
                                <div class="flex items-center gap-2 flex-wrap">
                                    @if(isset($task->priority) && $task->priority)
                                        <span class="badge
                                            {{ $task->priority === 'high' ? 'badge-red' : '' }}
                                            {{ $task->priority === 'medium' ? 'badge-yellow' : '' }}
                                            {{ $task->priority === 'low' ? 'badge-green' : '' }}">
                                            🔥 {{ ucfirst($task->priority) }}
                                        </span>
                                    @endif
                                    @if($task->due_date)
                                        <span class="badge badge-blue">
                                            📅 {{ \Carbon\Carbon::parse($task->due_date)->format('d M Y') }}
                                        </span>
                                    @endif
                                    <span class="badge badge-purple">
                                        {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <div class="text-center">
                            <div class="empty-icon">✨</div>
                            <p class="text-white text-2xl font-bold mb-2">Belum ada tugas baru</p>
                            <p class="text-slate-400">Semua tugas sudah selesai! Kerja bagus! 🎉</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Calendar & Events --}}
        <div>
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-3xl font-black text-white flex items-center gap-3">
                    <span class="text-4xl">📅</span>
                    Calendar
                </h3>
            </div>

            <div class="premium-card p-6 mb-5">
                <div class="text-center mb-5">
                    <p class="text-white font-bold text-2xl mb-1">{{ now()->format('F Y') }}</p>
                    <p class="text-blue-300 text-sm font-medium">{{ now()->format('l') }}</p>
                </div>
                <div class="calendar-date">
                    <p class="text-8xl font-black text-white">{{ now()->format('d') }}</p>
                </div>
            </div>

            <div class="space-y-3">
                <h4 class="text-white font-bold text-sm uppercase tracking-wider mb-4">Upcoming Events</h4>
                @forelse ($tasks->whereNotNull('due_date')->sortBy('due_date')->take(6) as $event)
                    <div class="event-card group">
                        <div class="flex gap-4">
                            <div class="event-date">
                                <p class="text-white font-black text-2xl">{{ \Carbon\Carbon::parse($event->due_date)->format('d') }}</p>
                                <p class="text-purple-300 text-xs font-bold uppercase">{{ \Carbon\Carbon::parse($event->due_date)->format('M') }}</p>
                            </div>
                            <div class="flex-1">
                                <p class="text-white font-bold group-hover:text-purple-300 transition-colors mb-1">{{ $event->title }}</p>
                                <p class="text-slate-400 text-xs">{{ \Carbon\Carbon::parse($event->due_date)->diffForHumans() }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="premium-card p-8 text-center">
                        <p class="text-slate-400 text-sm">Belum ada jadwal 📌</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>

@endsection

<style>
    /* Premium Card Style */
    .premium-card {
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 24px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .hover-lift:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 48px rgba(0, 0, 0, 0.6);
        border-color: rgba(255, 255, 255, 0.2);
    }

    /* Gradient Text */
    .gradient-text {
        background: linear-gradient(135deg, #60a5fa, #a78bfa);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .gradient-text-green {
        background: linear-gradient(135deg, #34d399, #10b981);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* Icon Wrapper */
    .icon-wrapper {
        padding: 1rem;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .hover-lift:hover .icon-wrapper {
        transform: scale(1.1) rotate(5deg);
    }

    /* Badge Styles */
    .badge {
        padding: 0.5rem 1rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        transition: all 0.3s ease;
    }

    .badge-blue {
        background: rgba(96, 165, 250, 0.2);
        color: #60a5fa;
        border: 1px solid rgba(96, 165, 250, 0.3);
    }

    .badge-green {
        background: rgba(52, 211, 153, 0.2);
        color: #34d399;
        border: 1px solid rgba(52, 211, 153, 0.3);
    }

    .badge-yellow {
        background: rgba(251, 191, 36, 0.2);
        color: #fbbf24;
        border: 1px solid rgba(251, 191, 36, 0.3);
    }

    .badge-red {
        background: rgba(248, 113, 113, 0.2);
        color: #f87171;
        border: 1px solid rgba(248, 113, 113, 0.3);
    }

    .badge-purple {
        background: rgba(167, 139, 250, 0.2);
        color: #a78bfa;
        border: 1px solid rgba(167, 139, 250, 0.3);
    }

    /* Task Card */
    .task-card {
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(24px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 1.75rem;
        transition: all 0.3s ease;
    }

    .task-card:hover {
        transform: translateX(8px);
        border-color: rgba(96, 165, 250, 0.3);
        box-shadow: 0 8px 24px rgba(96, 165, 250, 0.2);
    }

    .task-checkbox {
        width: 24px;
        height: 24px;
        border-radius: 8px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        background: rgba(255, 255, 255, 0.05);
        cursor: pointer;
        margin-top: 4px;
    }

    /* Calendar Date */
    .calendar-date {
        background: linear-gradient(135deg, rgba(96, 165, 250, 0.2), rgba(167, 139, 250, 0.2));
        border-radius: 20px;
        padding: 2rem;
        text-align: center;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Event Card */
    .event-card {
        background: rgba(15, 23, 42, 0.6);
        backdrop-filter: blur(16px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 1.25rem;
        transition: all 0.3s ease;
    }

    .event-card:hover {
        border-color: rgba(167, 139, 250, 0.3);
        transform: translateX(4px);
    }

    .event-date {
        background: linear-gradient(135deg, rgba(167, 139, 250, 0.3), rgba(236, 72, 153, 0.3));
        border-radius: 12px;
        padding: 0.75rem 1rem;
        text-align: center;
        min-width: 70px;
    }

    /* Empty State */
    .empty-state {
        background: rgba(15, 23, 42, 0.5);
        backdrop-filter: blur(24px);
        border: 2px dashed rgba(255, 255, 255, 0.1);
        border-radius: 24px;
        padding: 4rem 2rem;
    }

    .empty-icon {
        font-size: 5rem;
        margin-bottom: 1rem;
        animation: float 3s ease-in-out infinite;
    }

    /* Animations */
    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-20px); }
    }

    .animate-shimmer {
        animation: shimmer 2s infinite;
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar {
        width: 10px;
    }

    ::-webkit-scrollbar-track {
        background: rgba(15, 23, 42, 0.5);
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb {
        background: rgba(96, 165, 250, 0.3);
        border-radius: 10px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: rgba(96, 165, 250, 0.5);
    }
</style>
