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
            'subject' => 'Pertanyaan tentang pengiriman',
            'message' => 'Halo, saya ingin menanyakan estimasi waktu pengiriman untuk pesanan saya ke daerah Jakarta Selatan. Terima kasih.',
            'status' => 'replied',
        ]);

        ContactMessage::create([
            'customer_id' => 3,
            'subject' => 'Buku rusak saat diterima',
            'message' => 'Buku yang saya terima ada halaman yang sobek. Apakah bisa ditukar dengan yang baru?',
            'status' => 'read',
        ]);

        ContactMessage::create([
            'customer_id' => 4,
            'subject' => 'Rekomendasi buku fiksi',
            'message' => 'Apakah ada rekomendasi buku fiksi terbaru yang tersedia di toko ini? Saya mencari genre fantasi.',
            'status' => 'unread',
        ]);

        ContactMessage::create([
            'customer_id' => 5,
            'subject' => 'Pembatalan pesanan',
            'message' => 'Saya ingin membatalkan pesanan terakhir saya karena ada perubahan rencana. Mohon bantuannya.',
            'status' => 'read',
        ]);

        ContactMessage::create([
            'customer_id' => null,
            'subject' => 'Jam operasional toko',
            'message' => 'Permisi, saya ingin bertanya mengenai jam operasional toko offline. Apakah buka di hari Sabtu dan Minggu?',
            'status' => 'unread',
        ]);
    }
}
