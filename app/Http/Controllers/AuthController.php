<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. Validasi input email dan password
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_id' => 'required',
        ]);

        // 2. Cari user berdasarkan email
        $user = User::where('email', $request->email)->first();

        // 3. Cek apakah user ada dan password-nya benar
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.'
            ], 401);
        }
        // LOGIKA DEVICE BINDING:
        // Jika user belum punya device_id terdaftar, ikat HP ini sebagai HP resminya
        if (is_null($user->device_id)) {
            $user->update([
                'device_id' => $request->device_id
            ]);
        }
        // Jika sudah ada, pastikan device_id yang dikirim sama dengan yang terdaftar
        elseif ($user->device_id !== $request->device_id) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal login! Akun Anda sudah terikat di perangkat lain. Silakan hubungi admin.'
            ], 403); // 403 Forbidden
        }

        // 4. Bikin token baru untuk user tersebut
        $token = $user->createToken('auth_token')->plainTextToken;

        // 5. Kembalikan respon sukses beserta tokennya
        return response()->json([
            'success' => true,
            'message' => 'Login berhasil!',
            'access_token' => $token,
            'token_type' => 'Bearer'
        ], 200);
    }

    public function logout(Request $request)
    {
        // Menghapus token yang sedang digunakan saat ini
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil logout, token telah dihapus.'
        ], 200);
    }
}