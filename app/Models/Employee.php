<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_code',
        'name',
        'department_id',
        'position',
        'basic_salary',
        'allowance',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'allowance'    => 'decimal:2',
    ];

    /* =========================================================================
     * 1. RELASI ELOQUENT
     * ========================================================================= */

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function salaryTransactions()
    {
        return $this->hasMany(SalaryTransaction::class, 'employee_id', 'id');
    }

    /* =========================================================================
     * 2. RAW QUERY METHODS (MODULAR DATABASE LOGIC)
     * ========================================================================= */

    /**
     * Generate otomatis kode karyawan berikutnya: EMP-1001, EMP-1002, dst.
     */
    public static function generateCode(): string
    {
        $latest = DB::select("SELECT employee_code FROM employees ORDER BY id DESC LIMIT 1");
        $nextId = 1001;

        if (!empty($latest) && preg_match('/EMP-(\d+)/', $latest[0]->employee_code, $matches)) {
            $nextId = ((int) $matches[1]) + 1;
        }

        return 'EMP-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Ambil data karyawan dengan paginasi, pencarian, dan filter departemen
     */
    public static function getPaginatedList(?string $search = null, $departmentId = null, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        $conditions = ["1=1"];
        $bindings = [];

        if (!empty($search)) {
            $conditions[] = "(e.name LIKE ? OR e.employee_code LIKE ?)";
            $bindings[] = "%{$search}%";
            $bindings[] = "%{$search}%";
        }

        if (!empty($departmentId) && $departmentId !== 'all') {
            $conditions[] = "e.department_id = ?";
            $bindings[] = (int) $departmentId;
        }

        $whereClause = implode(" AND ", $conditions);

        // Hitung total records
        $countResult = DB::select("SELECT COUNT(*) AS total FROM employees e WHERE {$whereClause}", $bindings);
        $total = (int) ($countResult[0]->total ?? 0);

        // Ambil data halaman aktif
        $dataBindings = array_merge($bindings, [$perPage, $offset]);
        $rows = DB::select("
            SELECT 
                e.id,
                e.employee_code,
                e.name,
                e.position,
                e.basic_salary,
                e.allowance,
                e.department_id,
                d.department_code,
                d.name AS department_name,
                d.sub_dept,
                CONCAT(d.name, IF(d.sub_dept IS NOT NULL AND d.sub_dept != '', CONCAT(' - ', d.sub_dept), '')) AS full_department_name,
                e.created_at
            FROM employees e
            JOIN departments d ON d.id = e.department_id
            WHERE {$whereClause}
            ORDER BY e.id DESC
            LIMIT ? OFFSET ?
        ", $dataBindings);

        return [
            'data'         => $rows,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / ($perPage > 0 ? $perPage : 10)),
        ];
    }

    /**
     * Ambil detail satu karyawan berdasarkan ID
     */
    public static function findById(int $id)
    {
        $result = DB::select("
            SELECT 
                e.id,
                e.employee_code,
                e.name,
                e.position,
                e.basic_salary,
                e.allowance,
                e.department_id,
                d.department_code,
                d.name AS department_name,
                d.sub_dept,
                CONCAT(d.name, IF(d.sub_dept IS NOT NULL AND d.sub_dept != '', CONCAT(' - ', d.sub_dept), '')) AS full_department_name
            FROM employees e
            JOIN departments d ON d.id = e.department_id
            WHERE e.id = ?
            LIMIT 1
        ", [$id]);

        return $result ? $result[0] : null;
    }

    /**
     * Tambah karyawan baru (Raw Insert)
     */
    public static function createRaw(array $data)
    {
        $code = !empty($data['employee_code']) ? $data['employee_code'] : self::generateCode();
        $now = now();

        DB::insert("
            INSERT INTO employees (employee_code, name, department_id, position, basic_salary, allowance, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $code,
            $data['name'],
            $data['department_id'],
            $data['position'],
            $data['basic_salary'],
            $data['allowance'] ?? 0,
            $now,
            $now,
        ]);

        return self::findById((int) DB::getPdo()->lastInsertId());
    }

    /**
     * Update data karyawan (Raw Update)
     */
    public static function updateRaw(int $id, array $data)
    {
        return DB::update("
            UPDATE employees
            SET employee_code = ?, 
                name = ?, 
                department_id = ?, 
                position = ?, 
                basic_salary = ?, 
                allowance = ?, 
                updated_at = ?
            WHERE id = ?
        ", [
            $data['employee_code'],
            $data['name'],
            $data['department_id'],
            $data['position'],
            $data['basic_salary'],
            $data['allowance'] ?? 0,
            now(),
            $id,
        ]);
    }

    /**
     * Hapus data karyawan (Raw Delete)
     */
    public static function deleteRaw(int $id)
    {
        return DB::delete("DELETE FROM employees WHERE id = ?", [$id]);
    }

    /**
     * Import Karyawan Massal dari File CSV/Excel dengan Kode Departemen
     */
    public static function importRawRows(array $rows): int
    {
        $count = 0;
        $now = now();

        foreach ($rows as $row) {
            $name         = trim($row['name']);
            $deptInput    = trim($row['department_code']); // Bisa berisi 'DPT-001' atau nama departemen
            $position     = trim($row['position'] ?? 'Staff');
            $basicSalary  = (float) ($row['basic_salary'] ?? 0);
            $allowance    = (float) ($row['allowance'] ?? 0);

            if (empty($name)) continue;

            // 1. Cari departemen berdasarkan kode unik (DPT-xxx)
            $dept = DB::select("
                SELECT id FROM departments 
                WHERE department_code = ? 
                LIMIT 1
            ", [$deptInput]);

            // 2. Jika tidak ditemukan via kode, cari via nama / format gabungan
            if (empty($dept)) {
                $dept = DB::select("
                    SELECT id FROM departments 
                    WHERE name = ? OR CONCAT(name, ' - ', sub_dept) = ? 
                    LIMIT 1
                ", [$deptInput, $deptInput]);
            }

            // 3. Jika belum terdaftar sama sekali di database, buat otomatis
            if (!empty($dept)) {
                $departmentId = $dept[0]->id;
            } else {
                $newDeptCode = Department::generateCode();
                DB::insert("
                    INSERT INTO departments (department_code, name, created_at, updated_at) 
                    VALUES (?, ?, ?, ?)
                ", [$newDeptCode, $deptInput, $now, $now]);
                $departmentId = DB::getPdo()->lastInsertId();
            }

            // 4. Generate otomatis Kode Karyawan baru (EMP-xxxx)
            $empCode = self::generateCode();

            // 5. Simpan karyawan baru
            DB::insert("
                INSERT INTO employees (employee_code, name, department_id, position, basic_salary, allowance, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ", [
                $empCode,
                $name,
                $departmentId,
                $position,
                $basicSalary,
                $allowance,
                $now,
                $now,
            ]);

            $count++;
        }

        return $count;
    }
}