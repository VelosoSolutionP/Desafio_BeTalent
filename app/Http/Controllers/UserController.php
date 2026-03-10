<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorizeRole(['ADMIN', 'MANAGER']);
        return response()->json(User::select('id','name','email','role','created_at')->latest()->get());
    }

    public function show(int $id): JsonResponse
    {
        $this->authorizeRole(['ADMIN', 'MANAGER']);
        return response()->json(User::select('id','name','email','role','created_at')->findOrFail($id));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeRole(['ADMIN']);

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'role'     => ['required', Rule::in(User::ROLES)],
        ]);

        $user = User::create([...$data, 'password' => Hash::make($data['password'])]);

        return response()->json($user->only('id','name','email','role'), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorizeRole(['ADMIN', 'MANAGER']);

        $user = User::findOrFail($id);
        $requester = auth('api')->user();

        $rules = [
            'name'  => 'sometimes|string|max:255',
            'email' => ['sometimes','email', Rule::unique('users')->ignore($id)],
            'password' => 'sometimes|string|min:6',
        ];

        // Only ADMIN can change roles
        if ($requester->role === 'ADMIN') {
            $rules['role'] = ['sometimes', Rule::in(User::ROLES)];
        }

        $data = $request->validate($rules);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return response()->json($user->only('id','name','email','role'));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->authorizeRole(['ADMIN']);
        User::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    private function authorizeRole(array $roles): void
    {
        $user = auth('api')->user();
        if (!in_array($user->role, $roles)) {
            abort(403, 'Forbidden: insufficient permissions');
        }
    }
}
