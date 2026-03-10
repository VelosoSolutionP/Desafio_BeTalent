<?php

namespace App\Services\Gateways;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class Gateway2 implements GatewayInterface
{
    private string $baseUrl;
    private array $headers;

    public function __construct()
    {
        $this->baseUrl = config('services.gateway2.url');
        $this->headers = [
            'Gateway-Auth-Token'  => config('services.gateway2.auth_token'),
            'Gateway-Auth-Secret' => config('services.gateway2.auth_secret'),
        ];
    }

    public function charge(array $data): array
    {
        $response = Http::withHeaders($this->headers)->post("{$this->baseUrl}/transacoes", [
            'valor'        => $data['amount'],
            'nome'         => $data['name'],
            'email'        => $data['email'],
            'numeroCartao' => $data['cardNumber'],
            'cvv'          => $data['cvv'],
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('Gateway2: ' . ($response->json('message') ?? 'charge failed'));
        }

        return [
            'external_id'       => $response->json('id'),
            'card_last_numbers' => substr($data['cardNumber'], -4),
        ];
    }

    public function refund(string $externalId): void
    {
        $response = Http::withHeaders($this->headers)->post("{$this->baseUrl}/transacoes/reembolso", [
            'id' => $externalId,
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('Gateway2 refund: ' . ($response->json('message') ?? 'refund failed'));
        }
    }
}
