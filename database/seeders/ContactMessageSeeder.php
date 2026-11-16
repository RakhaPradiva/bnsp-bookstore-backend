<?php

namespace Database\Seeders;

use App\Models\ContactMessage;
use Illuminate\Database\Seeder;

class ContactMessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ContactMessage::create([
            'customer_id' => 2,
            'name' => 'Customer',
            'email' => 'customer@booksales.com',
            'subject' => 'Pertanyaan tentang pengiriman',
            'message' => 'Halo, saya ingin menanyakan estimasi waktu pengiriman untuk pesanan saya ke daerah Jakarta Selatan. Terima kasih.',

        ]);

        ContactMessage::create([
            'customer_id' => 3,
            'name' => 'Customer 2',
            'email' => 'customer2@booksales.com',
            'subject' => 'Buku rusak saat diterima',
            'message' => 'Buku yang saya terima ada halaman yang sobek. Apakah bisa ditukar dengan yang baru?',

        ]);
    }
}
