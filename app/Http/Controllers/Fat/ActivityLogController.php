<?php

namespace App\Http\Controllers\Fat;

use App\Http\Controllers\Controller;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user->isFAT() && !$user->isSuperAdmin()) {
            abort(403);
        }

        $query = Activity::with('causer')->latest();

        // Filter: Causer (User)
        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id)
                  ->where('causer_type', User::class);
        }

        // Filter: Event type
        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        // Filter: Subject type (Model)
        if ($request->filled('subject_type')) {
            $query->where('subject_type', 'like', '%' . $request->subject_type . '%');
        }

        // Filter: Date from
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        // Filter: Date to
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter: Search in description / properties
        if ($request->filled('search')) {
            $keyword = $request->search;
            $query->where(function ($q) use ($keyword) {
                $q->where('description', 'like', "%{$keyword}%")
                  ->orWhere('properties', 'like', "%{$keyword}%");
            });
        }

        $logs = $query->paginate(25)->withQueryString();

        // For filter dropdowns
        $causers     = User::orderBy('name')->get(['id', 'name']);
        $eventTypes  = Activity::select('event')->distinct()->pluck('event')->filter()->sort()->values();
        $subjectTypes = Activity::select('subject_type')->distinct()->pluck('subject_type')
            ->filter()
            ->map(fn($t) => class_basename($t))
            ->sort()
            ->values();

        // Stats
        $totalToday   = Activity::whereDate('created_at', today())->count();
        $totalCreated = Activity::where('event', 'created')->count();
        $totalUpdated = Activity::where('event', 'updated')->count();
        $totalDeleted = Activity::where('event', 'deleted')->count();

        return view('fat.activity_logs.index', compact(
            'logs', 'causers', 'eventTypes', 'subjectTypes',
            'totalToday', 'totalCreated', 'totalUpdated', 'totalDeleted'
        ));
    }
}
