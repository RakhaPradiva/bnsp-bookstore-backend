<?php

namespace Database\Seeders;

use App\Models\TransactionDetail;
use Illuminate\Database\Seeder;

class TransactionDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TransactionDetail::create([
            'transaction_id' => 1,
            'book_id' => 3,
            'quantity' => 4,
            'price' => 50000,
            'subtotal' => 200000,
        ]);

        TransactionDetail::create([
            'transaction_id' => 2,
            'book_id' => 3,
            'quantity' => 6,
            'price' => 50000,
            'subtotal' => 300000,
        ]);

        TransactionDetail::create([
            'transaction_id' => 3,
            'book_id' => 3,
            'quantity' => 1,
            'price' => 60000,
            'subtotal' => 60000,
        ]);

        TransactionDetail::create([
            'transaction_id' => 4,
            'book_id' => 3,
            'quantity' => 1,
            'price' => 60000,
            'subtotal' => 60000,
        ]);
    }
}
