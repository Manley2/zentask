<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use Carbon\Carbon;

class AdminAnalyticsController extends Controller
{
    public function index()
    {
        $days = 14;
        $start = Carbon::today()->subDays($days - 1);

        $visits = Visit::where('visited_at', '>=', $start)
            ->orderBy('visited_at', 'asc')
            ->get()
            ->groupBy(function ($visit) {
                return $visit->visited_at->format('Y-m-d');
            });

        $labels = [];
        $pageViews = [];
        $uniqueVisitors = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i)->format('Y-m-d');
            $labels[] = Carbon::parse($date)->format('d M');
            $dayVisits = $visits->get($date, collect());
            $pageViews[] = $dayVisits->count();
            $uniqueVisitors[] = $dayVisits->unique('ip_hash')->count();
        }

        return view('admin.analytics.index', [
            'labels' => $labels,
            'pageViews' => $pageViews,
            'uniqueVisitors' => $uniqueVisitors,
        ]);
    }
}
