<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use Carbon\Carbon;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $tz = config('app.timezone');

        // Bulan yang sedang dilihat (default: bulan sekarang)
        $monthParam = $request->query('month'); // format: YYYY-MM
        $monthStart = $monthParam
            ? Carbon::createFromFormat('Y-m', $monthParam, $tz)->startOfMonth()
            : now($tz)->startOfMonth();

        $monthEnd = $monthStart->copy()->endOfMonth();

        // Tanggal yang dipilih (default: hari ini)
        $selectedDate = $request->query('date')
            ? Carbon::parse($request->query('date'), $tz)->toDateString()
            : now($tz)->toDateString();

        // Ambil semua task user yang due_date ada, khusus di bulan yang sedang dilihat
        $tasksInMonth = Task::where('user_id', auth()->id())
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get(['id', 'title', 'due_date', 'status', 'category']);

        // Buat mapping: 'YYYY-MM-DD' => jumlah task (untuk badge di kalender)
        $taskCountByDate = $tasksInMonth
            ->groupBy(fn($t) => Carbon::parse($t->due_date, $tz)->toDateString())
            ->map(fn($items) => $items->count())
            ->toArray();

        // List task untuk tanggal yang dipilih
        $tasksOnSelectedDate = Task::where('user_id', auth()->id())
            ->whereDate('due_date', $selectedDate)
            ->orderByRaw("CASE
        WHEN status = 'in_progress' THEN 0
        WHEN status = 'completed' THEN 1
        ELSE 3
    END")
            ->orderBy('due_date', 'asc')
            ->orderBy('id', 'desc')
            ->get();


        // Navigasi bulan
        $prevMonth = $monthStart->copy()->subMonth()->format('Y-m');
        $nextMonth = $monthStart->copy()->addMonth()->format('Y-m');

        return view('calendar', compact(
            'monthStart',
            'selectedDate',
            'taskCountByDate',
            'tasksOnSelectedDate',
            'prevMonth',
            'nextMonth'
        ));
    }
}
