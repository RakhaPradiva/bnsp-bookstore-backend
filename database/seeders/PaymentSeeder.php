<?php

namespace Database\Seeders;

use App\Models\Payment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Payment::create([
            'transaction_id' => 1,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'pending',
            'payment_proof' => 'proof_001.jpg',
        ]);
        Payment::create([
            'transaction_id' => 2,
            'payment_method' => 'e-wallet',
            'payment_status' => 'paid',
            'payment_proof' => 'proof_002.png',
        ]);
        Payment::create([
            'transaction_id' => 3,
            'payment_method' => 'credit_card',
            'payment_status' => 'paid',
            'payment_proof' => null,
        ]);
        Payment::create([
            'transaction_id' => 4,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'payment_proof' => null,
        ]);
    }
}
