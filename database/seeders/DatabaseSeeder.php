<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    // Sem WithoutModelEvents: os tokens de QR Code de postos e pontos são
    // gerados no evento "creating" do model.

    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@notreguard.local'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('admin1234'),
                'role' => User::ROLE_ADMIN,
            ],
        );

        User::firstOrCreate(
            ['email' => 'supervisao@notreguard.local'],
            [
                'name' => 'Supervisão',
                'password' => Hash::make('super1234'),
                'role' => User::ROLE_SUPERVISOR,
                'permissions' => [User::PERMISSION_KEYS],
            ],
        );

        $this->call([
            IncidentTypeSeeder::class,
            DemoUnitSeeder::class,
        ]);
    }
}
