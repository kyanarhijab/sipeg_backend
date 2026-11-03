<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalaryTransaction;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $month  = (int) $request->get('month', 10);
        $year   = (int) $request->get('year', 2026);
        $deptId = $request->get('department_id');

        
        $report = SalaryTransaction::getReportData($month, $year, $deptId);

        return response()->json([
            'status'  => 'success',
            'filters' => [
                'month'         => $month,
                'year'          => $year,
                'department_id' => $deptId,
            ],
            'records' => $report['records'],
            'summary' => $report['summary'],
        ]);
    }
}