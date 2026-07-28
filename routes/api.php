<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;

// Public Route
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes (Harus Pakai Bearer Token)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Upload bukti
    Route::post('/bukti', [AttendanceController::class, 'uploadBukti']);

    // Ambil detail bukti
    Route::get('/bukti', [AttendanceController::class, 'bukti']);
    
    // Attendance Core (Check-In & Check-Out)
    Route::post('/attendance', [AttendanceController::class, 'store']);
    
    // Rekap
    Route::get('/hari-ini', [AttendanceController::class, 'today']);
    Route::get('/rekap', [AttendanceController::class, 'recap']);
});