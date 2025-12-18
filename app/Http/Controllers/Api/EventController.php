<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventCandidateCourse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $query = Event::with(['location', 'courses', 'trainer'])
            ->withCount('candidates');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('from_date')) {
            $query->where('start_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('start_date', '<=', $request->to_date);
        }

        if ($request->has('course_ids')) {
            $courseIds = explode(',', $request->course_ids);
            // AND condition - event must have ALL selected courses
            foreach ($courseIds as $courseId) {
                $query->whereHas('courses', function ($q) use ($courseId) {
                    $q->where('courses.id', $courseId);
                });
            }
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }

        $events = $query->orderBy('start_date', 'desc')->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => $events->items(),
            'meta' => [
                'current_page' => $events->currentPage(),
                'total' => $events->total(),
                'per_page' => $events->perPage(),
            ]
        ]);
    }

    public function upcoming()
    {
        $events = Event::with(['location', 'courses', 'trainer'])
            ->withCount('candidates')
            ->upcoming()
            ->limit(10)
            ->get();

        return response()->json(['data' => $events]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i,H:i:s',
            'end_time' => 'nullable|date_format:H:i,H:i:s',
            'location_id' => 'nullable|exists:locations,id',
            'trainer_id' => 'nullable|exists:trainers,id',
            'status' => 'in:draft,published,completed,cancelled',
            'notes' => 'nullable|string',
            'courses' => 'array',
            'courses.*' => 'exists:courses,id',
        ]);

        $event = Event::create($validated);

        if (!empty($validated['courses'])) {
            $event->courses()->attach($validated['courses']);
        }

        return response()->json([
            'data' => $event->load(['location', 'courses', 'trainer']),
            'message' => 'Event created'
        ], 201);
    }

    public function show(Event $event)
    {
        $event->load(['location', 'courses', 'trainer']);
        $event->loadCount(['candidates', 'certificates']);

        return response()->json(['data' => $event]);
    }

    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i,H:i:s',
            'end_time' => 'nullable|date_format:H:i,H:i:s',
            'location_id' => 'nullable|exists:locations,id',
            'trainer_id' => 'nullable|exists:trainers,id',
            'status' => 'in:draft,published,completed,cancelled',
            'notes' => 'nullable|string',
            'courses' => 'array',
            'courses.*' => 'exists:courses,id',
        ]);

        $event->update($validated);

        if (isset($validated['courses'])) {
            $event->courses()->sync($validated['courses']);
        }

        return response()->json([
            'data' => $event->load(['location', 'courses', 'trainer']),
            'message' => 'Event updated'
        ]);
    }

    public function destroy(Event $event)
    {
        $event->delete();
        return response()->json(['message' => 'Event deleted']);
    }

    public function candidates(Event $event)
    {
        $candidates = $event->candidates()->get();
        $courses = $event->courses()->get();

        $data = $candidates->map(function ($candidate) use ($event, $courses) {
            $attendance = EventCandidateCourse::where('event_id', $event->id)
                ->where('candidate_id', $candidate->id)
                ->get()
                ->keyBy('course_id');

            return [
                'id' => $candidate->id,
                'candidate' => [
                    'id' => $candidate->id,
                    'first_name' => $candidate->first_name,
                    'last_name' => $candidate->last_name,
                    'email' => $candidate->email,
                ],
                'registered_at' => $candidate->pivot->registered_at,
                'courses' => $courses->map(function ($course) use ($attendance) {
                    $att = $attendance->get($course->id);
                    return [
                        'course_id' => $course->id,
                        'course_name' => $course->name,
                        'attended' => $att?->attended,
                        'result' => $att?->result ?? 'pending',
                    ];
                }),
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function addCandidates(Request $request, Event $event)
    {
        $validated = $request->validate([
            'candidate_ids' => 'required|array',
            'candidate_ids.*' => 'exists:candidates,id',
        ]);

        $event->candidates()->syncWithoutDetaching($validated['candidate_ids']);

        return response()->json(['message' => 'Candidates added']);
    }

    public function removeCandidate(Event $event, $candidateId)
    {
        $event->candidates()->detach($candidateId);
        EventCandidateCourse::where('event_id', $event->id)
            ->where('candidate_id', $candidateId)
            ->delete();

        return response()->json(['message' => 'Candidate removed']);
    }

    public function attendance(Event $event)
    {
        $courses = $event->courses()->get();
        $candidates = $event->candidates()->get();

        $attendance = $candidates->map(function ($candidate) use ($event, $courses) {
            $records = EventCandidateCourse::where('event_id', $event->id)
                ->where('candidate_id', $candidate->id)
                ->get()
                ->keyBy('course_id');

            $coursesData = [];
            foreach ($courses as $course) {
                $record = $records->get($course->id);
                $coursesData[$course->id] = [
                    'attended' => $record?->attended,
                    'result' => $record?->result ?? 'pending',
                ];
            }

            return [
                'candidate_id' => $candidate->id,
                'candidate_name' => $candidate->full_name,
                'courses' => $coursesData,
            ];
        });

        return response()->json([
            'event' => ['id' => $event->id, 'title' => $event->title],
            'courses' => $courses,
            'attendance' => $attendance,
        ]);
    }

    public function updateAttendance(Request $request, Event $event, $candidateId, $courseId)
    {
        $validated = $request->validate([
            'attended' => 'nullable|in:yes,no,partial',
            'result' => 'nullable|in:pass,fail,pending',
            'notes' => 'nullable|string',
        ]);

        EventCandidateCourse::updateOrCreate(
            [
                'event_id' => $event->id,
                'candidate_id' => $candidateId,
                'course_id' => $courseId,
            ],
            array_merge($validated, ['marked_at' => now()])
        );

        return response()->json(['message' => 'Attendance updated']);
    }
}
