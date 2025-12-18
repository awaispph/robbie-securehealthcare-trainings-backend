<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Candidate;
use App\Models\Certificate;

class DashboardController extends Controller
{
    public function stats()
    {
        return response()->json([
            'upcoming_events_count' => Event::upcoming()->count(),
            'total_candidates' => Candidate::count(),
            'certificates_issued' => Certificate::count(),
            'events_this_month' => Event::whereMonth('start_date', now()->month)
                ->whereYear('start_date', now()->year)
                ->count(),
        ]);
    }

    public function upcomingEvents()
    {
        $events = Event::with(['location', 'courses'])
            ->withCount('candidates')
            ->upcoming()
            ->limit(5)
            ->get();

        return response()->json(['data' => $events]);
    }
}
