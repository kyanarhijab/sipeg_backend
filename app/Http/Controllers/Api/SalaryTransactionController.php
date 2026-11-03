<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalaryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryTransactionController extends Controller
{
    public function index(Request $request)
    {
        $month = (int) $request->get('month', 10);
        $year  = (int) $request->get('year', 2026);

        $transactions = SalaryTransaction::getTransactions(
            $month,
            $year,
            $request->get('search'),
            $request->get('status'),
            (int) $request->get('page', 1),
            (int) $request->get('per_page', 10)
        );

        $summary = SalaryTransaction::getSummary($month, $year);

        return response()->json([
            'status'  => 'success',
            'data'    => $transactions,
            'summary' => $summary,
        ]);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year'  => 'required|integer|min:2000',
        ]);

        $count = SalaryTransaction::generateForPeriod((int) $request->month, (int) $request->year);

        return response()->json([
            'status'          => 'success',
            'message'         => "Berhasil men-generate {$count} data transaksi gaji.",
            'generated_count' => $count,
        ]);
    }

    public function updateDeduction(Request $request, $id)
    {
        $request->validate(['deduction' => 'required|numeric|min:0']);

        SalaryTransaction::updateDeductionRaw((int) $id, (float) $request->deduction);

        return response()->json([
            'status'  => 'success',
            'message' => 'Potongan gaji berhasil diperbarui',
            'data'    => SalaryTransaction::findByIdWithDetails((int) $id),
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:draft,paid']);

        SalaryTransaction::updateStatusRaw((int) $id, $request->status);

        return response()->json([
            'status'  => 'success',
            'message' => 'Status pembayaran berhasil diubah',
            'data'    => SalaryTransaction::findByIdWithDetails((int) $id),
        ]);
    }

    public function show($id)
    {
        $data = SalaryTransaction::findByIdWithDetails((int) $id);
        if (!$data) {
            return response()->json(['message' => 'Transaksi tidak ditemukan'], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    /**
     * POST /api/salaries/manual
     */
    public function storeManual(Request $request)
    {
        $validated = $request->validate([
            'employee_id'  => 'required|exists:employees,id',
            'month'        => 'required|integer|between:1,12',
            'year'         => 'required|integer|min:2000',
            'basic_salary' => 'required|numeric|min:0',
            'allowance'    => 'required|numeric|min:0',
            'deduction'    => 'nullable|numeric|min:0',
            'status'       => 'required|in:draft,paid',
        ]);

        $exists = DB::select("
            SELECT id FROM salary_transactions 
            WHERE employee_id = ? AND month = ? AND year = ? 
            LIMIT 1
        ", [$validated['employee_id'], $validated['month'], $validated['year']]);

        if (!empty($exists)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Transaksi gaji untuk karyawan ini pada periode tersebut sudah ada.'
            ], 422);
        }

        $transaction = SalaryTransaction::createManualRaw($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Transaksi gaji berhasil ditambahkan secara manual',
            'data'    => $transaction,
        ], 201);
    }

    /**
     * POST /api/salaries/import-deductions
     */
    public function importDeductions(Request $request)
    {
        $request->validate([
            'file'  => 'required|file|mimes:csv,txt,xlsx,xls',
            'month' => 'required|integer|between:1,12',
            'year'  => 'required|integer|min:2000',
        ]);

        $month = (int) $request->month;
        $year  = (int) $request->year;
        $file  = $request->file('file');

        $updatedCount = 0;
        $notFoundEmployees = [];

        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
            fgetcsv($handle, 1000, ',');
            
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if (empty($row[0])) continue;

                $empCode   = trim($row[0]);
                $deduction = isset($row[1]) ? (float) str_replace(['.', ','], ['', '.'], trim($row[1])) : 0;

                $affected = SalaryTransaction::updateDeductionByEmployeeCode($empCode, $month, $year, $deduction);

                if ($affected > 0) {
                    $updatedCount++;
                } else {
                    $notFoundEmployees[] = $empCode;
                }
            }
            fclose($handle);
        }

        return response()->json([
            'status'          => 'success',
            'message'         => "Berhasil mengupdate potongan untuk {$updatedCount} karyawan.",
            'updated_count'   => $updatedCount,
            'not_found_codes' => $notFoundEmployees,
        ]);
    }
}