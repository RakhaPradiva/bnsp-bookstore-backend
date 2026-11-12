<?php

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

test('allows admins to update an order status', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $transaction = Transaction::create([
        'order_number' => 'ORD-STATUS-1',
        'customer_id' => $admin->id,
        'total_amount' => 25,
        'order_status' => 'pending',
        'shipping_address' => 'Sample address',
    ]);

    $token = JWTAuth::fromUser($admin);

    $this->withToken($token)
        ->patchJson('/api/transactions/'.$transaction->id, [
            'order_status' => 'shipped',
        ])
        ->assertOk()
        ->assertJsonPath('data.order_status', 'shipped');

    $this->assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'order_status' => 'shipped',
    ]);
});

test('rejects order status updates with 401 when no token is provided', function () {
    $customer = User::factory()->create();
    $transaction = Transaction::create([
        'order_number' => 'ORD-STATUS-2',
        'customer_id' => $customer->id,
        'total_amount' => 25,
        'order_status' => 'pending',
        'shipping_address' => 'Sample address',
    ]);

    $this->patchJson('/api/transactions/'.$transaction->id, [
        'order_status' => 'shipped',
    ])->assertUnauthorized();
});

test('rejects order status updates with 403 for customers', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $transaction = Transaction::create([
        'order_number' => 'ORD-STATUS-3',
        'customer_id' => $customer->id,
        'total_amount' => 25,
        'order_status' => 'pending',
        'shipping_address' => 'Sample address',
    ]);

    $token = JWTAuth::fromUser($customer);

    $this->withToken($token)
        ->patchJson('/api/transactions/'.$transaction->id, [
            'order_status' => 'shipped',
        ])
        ->assertForbidden();

    $this->assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'order_status' => 'pending',
    ]);
});

test('rejects unsupported order statuses with 422', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $transaction = Transaction::create([
        'order_number' => 'ORD-STATUS-4',
        'customer_id' => $admin->id,
        'total_amount' => 25,
        'order_status' => 'pending',
        'shipping_address' => 'Sample address',
    ]);

    $token = JWTAuth::fromUser($admin);

    $this->withToken($token)
        ->patchJson('/api/transactions/'.$transaction->id, [
            'order_status' => 'unknown',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('data.order_status.0', 'The selected order status is invalid.');

    $this->assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'order_status' => 'pending',
    ]);
});
