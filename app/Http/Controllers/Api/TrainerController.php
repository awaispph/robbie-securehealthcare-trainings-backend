<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trainer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TrainerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Trainer::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $trainers = $query->orderBy('name')->get();

        return response()->json(['data' => $trainers]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'status' => 'in:active,inactive',
        ]);

        $trainer = Trainer::create($validated);

        return response()->json([
            'message' => 'Trainer created successfully',
            'data' => $trainer
        ], 201);
    }

    public function show(Trainer $trainer): JsonResponse
    {
        return response()->json(['data' => $trainer]);
    }

    public function update(Request $request, Trainer $trainer): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'status' => 'in:active,inactive',
        ]);

        $trainer->update($validated);

        return response()->json([
            'message' => 'Trainer updated successfully',
            'data' => $trainer
        ]);
    }

    public function destroy(Trainer $trainer): JsonResponse
    {
        if ($trainer->hasEvents()) {
            return response()->json([
                'message' => 'Cannot delete trainer. This trainer is assigned to one or more events.'
            ], 422);
        }

        $trainer->delete();

        return response()->json([
            'message' => 'Trainer deleted successfully'
        ]);
    }
}
