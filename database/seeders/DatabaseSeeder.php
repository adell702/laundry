<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Owner Admin',
            'email' => 'admin@superclean.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Kasir Super Clean',
            'email' => 'kasir@superclean.test',
            'password' => Hash::make('password'),
            'role' => 'kasir',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $services = [
            ['name' => 'Cuci Kiloan', 'unit' => 'kg', 'price' => 7000, 'description' => 'Cuci + kering'],
            ['name' => 'Cuci Setrika', 'unit' => 'kg', 'price' => 10000, 'description' => 'Cuci + setrika'],
            ['name' => 'Setrika Saja', 'unit' => 'kg', 'price' => 5000, 'description' => 'Setrika only'],
            ['name' => 'Bed Cover', 'unit' => 'pcs', 'price' => 25000, 'description' => 'Bed cover satuan'],
            ['name' => 'Selimut', 'unit' => 'pcs', 'price' => 20000, 'description' => 'Selimut satuan'],
            ['name' => 'Boneka', 'unit' => 'pcs', 'price' => 15000, 'description' => 'Cuci boneka'],
            ['name' => 'Sepatu', 'unit' => 'pasang', 'price' => 30000, 'description' => 'Cuci sepatu'],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }

        Customer::create([
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
            'address' => 'Perum Dukuh Zamrud Blok A',
        ]);

        Customer::create([
            'name' => 'Siti Aminah',
            'phone' => '081298765432',
            'address' => 'Padurenan, Bekasi',
        ]);
    }
}
