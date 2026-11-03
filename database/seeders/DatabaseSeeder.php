<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use App\Models\Employee;
use App\Models\SalaryTransaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Akun Login Admin HRD
        User::updateOrCreate(
            ['employee_code' => 'EMP-1001'],
            [
                'name'     => 'Admin HRD',
                'password' => Hash::make('password123'),
            ]
        );

        // 2. Data Master Departemen
        $deptProduksi = Department::create([
            'department_code' => 'DPT-001',
            'name'            => 'Produksi',
            'sub_dept'        => 'Assembling',
        ]);

        $deptKeuangan = Department::create([
            'department_code' => 'DPT-002',
            'name'            => 'Keuangan',
            'sub_dept'        => 'Akunting',
        ]);

        $deptIT = Department::create([
            'department_code' => 'DPT-003',
            'name'            => 'IT',
            'sub_dept'        => 'Jaringan',
        ]);

        // 3. Data Master Karyawan
        $emp1 = Employee::create([
            'employee_code' => 'EMP-1001',
            'name'          => 'Budi Santoso',
            'department_id' => $deptProduksi->id,
            'position'      => 'Staff',
            'basic_salary'  => 4500000,
            'allowance'     => 500000,
        ]);

        $emp2 = Employee::create([
            'employee_code' => 'EMP-1002',
            'name'          => 'Siti Aminah',
            'department_id' => $deptKeuangan->id,
            'position'      => 'Supervisor',
            'basic_salary'  => 6000000,
            'allowance'     => 1000000,
        ]);

        $emp3 = Employee::create([
            'employee_code' => 'EMP-1003',
            'name'          => 'Andi Wijaya',
            'department_id' => $deptIT->id,
            'position'      => 'Staff',
            'basic_salary'  => 5500000,
            'allowance'     => 750000,
        ]);

        // 4. Data Transaksi Penggajian (Sertakan transaction_code dan net_salary)
        SalaryTransaction::create([
            'transaction_code' => 'SLR-202610-0001',
            'employee_id'      => $emp1->id,
            'month'            => 10,
            'year'             => 2026,
            'basic_salary'     => $emp1->basic_salary,
            'allowance'        => $emp1->allowance,
            'deduction'        => 100000,
            'net_salary'       => ($emp1->basic_salary + $emp1->allowance) - 100000,
            'status'           => 'paid',
        ]);

        SalaryTransaction::create([
            'transaction_code' => 'SLR-202610-0002',
            'employee_id'      => $emp2->id,
            'month'            => 10,
            'year'             => 2026,
            'basic_salary'     => $emp2->basic_salary,
            'allowance'        => $emp2->allowance,
            'deduction'        => 0,
            'net_salary'       => ($emp2->basic_salary + $emp2->allowance) - 0,
            'status'           => 'draft',
        ]);

        SalaryTransaction::create([
            'transaction_code' => 'SLR-202610-0003',
            'employee_id'      => $emp3->id,
            'month'            => 10,
            'year'             => 2026,
            'basic_salary'     => $emp3->basic_salary,
            'allowance'        => $emp3->allowance,
            'deduction'        => 50000,
            'net_salary'       => ($emp3->basic_salary + $emp3->allowance) - 50000,
            'status'           => 'paid',
        ]);

        // Data Navigasi Menu Dinamis
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
            \Illuminate\Support\Facades\DB::table('menus')->updateOrInsert(
                ['route' => $m['route']],
                array_merge($m, ['created_at' => now(), 'updated_at' => now()])
            );
        }
        
    }
}