<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Client::select('id','name','email','created_at')->latest()->get()
        );
    }

    public function show(int $id): JsonResponse
    {
        $client = Client::with([
            'transactions' => fn($q) => $q->with(['gateway:id,name', 'products'])->latest()
        ])->findOrFail($id);

        return response()->json($client);
    }
}
