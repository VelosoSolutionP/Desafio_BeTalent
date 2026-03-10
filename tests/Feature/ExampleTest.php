<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_login_returns_token(): void
    {
        $response = $this->postJson('/api/login', [
            'email'    => 'admin@payment.com',
            'password' => 'admin123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'type', 'user']);
    }

    public function test_login_with_wrong_password_returns_401(): void
    {
        $response = $this->postJson('/api/login', [
            'email'    => 'admin@payment.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    public function test_unauthenticated_access_returns_401(): void
    {
        $response = $this->getJson('/api/users');
        $response->assertStatus(401);
    }

    public function test_products_list_requires_auth(): void
    {
        $response = $this->getJson('/api/products');
        $response->assertStatus(401);
    }
}
