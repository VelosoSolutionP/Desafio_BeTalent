<?php

namespace App\Services\Gateways;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class Gateway1 implements GatewayInterface
{
    private string $baseUrl;
    private ?string $token = null;

    public function __construct()
    {
        $this->baseUrl = config('services.gateway1.url');
    }

    private function getToken(): string
    {
        if ($this->token) return $this->token;

        $response = Http::post("{$this->baseUrl}/login", [
            'email' => config('services.gateway1.email'),
            'token' => config('services.gateway1.token'),
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('Gateway1: authentication failed');
        }

        $this->token = $response->json('token');
        return $this->token;
    }

    public function charge(array $data): array
    {
        $token = $this->getToken();

        $response = Http::withToken($token)->post("{$this->baseUrl}/transactions", [
            'amount'     => $data['amount'],
            'name'       => $data['name'],
            'email'      => $data['email'],
            'cardNumber' => $data['cardNumber'],
            'cvv'        => $data['cvv'],
        ]);

        if (!$response->successful()) {
            $this->token = null;
            throw new RuntimeException('Gateway1: ' . ($response->json('message') ?? 'charge failed'));
        }

        return [
            'external_id'      => $response->json('id'),
            'card_last_numbers' => substr($data['cardNumber'], -4),
        ];
    }

    public function refund(string $externalId): void
    {
        $token = $this->getToken();

        $response = Http::withToken($token)->post("{$this->baseUrl}/transactions/{$externalId}/charge_back");

        if (!$response->successful()) {
            $this->token = null;
            throw new RuntimeException('Gateway1 refund: ' . ($response->json('message') ?? 'refund failed'));
        }
    }
}
