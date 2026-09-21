<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TrackedUrlSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sourceId = DB::table('sources')->where('name', 'google_maps')->value('id');

        if (! $sourceId) {
            return;
        }

        DB::table('tracked_urls')->updateOrInsert(
            ['place_identifier' => '0x2e7a8fdbd0a57879:0x303f20266a7c53a2'],
            [
                'source_id' => $sourceId,
                'label' => 'Perpustakaan Daerah Kota Magelang',
                'search_query' => 'Perpustakaan Daerah Kota Magelang',
                'url' => 'https://maps.app.goo.gl/AhdETuoNUfwYfS8U8',
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
