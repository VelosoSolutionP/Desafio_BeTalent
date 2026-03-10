<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['ADMIN', 'MANAGER', 'FINANCE', 'USER'])->default('USER');
            $table->timestamps();
        });

        Schema::create('gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->unique();
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('amount'); // in cents
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gateway_id')->constrained();
            $table->string('external_id');
            $table->enum('status', ['PENDING', 'APPROVED', 'REFUNDED', 'FAILED'])->default('PENDING');
            $table->unsignedInteger('amount'); // in cents
            $table->string('card_last_numbers', 4);
            $table->timestamps();
        });

        Schema::create('transaction_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->unsignedInteger('quantity')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_products');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('products');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('gateways');
        Schema::dropIfExists('users');
    }
};
