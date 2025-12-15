@extends('layouts.dashboard')

@section('content')
    @php
        use Carbon\Carbon;

        $tz = config('app.timezone');

        // Month label
        $monthLabel = $monthStart->timezone($tz)->format('F Y');

        // Calendar grid
        $startOfGrid = $monthStart->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $endOfGrid = $monthStart->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $days = [];
        $cursor = $startOfGrid->copy();
        while ($cursor->lte($endOfGrid)) {
            $days[] = $cursor->copy();
            $cursor->addDay();
        }

        $selected = Carbon::parse($selectedDate, $tz)->toDateString();

        // Day headers (Mon-Sun)
        $dayHeaders = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    @endphp

    <div class="mb-10">
        <div class="flex items-start gap-4">
            <div
                class="w-14 h-14 rounded-2xl bg-white/10 border border-white/10 backdrop-blur flex items-center justify-center">
                {{-- icon calendar --}}
                <svg class="w-7 h-7 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>

            <div>
                {{-- Judul --}}
                <h1 class="text-6xl font-black text-blue-200 mb-2">
                    Calendar
                </h1>

                {{-- Subtext: biru muda seperti Tasks --}}
                <p class="text-blue-300/80 text-base">
                    Klik tanggal untuk melihat list task pada hari itu.
                </p>
            </div>
        </div>
    </div>


    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- LEFT: Calendar --}}
        <div class="lg:col-span-2 premium-card p-6">
            <div class="flex items-center justify-between mb-5">
                <a href="{{ url('/calendar') }}?month={{ $prevMonth }}&date={{ $selectedDate }}"
                    class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/15 border border-white/10 text-blue-100 font-semibold transition">
                    ← Prev
                </a>

                <div class="text-center">
                    <div class="text-white font-black text-3xl">{{ $monthLabel }}</div>
                    <div class="text-blue-200 text-sm mt-1">
                        Tanggal dipilih: <span
                            class="font-semibold text-blue-100">{{ Carbon::parse($selectedDate, $tz)->format('d M Y') }}</span>
                    </div>
                </div>

                <a href="{{ url('/calendar') }}?month={{ $nextMonth }}&date={{ $selectedDate }}"
                    class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/15 border border-white/10 text-blue-100 font-semibold transition">
                    Next →
                </a>
            </div>

            {{-- Day headers --}}
            <div class="grid grid-cols-7 gap-2 mb-3">
                @foreach($dayHeaders as $h)
                    <div class="text-center text-xs font-bold uppercase tracking-wider text-blue-100/70">
                        {{ $h }}
                    </div>
                @endforeach
            </div>

            {{-- Days grid --}}
            <div class="grid grid-cols-7 gap-2">
                @foreach($days as $d)
                    @php
                        $dateStr = $d->toDateString();
                        $inMonth = $d->month === $monthStart->month;
                        $isToday = $dateStr === now($tz)->toDateString();
                        $isSelected = $dateStr === $selected;

                        $count = $taskCountByDate[$dateStr] ?? 0;

                        $baseClass = "relative rounded-xl border backdrop-blur transition overflow-hidden";
                        $bgClass = $inMonth ? "bg-white/5 border-white/10 hover:bg-white/10" : "bg-white/0 border-white/5 opacity-50";
                        $todayRing = $isToday ? "ring-2 ring-blue-400/50" : "";
                        $selectedRing = $isSelected ? "ring-2 ring-purple-400/60 bg-white/10 border-white/20" : "";
                    @endphp

                    <a href="{{ url('/calendar') }}?month={{ $monthStart->format('Y-m') }}&date={{ $dateStr }}"
                        class="{{ $baseClass }} {{ $bgClass }} {{ $todayRing }} {{ $selectedRing }} p-3 min-h-[70px]">
                        <div class="flex items-start justify-between">
                            <div class="text-white font-bold">
                                {{ $d->format('j') }}
                            </div>

                            @if($count > 0)
                                <span
                                    class="text-[10px] font-black px-2 py-1 rounded-full bg-blue-500/20 border border-blue-400/20 text-blue-100">
                                    {{ $count }}
                                </span>
                            @endif
                        </div>

                        {{-- Optional tiny dot indicator --}}
                        @if($count > 0)
                            <div class="mt-2 flex gap-1 flex-wrap">
                                <span class="h-1.5 w-10 rounded-full bg-blue-400/40"></span>
                            </div>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        {{-- RIGHT: Tasks list (selected date) --}}
        <div class="premium-card p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-white font-black text-2xl">Tasks</h3>
                <div class="text-blue-200 text-sm">
                    {{ Carbon::parse($selectedDate, $tz)->format('d M Y') }}
                </div>
            </div>

            @if($tasksOnSelectedDate->count() === 0)
                <div class="bg-white/5 border border-white/10 rounded-2xl p-6 text-center">
                    <div class="text-blue-100/80 font-semibold">Tidak ada task di tanggal ini.</div>
                    <div class="text-blue-100/50 text-sm mt-1">Coba klik tanggal lain di kalender.</div>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($tasksOnSelectedDate as $t)
                        @php
                            $status = $t->status ?? 'pending';
                            $statusLabel = strtoupper(str_replace('_', ' ', $status));

                            $statusClass = "badge badge-purple";
                            if ($status === 'completed')
                                $statusClass = "badge badge-green";
                            if ($status === 'in_progress')
                                $statusClass = "badge badge-yellow";
                            if ($status === 'pending')
                                $statusClass = "badge badge-purple";

                            $cat = $t->category ?? '-';
                            $due = $t->due_date ? Carbon::parse($t->due_date, $tz)->format('d M Y') : '-';
                        @endphp

                        <div class="bg-white/5 border border-white/10 rounded-2xl p-4 hover:bg-white/10 transition">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-white font-bold text-lg truncate">{{ $t->title }}</div>
                                    <div class="text-blue-100/60 text-sm mt-1">
                                        Category: <span class="text-blue-100/80 font-semibold">{{ $cat }}</span>
                                    </div>
                                    <div class="text-blue-100/60 text-sm">
                                        Deadline: <span class="text-blue-100/80 font-semibold">{{ $due }}</span>
                                    </div>
                                </div>

                                <div class="flex flex-col items-end gap-2">
                                    <span class="{{ $statusClass }}">{{ $statusLabel }}</span>

                                    {{-- optional quick link to edit --}}
                                    <a href="{{ route('tasks.edit', $t->id) }}"
                                        class="text-xs px-3 py-2 rounded-xl bg-white/10 hover:bg-white/15 border border-white/10 text-blue-100 font-semibold transition">
                                        Edit
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection

<style>
    /* Reuse style yang sudah kamu pakai di dashboard */
    .premium-card {
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 24px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .gradient-text {
        background: linear-gradient(135deg, #60a5fa, #a78bfa);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .badge {
        padding: 0.5rem 1rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border: 1px solid rgba(255, 255, 255, 0.12);
    }

    .badge-green {
        background: rgba(52, 211, 153, 0.2);
        color: #34d399;
        border-color: rgba(52, 211, 153, 0.25);
    }

    .badge-yellow {
        background: rgba(251, 191, 36, 0.2);
        color: #fbbf24;
        border-color: rgba(251, 191, 36, 0.25);
    }

    .badge-purple {
        background: rgba(167, 139, 250, 0.2);
        color: #a78bfa;
        border-color: rgba(167, 139, 250, 0.25);
    }
</style>
