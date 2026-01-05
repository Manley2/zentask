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
            // Count visitors per "person": logged-in users are grouped by user_id,
            // anonymous users are grouped by IP + User-Agent to distinguish devices on same network.
            $uniqueVisitors[] = $dayVisits
                ->unique(function ($visit) {
                    if ($visit->user_id) {
                        return 'user:' . $visit->user_id;
                    }
                    $ua = (string) $visit->user_agent;
                    return 'anon:' . $visit->ip_hash . '|' . $ua;
                })
                ->count();
        }

        return view('admin.analytics.index', [
            'labels' => $labels,
            'pageViews' => $pageViews,
            'uniqueVisitors' => $uniqueVisitors,
        ]);
    }
}
