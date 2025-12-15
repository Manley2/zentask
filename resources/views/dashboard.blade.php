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

        {{-- =========================
        [STATS VAR] HITUNG SEKALI (BIAR KONSISTEN)
        ========================== --}}
        @php
            // Total
            $totalAll = $tasks->count();

            // Completed = completed
            $totalCompleted = $tasks->where('status', 'completed')->count();

            // In Progress
            $totalInProgress = $tasks->where('status', 'in_progress')->count();

            // Minggu ini (created_at >= 7 hari terakhir)
            $totalThisWeek = $tasks->filter(function ($t) {
                return $t->created_at && $t->created_at->gte(now()->subDays(7));
            })->count();

            // Deadline terdekat
            $nearestTask = $tasks
                ->where('status', 'in_progress')
                ->whereNotNull('due_date')
                ->sortBy('due_date')
                ->first();

            // Progress bar completed (%)
            $completedPercent = $totalAll > 0 ? ($totalCompleted / $totalAll) * 100 : 0;
        @endphp

        <!-- Total Tasks Card -->
        <div class="premium-card p-7 hover-lift group">
            <div class="flex items-center justify-between mb-5">
                <div class="icon-wrapper bg-blue-500/20">
                    <svg class="w-9 h-9 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <span class="badge badge-blue">+{{ $totalThisWeek }} minggu ini</span>
            </div>
            <p class="text-blue-200 text-sm font-semibold mb-2 uppercase tracking-wide">Total Kegiatan</p>
            <p class="text-6xl font-black text-white mb-2">{{ $totalAll }}</p>
            <div class="h-1 bg-blue-500/20 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-blue-400 to-blue-600 w-full animate-shimmer"></div>
            </div>
        </div>

        <!-- In Progress Card -->
        <div class="premium-card p-7 hover-lift group">
            <div class="flex items-center justify-between mb-5">
                <div class="icon-wrapper bg-yellow-500/20">
                    <svg class="w-9 h-9 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="badge badge-yellow">Berjalan</span>
            </div>
            <p class="text-yellow-200 text-sm font-semibold mb-2 uppercase tracking-wide">In Progress</p>
            <p class="text-6xl font-black text-white mb-2">{{ $totalInProgress }}</p>
            <div class="h-1 bg-yellow-500/20 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-yellow-400 to-yellow-600 w-2/3 animate-shimmer"></div>
            </div>
        </div>

        <!-- Completed Tasks Card -->
        <div class="premium-card p-7 hover-lift group">
            <div class="flex items-center justify-between mb-5">
                <div class="icon-wrapper bg-green-500/20">
                    <svg class="w-9 h-9 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="badge badge-green">Selesai</span>
            </div>
            <p class="text-green-200 text-sm font-semibold mb-2 uppercase tracking-wide">Task Completed</p>
            <p class="text-6xl font-black text-white mb-3">{{ $totalCompleted }}</p>
            <div class="h-3 bg-slate-700/50 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-green-400 via-emerald-500 to-green-600 transition-all duration-700 rounded-full shadow-lg shadow-green-500/50"
                     style="width: {{ $completedPercent }}%">
                </div>
            </div>
        </div>

        <!-- Deadline Card -->
        <div class="premium-card p-7 hover-lift group">
            <div class="flex items-center justify-between mb-5">
                <div class="icon-wrapper bg-red-500/20">
                    <svg class="w-9 h-9 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <span class="badge badge-red">Urgent</span>
            </div>

            <p class="text-red-200 text-sm font-semibold mb-2 uppercase tracking-wide">Deadline Terdekat</p>

            <p class="text-3xl font-black text-white mb-1">
                {{ $nearestTask ? \Carbon\Carbon::parse($nearestTask->due_date)->format('d M Y') : 'Tidak ada' }}
            </p>

            @if($nearestTask)
                <p class="text-sm text-red-300/80 mt-2 font-medium truncate">{{ $nearestTask->title }}</p>
            @endif
        </div>

    </div>

    {{-- =========================
    UPCOMING TASKS (FULL WIDTH)
    - preview: 3 task saja (deadline terdekat di kiri)
    - modal: show all (urut deadline terdekat di atas)
    ========================== --}}
    @php
        $in_progressSorted = $tasks
            ->where('status', 'in_progress')
            ->sortBy(function ($t) {
                return $t->due_date
                    ? \Carbon\Carbon::parse($t->due_date)->timestamp
                    : PHP_INT_MAX; // null taruh paling bawah
            })
            ->values();

        $in_progressTop3 = $in_progressSorted->take(3);
        $in_progressCount = $in_progressSorted->count();
    @endphp

    <div class="grid grid-cols-1 gap-6">
        <div class="col-span-1">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-4xl font-black text-white flex items-center gap-3">
                    <span class="text-4xl">📋</span>
                    Upcoming Tasks
                </h3>

                {{-- SHOW ALL (muncul jika > 3) --}}
                @if($in_progressCount > 3)
                    <button type="button" onclick="openUpcomingModal()"
                            class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/15 border border-white/10 text-blue-100 font-semibold transition">
                        Show All ({{ $in_progressCount }})
                    </button>
                @endif
            </div>

            {{-- PREVIEW 3 CARD (HORIZONTAL) --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @forelse ($in_progressTop3 as $task)
                    <div class="task-card group min-h-[170px] flex flex-col justify-between">
                        <div>
                            <h4 class="text-white font-bold text-xl mb-2 group-hover:text-blue-300 transition-colors">
                                {{ $task->title }}
                            </h4>

                            @if(!empty($task->description))
                                <p class="text-slate-300 text-sm mb-3 leading-relaxed">
                                    {{ Str::limit($task->description, 80) }}
                                </p>
                            @endif
                        </div>

                        <div class="flex items-center gap-2 flex-wrap">
                            @if($task->due_date)
                                <span class="badge badge-blue">
                                    📅 {{ \Carbon\Carbon::parse($task->due_date)->format('d M Y') }}
                                </span>
                            @endif

                            <span class="badge badge-purple">IN PROGRESS</span>
                        </div>
                    </div>
                @empty
                    <div class="premium-card p-8 text-center">
                        <p class="text-slate-300 text-sm">Tidak ada upcoming task 📌</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- =========================
    MODAL POPUP "SHOW ALL"
    ========================== --}}
    <div id="upcomingModal" class="fixed inset-0 z-[9999] hidden items-start justify-center p-4 sm:p-6">
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeUpcomingModal()"></div>

        {{-- Modal Box --}}
        <div class="relative w-full max-w-3xl mt-24 rounded-2xl bg-slate-950/70 border border-white/10 backdrop-blur-xl shadow-2xl overflow-hidden max-h-[calc(100vh-8rem)]">

            <div class="flex items-center justify-between px-6 py-4 border-b border-white/10">
                <h4 class="text-white font-black text-xl">Semua Upcoming Tasks</h4>

                <button type="button" onclick="closeUpcomingModal()"
                        class="px-3 py-2 rounded-xl bg-white/10 hover:bg-white/15 border border-white/10 text-blue-100 font-semibold transition">
                    Tutup
                </button>
            </div>

            <div class="p-6 overflow-y-auto space-y-4" style="max-height: calc(100vh - 14rem);">
                @php
                    // Modal: pakai urutan yang sama (deadline terdekat paling atas)
                    $in_progressModal = $in_progressSorted;
                @endphp

                @forelse($in_progressModal as $task)
                    <div class="task-card group min-h-[170px] flex flex-col justify-between">
                        <div>
                            <h4 class="text-white font-bold text-xl mb-2 group-hover:text-blue-300 transition-colors">
                                {{ $task->title }}
                            </h4>

                            @if(!empty($task->description))
                                <p class="text-slate-300 text-sm mb-3 leading-relaxed">
                                    {{ Str::limit($task->description, 140) }}
                                </p>
                            @endif
                        </div>

                        <div class="flex items-center gap-2 flex-wrap">
                            @if($task->due_date)
                                <span class="badge badge-blue">
                                    📅 {{ \Carbon\Carbon::parse($task->due_date)->format('d M Y') }}
                                </span>
                            @endif
                            <span class="badge badge-purple">IN PROGRESS</span>
                        </div>
                    </div>
                @empty
                    <div class="premium-card p-8 text-center">
                        <p class="text-slate-300 text-sm">Belum ada upcoming task 📌</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Script Modal --}}
    <script>
        function openUpcomingModal() {
            const modal = document.getElementById('upcomingModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeUpcomingModal() {
            const modal = document.getElementById('upcomingModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeUpcomingModal();
        });
    </script>

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
        0% {
            transform: translateX(-100%);
        }

        100% {
            transform: translateX(100%);
        }
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0px);
        }

        50% {
            transform: translateY(-20px);
        }
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
