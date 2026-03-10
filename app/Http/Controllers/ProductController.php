<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Product::latest()->get());
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(Product::findOrFail($id));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeRole(['ADMIN', 'MANAGER', 'FINANCE']);

        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'amount' => 'required|integer|min:1',
        ]);

        return response()->json(Product::create($data), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorizeRole(['ADMIN', 'MANAGER', 'FINANCE']);

        $product = Product::findOrFail($id);

        $data = $request->validate([
            'name'   => 'sometimes|string|max:255',
            'amount' => 'sometimes|integer|min:1',
        ]);

        $product->update($data);
        return response()->json($product);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->authorizeRole(['ADMIN', 'MANAGER']);
        Product::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    private function authorizeRole(array $roles): void
    {
        if (!in_array(auth('api')->user()->role, $roles)) {
            abort(403, 'Forbidden: insufficient permissions');
        }
    }
}
