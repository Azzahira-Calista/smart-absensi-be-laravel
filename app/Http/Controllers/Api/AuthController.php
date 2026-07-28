<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_id' => 'required',
        ]);

        // 2. Cari user
        $user = User::where('email', $request->email)->first();

        // 3. Cek user & password
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.'
            ], 401);
        }

        // 4. Device Binding
        if (is_null($user->device_id)) {
            $user->update([
                'device_id' => $request->device_id
            ]);
        } elseif ($user->device_id !== $request->device_id) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal login! Akun Anda sudah terikat di perangkat lain. Silakan hubungi admin.'
            ], 403);
        }

        // 5. Generate token (Tanpa token_type)
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil!',
            // 'message' => null,
            'access_token' => $token,
        ], 200);
    }

    public function profile()
    {
        // Jika sukses, message = null
        return response()->json([
            'success' => true,
            // 'message' => null,
            'message' => 'data berhasil diambil',
            'data' => Auth::user()
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil logout, token telah dihapus.'
            // 'message' => null,
        ], 200);
    }
}