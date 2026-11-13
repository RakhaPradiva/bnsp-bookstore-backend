<?php

use App\Models\Author;
use App\Models\Book;
use App\Models\Cart;
use App\Models\Genre;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

function createBook(int $stock = 10): Book
{
    return Book::create([
        'title' => 'Book '.uniqid(),
        'description' => 'Description',
        'price' => 50,
        'stock' => $stock,
        'cover_photo' => 'covers/sample.jpg',
        'genre_id' => Genre::create(['name' => 'Genre '.uniqid(), 'description' => 'Description'])->id,
        'author_id' => Author::create(['name' => 'Author '.uniqid(), 'photo' => 'authors/sample.jpg', 'bio' => 'Bio'])->id,
    ]);
}

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

test('creates a transaction from explicit items', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $book = createBook(5);
    $token = JWTAuth::fromUser($customer);

    $this->withToken($token)
        ->postJson('/api/transactions', [
            'items' => [
                ['book_id' => $book->id, 'quantity' => 2],
            ],
            'shipping_address' => 'Sample address',
        ])
        ->assertCreated()
        ->assertJsonPath('data.total_amount', 100)
        ->assertJsonCount(1, 'data.details');

    expect($book->fresh()->stock)->toBe(3);
});

test('creates a transaction from the cart and empties it', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $book = createBook(5);
    Cart::create([
        'customer_id' => $customer->id,
        'book_id' => $book->id,
        'quantity' => 3,
    ]);
    $token = JWTAuth::fromUser($customer);

    $this->withToken($token)
        ->postJson('/api/transactions', [
            'from_cart' => true,
            'shipping_address' => 'Sample address',
        ])
        ->assertCreated()
        ->assertJsonCount(1, 'data.details');

    expect($book->fresh()->stock)->toBe(2);
    $this->assertDatabaseCount('carts', 0);
});

test('rejects a transaction that has neither items nor cart flag', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $token = JWTAuth::fromUser($customer);

    $this->withToken($token)
        ->postJson('/api/transactions', [
            'shipping_address' => 'Sample address',
        ])
        ->assertUnprocessable()
        ->assertJsonStructure(['data' => ['items']]);
});

test('rolls back the whole transaction when stock is insufficient', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    $enough = createBook(10);
    $scarce = createBook(1);
    $token = JWTAuth::fromUser($customer);

    $this->withToken($token)
        ->postJson('/api/transactions', [
            'items' => [
                ['book_id' => $enough->id, 'quantity' => 2],
                ['book_id' => $scarce->id, 'quantity' => 5],
            ],
            'shipping_address' => 'Sample address',
        ])
        ->assertStatus(400);

    expect($enough->fresh()->stock)->toBe(10);
    $this->assertDatabaseCount('transactions', 0);
});
