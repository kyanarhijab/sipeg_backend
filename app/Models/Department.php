<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_code',
        'name',
        'sub_dept',
    ];

    /* =========================================================================
     * 1. RELASI ELOQUENT & ACCESSOR
     * ========================================================================= */

    /**
     * Relasi: Satu departemen memiliki banyak karyawan.
     */
    public function employees()
    {
        return $this->hasMany(Employee::class, 'department_id', 'id');
    }

    /**
     * Accessor tampilan format "Produksi - Assembling"
     */
    public function getFullNameAttribute(): string
    {
        return $this->sub_dept ? "{$this->name} - {$this->sub_dept}" : $this->name;
    }

    protected $appends = ['full_name'];

    /* =========================================================================
     * 2. RAW QUERY METHODS (MODULAR DATABASE LOGIC)
     * ========================================================================= */

    /**
     * Ambil semua departemen beserta jumlah karyawannya
     */
    public static function getAllWithCount()
    {
        return DB::select("
            SELECT 
                d.id,
                d.department_code,
                d.name,
                d.sub_dept,
                CONCAT(d.name, IF(d.sub_dept IS NOT NULL AND d.sub_dept != '', CONCAT(' - ', d.sub_dept), '')) AS full_name,
                (SELECT COUNT(e.id) FROM employees e WHERE e.department_id = d.id) AS employees_count,
                d.created_at
            FROM departments d
            ORDER BY d.id DESC
        ");
    }

    /**
     * Ambil 1 data departemen berdasarkan ID
     */
    public static function findById(int $id)
    {
        $result = DB::select("
            SELECT 
                id, 
                department_code, 
                name, 
                sub_dept,
                CONCAT(name, IF(sub_dept IS NOT NULL AND sub_dept != '', CONCAT(' - ', sub_dept), '')) AS full_name
            FROM departments 
            WHERE id = ? 
            LIMIT 1
        ", [$id]);

        return $result ? $result[0] : null;
    }

    /**
     * Generate kode unik baru otomatis: DPT-001, DPT-002, dst.
     */
    public static function generateCode(): string
    {
        $latest = DB::select("SELECT department_code FROM departments ORDER BY id DESC LIMIT 1");
        $nextNumber = 1;

        if (!empty($latest) && preg_match('/DPT-(\d+)/', $latest[0]->department_code, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return 'DPT-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Simpan departemen baru (Raw Insert)
     */
    public static function createRaw(array $data)
    {
        $code = !empty($data['department_code']) ? $data['department_code'] : self::generateCode();
        $now = now();

        DB::insert("
            INSERT INTO departments (department_code, name, sub_dept, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?)
        ", [
            $code,
            $data['name'],
            $data['sub_dept'] ?? null,
            $now,
            $now,
        ]);

        return self::findById((int) DB::getPdo()->lastInsertId());
    }

    /**
     * Update departemen (Raw Update)
     */
    public static function updateRaw(int $id, array $data)
    {
        return DB::update("
            UPDATE departments 
            SET department_code = ?, 
                name = ?, 
                sub_dept = ?, 
                updated_at = ?
            WHERE id = ?
        ", [
            $data['department_code'],
            $data['name'],
            $data['sub_dept'] ?? null,
            now(),
            $id,
        ]);
    }

    /**
     * Cek apakah departemen masih memiliki karyawan
     */
    public static function countLinkedEmployees(int $id): int
    {
        $result = DB::select("SELECT COUNT(*) AS total FROM employees WHERE department_id = ?", [$id]);
        return (int) ($result[0]->total ?? 0);
    }

    /**
     * Hapus departemen (Raw Delete)
     */
    public static function deleteRaw(int $id)
    {
        return DB::delete("DELETE FROM departments WHERE id = ?", [$id]);
    }
}