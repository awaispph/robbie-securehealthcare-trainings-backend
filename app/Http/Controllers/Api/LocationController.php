<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $query = Location::query();

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

        $locations = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => $locations->items(),
            'meta' => [
                'current_page' => $locations->currentPage(),
                'total' => $locations->total(),
                'per_page' => $locations->perPage(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'status' => 'in:active,inactive',
        ]);

        $location = Location::create($validated);

        return response()->json(['data' => $location, 'message' => 'Location created'], 201);
    }

    public function show(Location $location)
    {
        return response()->json(['data' => $location]);
    }

    public function update(Request $request, Location $location)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'status' => 'in:active,inactive',
        ]);

        $location->update($validated);

        return response()->json(['data' => $location, 'message' => 'Location updated']);
    }

    public function destroy(Location $location)
    {
        $location->delete();
        return response()->json(['message' => 'Location deleted']);
    }
}
