<?php

namespace App\Http\Controllers;

use App\Models\Gateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GatewayController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Gateway::orderBy('priority')->get());
    }

    public function toggle(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $gateway = Gateway::findOrFail($id);
        $gateway->update($data);

        return response()->json($gateway);
    }

    public function updatePriority(Request $request, int $id): JsonResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'priority' => 'required|integer|min:1',
        ]);

        $conflict = Gateway::where('priority', $data['priority'])
                           ->where('id', '!=', $id)
                           ->exists();

        if ($conflict) {
            return response()->json(['error' => "Priority {$data['priority']} already in use"], 409);
        }

        $gateway = Gateway::findOrFail($id);
        $gateway->update($data);

        return response()->json($gateway);
    }

    private function authorizeAdmin(): void
    {
        if (auth('api')->user()->role !== 'ADMIN') {
            abort(403, 'Forbidden: only ADMIN can manage gateways');
        }
    }
}
