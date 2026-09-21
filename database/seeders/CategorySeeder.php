<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disusun berdasarkan pola keluhan yang muncul di data ulasan asli
        // (pelayanan pegawai, fasilitas wifi/toilet, koleksi buku, jam buka, aturan).
        $categories = [
            'Pelayanan',
            'Fasilitas',
            'Koleksi Buku',
            'Jam Operasional',
            'Kebijakan & Aturan',
            'Lainnya',
        ];

        foreach ($categories as $name) {
            DB::table('categories')->updateOrInsert(
                ['name' => $name],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
