<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
       
        $departments = Department::getAllWithCount();

        return response()->json([
            'status' => 'success',
            'data'   => $departments,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_code' => 'nullable|string|unique:departments,department_code',
            'name'            => 'required|string|max:100',
            'sub_dept'        => 'nullable|string|max:100',
        ]);

        $department = Department::createRaw($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Departemen berhasil ditambahkan',
            'data'    => $department,
        ], 201);
    }

    public function show($id)
    {
        $department = Department::findById((int) $id);
        if (!$department) {
            return response()->json(['message' => 'Departemen tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $department,
        ]);
    }

    public function update(Request $request, $id)
    {
        $department = Department::findById((int) $id);
        if (!$department) {
            return response()->json(['message' => 'Departemen tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'department_code' => 'required|string|unique:departments,department_code,' . $id,
            'name'            => 'required|string|max:100',
            'sub_dept'        => 'nullable|string|max:100',
        ]);

        Department::updateRaw((int) $id, $validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Departemen berhasil diperbarui',
            'data'    => Department::findById((int) $id),
        ]);
    }

    public function destroy($id)
    {
        if (Department::countLinkedEmployees((int) $id) > 0) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tidak dapat menghapus departemen karena masih memiliki data karyawan terkait.',
            ], 422);
        }

        Department::deleteRaw((int) $id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Departemen berhasil dihapus',
        ]);
    }
}