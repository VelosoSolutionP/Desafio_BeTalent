<?php

namespace Database\Seeders;

use App\Models\Gateway;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Users
        $users = [
            ['name' => 'Admin',   'email' => 'admin@payment.com',   'password' => Hash::make('admin123'),   'role' => 'ADMIN'],
            ['name' => 'Manager', 'email' => 'manager@payment.com', 'password' => Hash::make('manager123'), 'role' => 'MANAGER'],
            ['name' => 'Finance', 'email' => 'finance@payment.com', 'password' => Hash::make('finance123'), 'role' => 'FINANCE'],
            ['name' => 'User',    'email' => 'user@payment.com',    'password' => Hash::make('user123'),    'role' => 'USER'],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(['email' => $user['email']], $user);
        }

        // Gateways
        Gateway::updateOrCreate(['name' => 'Gateway1'], ['is_active' => true, 'priority' => 1]);
        Gateway::updateOrCreate(['name' => 'Gateway2'], ['is_active' => true, 'priority' => 2]);

        // Products
        if (Product::count() === 0) {
            Product::insert([
                ['name' => 'Plano Basic',      'amount' => 2990,  'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Plano Pro',        'amount' => 9990,  'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Plano Enterprise', 'amount' => 29990, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        $this->command->info('✅ Seed completed!');
    }
}
