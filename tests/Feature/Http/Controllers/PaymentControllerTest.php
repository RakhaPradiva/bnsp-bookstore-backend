<?php

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('bank transfer payment requires proof', function () {
    $customer = User::factory()->create();
    $transaction = Transaction::create([
        'order_number' => 'ORD-TRANSFER-1',
        'customer_id' => $customer->id,
        'total_amount' => 25,
        'order_status' => 'pending',
        'shipping_address' => 'Sample address',
    ]);

    $this->actingAs($customer, 'api')
        ->postJson('/api/payments', [
            'transaction_id' => $transaction->id,
            'payment_method' => 'bank_transfer',
        ])
        ->assertUnprocessable()
        ->assertJsonPath(
            'data.payment_proof.0',
            'The payment proof field is required unless payment method is in cod.',
        );

    $this->assertDatabaseMissing('payments', [
        'transaction_id' => $transaction->id,
    ]);
});

test('cod payment does not require proof', function () {
    $customer = User::factory()->create();
    $transaction = Transaction::create([
        'order_number' => 'ORD-COD-1',
        'customer_id' => $customer->id,
        'total_amount' => 25,
        'order_status' => 'pending',
        'shipping_address' => 'Sample address',
    ]);

    $this->actingAs($customer, 'api')
        ->postJson('/api/payments', [
            'transaction_id' => $transaction->id,
            'payment_method' => 'cod',
        ])
        ->assertCreated()
        ->assertJsonPath('data.payment_method', 'cod')
        ->assertJsonPath('data.payment_proof', null);

    $this->assertDatabaseHas('payments', [
        'transaction_id' => $transaction->id,
        'payment_method' => 'cod',
        'payment_proof' => null,
    ]);
});

test('bank transfer accepts a valid proof image', function () {
    Storage::fake('public');

    $customer = User::factory()->create();
    $transaction = Transaction::create([
        'order_number' => 'ORD-TRANSFER-2',
        'customer_id' => $customer->id,
        'total_amount' => 25,
        'order_status' => 'pending',
        'shipping_address' => 'Sample address',
    ]);

    $response = $this->actingAs($customer, 'api')
        ->post('/api/payments', [
            'transaction_id' => $transaction->id,
            'payment_method' => 'bank_transfer',
            'payment_proof' => UploadedFile::fake()->createWithContent(
                'proof.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jf5sAAAAASUVORK5CYII='),
            ),
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.payment_method', 'bank_transfer');

    $this->assertDatabaseHas('payments', [
        'transaction_id' => $transaction->id,
        'payment_method' => 'bank_transfer',
    ]);
});
