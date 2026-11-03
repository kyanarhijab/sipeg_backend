<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SalaryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_code',
        'employee_id',
        'month',
        'year',
        'basic_salary',
        'allowance',
        'deduction',
        'net_salary',
        'status',
    ];

    protected $casts = [
        'month'        => 'integer',
        'year'         => 'integer',
        'basic_salary' => 'decimal:2',
        'allowance'    => 'decimal:2',
        'deduction'    => 'decimal:2',
        'net_salary'   => 'decimal:2',
    ];

    /* =========================================================================
     * 1. RELASI ELOQUENT & ACCESSOR
     * ========================================================================= */

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function getPeriodNameAttribute(): string
    {
        return Carbon::createFromDate($this->year, $this->month, 1)->translatedFormat('F Y');
    }

    protected $appends = ['period_name'];

    /* =========================================================================
     * 2. RAW QUERY METHODS (MODULAR DATABASE LOGIC)
     * ========================================================================= */

    /**
     * Generate kode transaksi unik SLR-YYYYMM-XXXX
     */
    public static function generateCode(int $year, int $month): string
    {
        $prefix = sprintf('SLR-%04d%02d-', $year, $month);
        $latest = DB::select("
            SELECT transaction_code 
            FROM salary_transactions 
            WHERE transaction_code LIKE ? 
            ORDER BY id DESC 
            LIMIT 1
        ", ["{$prefix}%"]);

        $nextNumber = 1;
        if (!empty($latest) && preg_match('/-(\d+)$/', $latest[0]->transaction_code, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Ambil data transaksi per periode dengan paginasi dan filter
     */
    public static function getTransactions(int $month, int $year, ?string $search = null, ?string $status = null, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        $conditions = ["st.month = ?", "st.year = ?"];
        $bindings = [$month, $year];

        if (!empty($search)) {
            $conditions[] = "(st.transaction_code LIKE ? OR e.name LIKE ? OR e.employee_code LIKE ?)";
            $bindings[] = "%{$search}%";
            $bindings[] = "%{$search}%";
            $bindings[] = "%{$search}%";
        }

        if (!empty($status) && $status !== 'all') {
            $conditions[] = "st.status = ?";
            $bindings[] = $status;
        }

        $whereClause = implode(" AND ", $conditions);

        // Hitung total data
        $countResult = DB::select("
            SELECT COUNT(*) AS total 
            FROM salary_transactions st
            JOIN employees e ON e.id = st.employee_id
            WHERE {$whereClause}
        ", $bindings);
        $total = (int) ($countResult[0]->total ?? 0);

        // Ambil baris per halaman
        $dataBindings = array_merge($bindings, [$perPage, $offset]);
        $rows = DB::select("
            SELECT 
                st.id,
                st.transaction_code,
                st.month,
                st.year,
                st.basic_salary,
                st.allowance,
                st.deduction,
                st.net_salary,
                st.status,
                e.id AS employee_id,
                e.employee_code,
                e.name AS employee_name,
                e.position,
                d.name AS department_name,
                d.sub_dept,
                CONCAT(d.name, IF(d.sub_dept IS NOT NULL AND d.sub_dept != '', CONCAT(' - ', d.sub_dept), '')) AS full_department_name
            FROM salary_transactions st
            JOIN employees e ON e.id = st.employee_id
            JOIN departments d ON d.id = e.department_id
            WHERE {$whereClause}
            ORDER BY st.id DESC
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
     * Hitung ringkasan transaksi per periode (Raw Aggregate)
     */
    public static function getSummary(int $month, int $year): array
    {
        $result = DB::select("
            SELECT 
                COUNT(*) AS total_transactions,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN net_salary ELSE 0 END), 0) AS total_paid,
                COALESCE(SUM(CASE WHEN status = 'draft' THEN net_salary ELSE 0 END), 0) AS total_draft
            FROM salary_transactions
            WHERE month = ? AND year = ?
        ", [$month, $year]);

        return [
            'total_transactions' => (int) ($result[0]->total_transactions ?? 0),
            'total_paid'         => (float) ($result[0]->total_paid ?? 0),
            'total_draft'        => (float) ($result[0]->total_draft ?? 0),
        ];
    }

    /**
     * Generate data transaksi otomatis dari seluruh karyawan yang belum ada di periode tersebut
     */
    public static function generateForPeriod(int $month, int $year): int
    {
        $employees = DB::select("
            SELECT e.id, e.basic_salary, e.allowance 
            FROM employees e
            WHERE NOT EXISTS (
                SELECT 1 FROM salary_transactions st 
                WHERE st.employee_id = e.id AND st.month = ? AND st.year = ?
            )
        ", [$month, $year]);

        $count = 0;
        $now = now();

        foreach ($employees as $emp) {
            $code = self::generateCode($year, $month);
            $netSalary = ((float) $emp->basic_salary + (float) $emp->allowance) - 0;

            DB::insert("
                INSERT INTO salary_transactions 
                (transaction_code, employee_id, month, year, basic_salary, allowance, deduction, net_salary, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, 0, ?, 'draft', ?, ?)
            ", [
                $code,
                $emp->id,
                $month,
                $year,
                $emp->basic_salary,
                $emp->allowance,
                $netSalary,
                $now,
                $now,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Update potongan dan hitung otomatis net_salary
     */
    public static function updateDeductionRaw(int $id, float $deduction)
    {
        return DB::update("
            UPDATE salary_transactions
            SET deduction = ?,
                net_salary = (basic_salary + allowance) - ?,
                updated_at = ?
            WHERE id = ?
        ", [$deduction, $deduction, now(), $id]);
    }

    /**
     * Update status transaksi (draft / paid)
     */
    public static function updateStatusRaw(int $id, string $status)
    {
        return DB::update("
            UPDATE salary_transactions
            SET status = ?, 
                updated_at = ?
            WHERE id = ?
        ", [$status, now(), $id]);
    }

    /**
     * Ambil satu transaksi lengkap dengan relasi karyawan dan departemen
     */
    public static function findByIdWithDetails(int $id)
    {
        $result = DB::select("
            SELECT 
                st.*,
                e.employee_code,
                e.name AS employee_name,
                e.position,
                d.department_code,
                d.name AS department_name,
                d.sub_dept,
                CONCAT(d.name, IF(d.sub_dept IS NOT NULL AND d.sub_dept != '', CONCAT(' - ', d.sub_dept), '')) AS full_department_name
            FROM salary_transactions st
            JOIN employees e ON e.id = st.employee_id
            JOIN departments d ON d.id = e.department_id
            WHERE st.id = ?
            LIMIT 1
        ", [$id]);

        return $result ? $result[0] : null;
    }

    /**
     * Ambil data rekapitulasi laporan penggajian keseluruhan beserta grand total
     */
    public static function getReportData(int $month, int $year, $departmentId = null): array
    {
        $conditions = ["st.month = ?", "st.year = ?"];
        $bindings = [$month, $year];

        if (!empty($departmentId) && $departmentId !== 'all') {
            $conditions[] = "e.department_id = ?";
            $bindings[] = (int) $departmentId;
        }

        $whereClause = implode(" AND ", $conditions);

        $records = DB::select("
            SELECT 
                st.id,
                st.transaction_code,
                st.month,
                st.year,
                st.basic_salary,
                st.allowance,
                st.deduction,
                st.net_salary,
                st.status,
                e.employee_code,
                e.name AS employee_name,
                e.position,
                d.department_code,
                d.name AS department_name,
                d.sub_dept,
                CONCAT(d.name, IF(d.sub_dept IS NOT NULL AND d.sub_dept != '', CONCAT(' - ', d.sub_dept), '')) AS full_department_name
            FROM salary_transactions st
            JOIN employees e ON e.id = st.employee_id
            JOIN departments d ON d.id = e.department_id
            WHERE {$whereClause}
            ORDER BY st.id ASC
        ", $bindings);

        $summaryResult = DB::select("
            SELECT 
                COUNT(*) AS total_employees,
                COALESCE(SUM(st.basic_salary), 0) AS total_basic_salary,
                COALESCE(SUM(st.allowance), 0) AS total_allowance,
                COALESCE(SUM(st.deduction), 0) AS total_deduction,
                COALESCE(SUM(st.net_salary), 0) AS total_net_salary
            FROM salary_transactions st
            JOIN employees e ON e.id = st.employee_id
            WHERE {$whereClause}
        ", $bindings);

        $summary = $summaryResult[0];

        return [
            'records' => $records,
            'summary' => [
                'total_employees'    => (int) $summary->total_employees,
                'total_basic_salary' => (float) $summary->total_basic_salary,
                'total_allowance'    => (float) $summary->total_allowance,
                'total_deduction'    => (float) $summary->total_deduction,
                'total_net_salary'   => (float) $summary->total_net_salary,
            ],
        ];
    }

    /**
     * Input Manual 1 Transaksi Gaji (Raw Insert)
     */
    public static function createManualRaw(array $data)
    {
        $code = self::generateCode((int) $data['year'], (int) $data['month']);
        $basic = (float) $data['basic_salary'];
        $allowance = (float) $data['allowance'];
        $deduction = (float) ($data['deduction'] ?? 0);
        $netSalary = ($basic + $allowance) - $deduction;
        $now = now();

        DB::insert("
            INSERT INTO salary_transactions 
            (transaction_code, employee_id, month, year, basic_salary, allowance, deduction, net_salary, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $code,
            (int) $data['employee_id'],
            (int) $data['month'],
            (int) $data['year'],
            $basic,
            $allowance,
            $deduction,
            $netSalary,
            $data['status'] ?? 'draft',
            $now,
            $now,
        ]);

        return self::findByIdWithDetails((int) DB::getPdo()->lastInsertId());
    }

    /**
     * Update Potongan berdasarkan employee_code (Untuk Import Excel/CSV)
     */
    public static function updateDeductionByEmployeeCode(string $employeeCode, int $month, int $year, float $deduction)
    {
        return DB::update("
            UPDATE salary_transactions st
            JOIN employees e ON e.id = st.employee_id
            SET st.deduction = ?,
                st.net_salary = (st.basic_salary + st.allowance) - ?,
                st.updated_at = ?
            WHERE e.employee_code = ? AND st.month = ? AND st.year = ?
        ", [$deduction, $deduction, now(), $employeeCode, $month, $year]);
    }

}