@extends('layouts.dashboard')

@section('content')

<div class="mb-10">
    <h1 class="text-5xl font-black text-white flex items-center gap-4">
        📅 Calendar
    </h1>
    <p class="text-blue-200 mt-2">
        Klik tanggal untuk melihat task pada hari tersebut
    </p>
</div>

{{-- GRID UTAMA --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- KALENDER --}}
    <div class="lg:col-span-2 premium-card p-6">
        <div class="flex items-center justify-between mb-4">
            <button class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/15">← Prev</button>
            <h2 class="text-2xl font-bold text-white">
                {{ now()->format('F Y') }}
            </h2>
            <button class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/15">Next →</button>
        </div>

        {{-- HARI --}}
        <div class="grid grid-cols-7 text-center text-blue-200 mb-2">
            <div>Mon</div>
            <div>Tue</div>
            <div>Wed</div>
            <div>Thu</div>
            <div>Fri</div>
            <div>Sat</div>
            <div>Sun</div>
        </div>

        {{-- TANGGAL (sementara dummy dulu) --}}
        <div class="grid grid-cols-7 gap-2">
            @for($i = 1; $i <= 31; $i++)
                <div
                    class="h-20 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-white hover:bg-blue-500/20 cursor-pointer">
                    {{ $i }}
                </div>
            @endfor
        </div>
    </div>

    {{-- TASK LIST (KANAN) --}}
    <div class="premium-card p-6">
        <h3 class="text-xl font-bold text-white mb-4">
            Tasks on {{ now()->format('d M Y') }}
        </h3>

        <p class="text-slate-400 text-sm">
            Tidak ada task di tanggal ini.
        </p>
    </div>

</div>

@endsection
