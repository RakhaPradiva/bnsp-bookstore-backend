<?php

namespace Database\Seeders;

use App\Models\Transaction;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        Transaction::create([
            'order_number' => 'ORD-001',
            'customer_id' => 2,
            'total_amount' => 200000.00,
            'order_status' => 'pending',
            'shipping_address' => 'Jl. Sudirman No. 1, Jakarta',
            'notes' => 'Antar siang',
        ]);
        Transaction::create([
            'order_number' => 'ORD-002',
            'customer_id' => 3,
            'total_amount' => 300000.00,
            'order_status' => 'paid',
            'shipping_address' => 'Jl. Thamrin No. 5, Bandung',
            'notes' => null,
        ]);
        Transaction::create([
            'order_number' => 'ORD-003',
            'customer_id' => 3,
            'total_amount' => 60000.00,
            'order_status' => 'shipped',
            'shipping_address' => 'Jl. Gatot Subroto No. 10, Surabaya',
            'notes' => null,
        ]);
        Transaction::create([
            'order_number' => 'ORD-004',
            'customer_id' => 3,
            'total_amount' => 60000.00,
            'order_status' => 'delivered',
            'shipping_address' => 'Jl. Ahmad Yani No. 20, Yogyakarta',
            'notes' => 'Barang sudah diterima',
        ]);
    }
}
