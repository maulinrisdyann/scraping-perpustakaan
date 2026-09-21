<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'role' => 'superadmin',
                'email_verified_at' => now(),
            ]
        );

        $this->call([
            SourceSeeder::class,
            TrackedUrlSeeder::class,
            CategorySeeder::class,
        ]);
    }
}
