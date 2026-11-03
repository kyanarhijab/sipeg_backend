<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            [
                'name'        => 'Dashboard',
                'route'       => '/dashboard',
                'icon'        => 'fa-solid fa-chart-pie',
                'order_index' => 1,
                'is_active'   => 1,
            ],
            [
                'name'        => 'Master Karyawan',
                'route'       => '/karyawan',
                'icon'        => 'fa-solid fa-users',
                'order_index' => 2,
                'is_active'   => 1,
            ],
            [
                'name'        => 'Transaksi Gaji',
                'route'       => '/transaksi',
                'icon'        => 'fa-solid fa-money-bill-wave',
                'order_index' => 3,
                'is_active'   => 1,
            ],
            [
                'name'        => 'Laporan',
                'route'       => '/laporan',
                'icon'        => 'fa-solid fa-file-invoice-dollar',
                'order_index' => 4,
                'is_active'   => 1,
            ],
        ];

        foreach ($menus as $m) {
            DB::table('menus')->updateOrInsert(
                ['route' => $m['route']],
                array_merge($m, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}