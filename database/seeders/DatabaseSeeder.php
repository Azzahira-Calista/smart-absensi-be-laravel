<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema; // Tambahkan ini
use App\Models\Attendance;
use App\Models\Location;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Matikan sementara pengecekan foreign key agar truncate aman
        Schema::disableForeignKeyConstraints();

        Attendance::truncate();
        Location::truncate();
        User::truncate();

        Schema::enableForeignKeyConstraints();

        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'satuan_kerja' => 'IT Development / Software Engineer',
            'status_magang' => 'Aktif magang',
        ]);

        $this->call([
            // Seeder user/login kamu yang lama taruh di sini (jika ada)
            LocationSeeder::class,
        ]);
    }
}