<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        
        $list = Employee::getPaginatedList(
            $request->get('search'),
            $request->get('department_id'),
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        return response()->json([
            'status' => 'success',
            'data'   => $list,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_code' => 'nullable|string|unique:employees,employee_code',
            'name'          => 'required|string|max:150',
            'department_id' => 'required|exists:departments,id',
            'position'      => 'required|string|max:100',
            'basic_salary'  => 'required|numeric|min:0',
            'allowance'     => 'nullable|numeric|min:0',
        ]);

        $employee = Employee::createRaw($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Karyawan berhasil ditambahkan',
            'data'    => $employee,
        ], 201);
    }

    public function show($id)
    {
        $employee = Employee::findById((int) $id);
        if (!$employee) {
            return response()->json(['message' => 'Karyawan tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $employee,
        ]);
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findById((int) $id);
        if (!$employee) {
            return response()->json(['message' => 'Karyawan tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'employee_code' => 'required|string|unique:employees,employee_code,' . $id,
            'name'          => 'required|string|max:150',
            'department_id' => 'required|exists:departments,id',
            'position'      => 'required|string|max:100',
            'basic_salary'  => 'required|numeric|min:0',
            'allowance'     => 'nullable|numeric|min:0',
        ]);

        Employee::updateRaw((int) $id, $validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Data karyawan berhasil diperbarui',
            'data'    => Employee::findById((int) $id),
        ]);
    }

    public function destroy($id)
    {
        Employee::deleteRaw((int) $id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Data karyawan berhasil dihapus',
        ]);
    }

    /**
     * POST /api/employees/import
     * Format Kolom CSV: Nama, Departemen, Jabatan, Gaji Pokok, Tunjangan
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('file');
        $rows = [];

        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
            fgetcsv($handle, 1000, ','); 

            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if (empty($row[0])) continue;

                $rows[] = [
                    'name'            => $row[0] ?? '',
                    'department_code' => $row[1] ?? 'DPT-001', 
                    'position'        => $row[2] ?? 'Staff',
                    'basic_salary'    => isset($row[3]) ? (float) str_replace(['.', ','], ['', '.'], trim($row[3])) : 0,
                    'allowance'       => isset($row[4]) ? (float) str_replace(['.', ','], ['', '.'], trim($row[4])) : 0,
                ];
            }
            fclose($handle);
        }

        $importedCount = Employee::importRawRows($rows);

        return response()->json([
            'status'  => 'success',
            'message' => "Berhasil mengimpor {$importedCount} data karyawan beserta kode departemen dan komponen gaji.",
            'count'   => $importedCount,
        ]);
    }

    
}