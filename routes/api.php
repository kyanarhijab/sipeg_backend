<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\SalaryTransactionController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\MenuController;
use Illuminate\Support\Facades\Route;

// Endpoint Login dengan proteksi API Key
Route::post('/login', [AuthController::class, 'login'])->middleware('api.key');

// Endpoint Terproteksi Token
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Master CRUD
    Route::apiResource('departments', DepartmentController::class);
    Route::post('/employees/import', [EmployeeController::class, 'import']);
    Route::apiResource('employees', EmployeeController::class);

    // Transaksi Gaji
    Route::get('/salaries', [SalaryTransactionController::class, 'index']);
    Route::post('/salaries/generate', [SalaryTransactionController::class, 'generate']);
    Route::get('/salaries/{id}', [SalaryTransactionController::class, 'show']);
    Route::patch('/salaries/{id}/deduction', [SalaryTransactionController::class, 'updateDeduction']);
    Route::patch('/salaries/{id}/status', [SalaryTransactionController::class, 'updateStatus']);
    Route::post('/salaries/manual', [SalaryTransactionController::class, 'storeManual']);
    Route::post('/salaries/import-deductions', [SalaryTransactionController::class, 'importDeductions']);

    // Laporan Rekapitulasi Gaji
    Route::get('/reports', [ReportController::class, 'index']);

    Route::get('/menus', [MenuController::class, 'index']);
});