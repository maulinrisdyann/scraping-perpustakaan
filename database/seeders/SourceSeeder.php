<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sources = [
            ['name' => 'google_maps', 'label' => 'Google Maps'],
            ['name' => 'instagram', 'label' => 'Instagram'],
            ['name' => 'facebook', 'label' => 'Facebook'],
            ['name' => 'monggo_lapor', 'label' => 'Monggo Lapor'],
        ];

        foreach ($sources as $source) {
            DB::table('sources')->updateOrInsert(
                ['name' => $source['name']],
                ['label' => $source['label'], 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}
