<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $query->orderBy('name');

        // Return all records if all=true, otherwise paginate
        if ($request->boolean('all')) {
            return response()->json(['data' => $query->get()]);
        }

        $courses = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => $courses->items(),
            'meta' => [
                'current_page' => $courses->currentPage(),
                'total' => $courses->total(),
                'per_page' => $courses->perPage(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'in:active,inactive',
            'expiry_duration' => 'nullable|integer|min:1',
            'expiry_unit' => 'nullable|in:day,week,month,year',
        ]);

        $course = Course::create($validated);

        return response()->json(['data' => $course, 'message' => 'Course created'], 201);
    }

    public function show(Course $course)
    {
        return response()->json(['data' => $course]);
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'in:active,inactive',
            'expiry_duration' => 'nullable|integer|min:1',
            'expiry_unit' => 'nullable|in:day,week,month,year',
        ]);

        $course->update($validated);

        return response()->json(['data' => $course, 'message' => 'Course updated']);
    }

    public function destroy(Course $course)
    {
        $course->delete();
        return response()->json(['message' => 'Course deleted']);
    }
}
