<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use Illuminate\Http\Request;

class CandidateController extends Controller
{
    public function index(Request $request)
    {
        $query = Candidate::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }

        $query->orderBy('first_name');

        // Return all records without pagination
        if ($request->has('all')) {
            return response()->json(['data' => $query->get()]);
        }

        $candidates = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => $candidates->items(),
            'meta' => [
                'current_page' => $candidates->currentPage(),
                'total' => $candidates->total(),
                'per_page' => $candidates->perPage(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:candidates,email',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'status' => 'in:active,inactive',
        ]);

        $candidate = Candidate::create($validated);

        return response()->json(['data' => $candidate, 'message' => 'Candidate created'], 201);
    }

    public function show(Candidate $candidate)
    {
        $candidate->load('events');
        return response()->json(['data' => $candidate]);
    }

    public function update(Request $request, Candidate $candidate)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:candidates,email,' . $candidate->id,
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'status' => 'in:active,inactive',
        ]);

        $candidate->update($validated);

        return response()->json(['data' => $candidate, 'message' => 'Candidate updated']);
    }

    public function destroy(Candidate $candidate)
    {
        // Check if candidate attended any past events (end_date in the past AND has attendance marked as yes)
        // Status doesn't matter - only check if event has ended and candidate attended
        $attendedPastEvents = $candidate->events()
            ->where('end_date', '<', now()->toDateString())
            ->whereHas('attendance', function ($query) use ($candidate) {
                $query->where('candidate_id', $candidate->id)
                    ->where('attended', 'yes');
            })
            ->exists();

        if ($attendedPastEvents) {
            return response()->json([
                'message' => 'Cannot delete candidate. This candidate has attended one or more past events.'
            ], 422);
        }

        $candidate->delete();
        return response()->json(['message' => 'Candidate deleted']);
    }
}
