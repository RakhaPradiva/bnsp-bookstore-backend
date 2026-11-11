<?php

namespace Database\Seeders;

use App\Models\Cart;
use Illuminate\Database\Seeder;

class CartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Cart::create([
            'customer_id' => 2,
            'book_id' => 1,
            'quantity' => 2,
        ]);
        Cart::create([
            'customer_id' => 3,
            'book_id' => 2,
            'quantity' => 1,
        ]);
        Cart::create([
            'customer_id' => 4,
            'book_id' => 3,
            'quantity' => 3,
        ]);
        Cart::create([
            'customer_id' => 5,
            'book_id' => 4,
            'quantity' => 1,
        ]);
        Cart::create([
            'customer_id' => 2,
            'book_id' => 5,
            'quantity' => 4,
        ]);
    }
}
