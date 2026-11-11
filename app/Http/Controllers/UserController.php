<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();

        if ($users->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'User data not found!',
                'data' => $users,
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Get All User Data',
            'data' => $users,
        ], 200);
    }

    public function show(string $id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found!',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Get detail user',
            'data' => $user,
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found!',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'role' => 'nullable|string|in:customer,admin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors(),
            ], 422);
        }

        $data = [
            'name' => $request->input('name', $user->name),
            'email' => $request->input('email', $user->email),
            'password' => $request->filled('password') ? Hash::make($request->input('password')) : $user->password,
            'role' => $request->input('role', $user->role)
        ];

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully!',
            'data' => $user,
        ], 200);
    }

    public function destroy(string $id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found!',
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully!',
        ], 200);
    }
}
