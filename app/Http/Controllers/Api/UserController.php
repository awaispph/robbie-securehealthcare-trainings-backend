<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $query->orderBy('name');

        // Return all records if all=true, otherwise paginate
        if ($request->boolean('all')) {
            return response()->json(['data' => $query->get()]);
        }

        $users = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'total' => $users->total(),
                'per_page' => $users->perPage(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        // Check if a soft-deleted user exists with this email
        $existingUser = User::withTrashed()->where('email', $request->email)->first();
        
        if ($existingUser && $existingUser->trashed()) {
            // Restore and update the soft-deleted user
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email',
                'password' => 'required|string|min:6',
                'phone' => 'nullable|string|max:50',
                'role_type' => 'in:admin,manager,staff',
                'status' => 'in:0,1',
            ]);

            $existingUser->restore();
            $existingUser->update([
                'name' => $request->name,
                'password' => Hash::make($request->password),
                'phone' => $request->phone ?? null,
                'role_type' => $request->role_type ?? 'admin',
                'status' => $request->status ?? 1,
            ]);

            return response()->json(['data' => $existingUser, 'message' => 'User restored and updated'], 201);
        }

        // Check for active user with same email
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:50',
            'role_type' => 'in:admin,manager,staff',
            'status' => 'in:0,1',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'role_type' => $validated['role_type'] ?? 'admin',
            'status' => $validated['status'] ?? 1,
        ]);

        return response()->json(['data' => $user, 'message' => 'User created'], 201);
    }

    public function show(User $user)
    {
        return response()->json(['data' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'phone' => 'nullable|string|max:50',
            'role_type' => 'in:admin,manager,staff',
            'status' => 'in:0,1',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role_type' => $validated['role_type'] ?? $user->role_type,
            'status' => $validated['status'] ?? $user->status,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return response()->json(['data' => $user, 'message' => 'User updated']);
    }

    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth('api')->id()) {
            return response()->json(['message' => 'Cannot delete your own account'], 422);
        }

        $user->delete();
        return response()->json(['message' => 'User deleted']);
    }
}
