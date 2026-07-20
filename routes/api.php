<?php

// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AttendanceController;

// Route Public (Bisa diakses tanpa token)
Route::post('/login', [AuthController::class, 'login']);

// Route Protected (Wajib bawa token untuk mengaksesnya)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Menggunakan closure langsung tanpa controller
    Route::get('/user-profile', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    });
    
    // Route transaksi absen Anda
    Route::post('/attendance', [AttendanceController::class, 'store']);
    // Tambahkan route rekap di bawah ini
    Route::get('/attendance/recap', [AttendanceController::class, 'recap']);
});