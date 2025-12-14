<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::id();

        return view('dashboard', [
            'today' => Task::where('user_id', $user)
                ->whereDate('due_date', Carbon::today())
                ->count(),

            'upcoming' => Task::where('user_id', $user)
                ->whereDate('due_date', '>', Carbon::today())
                ->count(),

            'completed' => Task::where('user_id', $user)
                ->where('status', 'completed')
                ->count(),
        ]);
    }
}
