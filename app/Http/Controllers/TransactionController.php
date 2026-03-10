<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Gateway;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\Gateways\GatewayRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    // PUBLIC — no auth required
    public function purchase(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_name'  => 'required|string|max:255',
            'client_email' => 'required|email',
            'card_number'  => 'required|string|size:16',
            'cvv'          => 'required|string',
            'items'        => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        // 1. Calculate total from products
        $productIds = collect($data['items'])->pluck('product_id');
        $products   = Product::whereIn('id', $productIds)->get()->keyBy('id');

        $total = collect($data['items'])->reduce(function (int $sum, array $item) use ($products) {
            return $sum + ($products[$item['product_id']]->amount * $item['quantity']);
        }, 0);

        // 2. Upsert client
        $client = Client::updateOrCreate(
            ['email' => $data['client_email']],
            ['name'  => $data['client_name']]
        );

        // 3. Try gateways in priority order
        $gateways = Gateway::where('is_active', true)->orderBy('priority')->get();

        if ($gateways->isEmpty()) {
            return response()->json(['error' => 'No active payment gateways'], 503);
        }

        $lastError = '';

        foreach ($gateways as $gateway) {
            try {
                $adapter = GatewayRegistry::get($gateway->name);
                $result  = $adapter->charge([
                    'amount'     => $total,
                    'name'       => $client->name,
                    'email'      => $client->email,
                    'cardNumber' => $data['card_number'],
                    'cvv'        => $data['cvv'],
                ]);

                // 4. Persist transaction
                $transaction = DB::transaction(function () use ($client, $gateway, $result, $total, $data) {
                    $tx = Transaction::create([
                        'client_id'        => $client->id,
                        'gateway_id'       => $gateway->id,
                        'external_id'      => $result['external_id'],
                        'status'           => 'APPROVED',
                        'amount'           => $total,
                        'card_last_numbers' => $result['card_last_numbers'],
                    ]);

                    $pivot = collect($data['items'])->mapWithKeys(fn($i) => [
                        $i['product_id'] => ['quantity' => $i['quantity']]
                    ])->toArray();

                    $tx->products()->attach($pivot);
                    return $tx;
                });

                $transaction->load(['client', 'gateway:id,name', 'products']);
                return response()->json($transaction, 201);

            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                // try next gateway
            }
        }

        return response()->json(['error' => "Payment failed: {$lastError}"], 502);
    }

    public function index(): JsonResponse
    {
        $transactions = Transaction::with(['client:id,name,email', 'gateway:id,name', 'products'])
            ->latest()->get();

        return response()->json($transactions);
    }

    public function show(int $id): JsonResponse
    {
        $transaction = Transaction::with(['client', 'gateway:id,name', 'products'])
            ->findOrFail($id);

        return response()->json($transaction);
    }

    public function refund(int $id): JsonResponse
    {
        $user = auth('api')->user();

        if (!in_array($user->role, ['ADMIN', 'FINANCE'])) {
            return response()->json(['error' => 'Forbidden: insufficient permissions'], 403);
        }

        $transaction = Transaction::with('gateway')->findOrFail($id);

        if ($transaction->status === 'REFUNDED') {
            return response()->json(['error' => 'Transaction already refunded'], 409);
        }

        if ($transaction->status !== 'APPROVED') {
            return response()->json(['error' => 'Only approved transactions can be refunded'], 422);
        }

        $adapter = GatewayRegistry::get($transaction->gateway->name);
        $adapter->refund($transaction->external_id);

        $transaction->update(['status' => 'REFUNDED']);

        return response()->json($transaction);
    }
}
