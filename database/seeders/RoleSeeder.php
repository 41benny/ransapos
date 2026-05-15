<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Full access to system',
            ],
            [
                'name' => 'kasir',
                'display_name' => 'Kasir',
                'description' => 'POS operator',
            ],
            [
                'name' => 'manager',
                'display_name' => 'Manager',
                'description' => 'Store manager',
            ],
            [
                'name' => 'kitchen',
                'display_name' => 'Kitchen Staff',
                'description' => 'Hanya akses ke layar dapur/kitchen display',
            ],
            [
                'name' => 'karyawan_outlet',
                'display_name' => 'Karyawan Outlet',
                'description' => 'Karyawan outlet untuk absensi tanpa akses login',
            ],
            [
                'name' => 'superadmin',
                'display_name' => 'Super Admin',
                'description' => 'Akses penuh bawaan sistem',
            ],
            [
                'name' => 'pajak',
                'display_name' => 'Dinas Pajak',
                'description' => 'Akses terbatas untuk petugas Dinas Pendapatan (eksternal). Hanya dapat melihat Laporan Penjualan Harian.',
            ],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(
                ['name' => $role['name']],
                [
                    'display_name' => $role['display_name'],
                    'description' => $role['description'],
                ]
            );
        }
    }
}
