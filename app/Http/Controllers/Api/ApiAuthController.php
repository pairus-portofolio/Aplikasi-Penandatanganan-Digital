<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * API Controller untuk autentikasi menggunakan Laravel Sanctum.
 *
 * Menyediakan endpoint untuk login, logout, dan mendapatkan profil user
 * yang terautentikasi menggunakan token bearer.
 *
 * @package App\Http\Controllers\Api
 */
class ApiAuthController extends Controller
{
    /**
     * Login user dan generate access token.
     *
     * Validasi kredensial email dan password, kemudian generate
     * Sanctum token untuk autentikasi API.
     *
     * @param Request $request HTTP request dengan email dan password
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email atau password salah.'
            ], 401);
        }

        $user->tokens()->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil.',
            'data' => [
                'user_id' => $user->id,
                'nama' => $user->nama_lengkap,
                'role' => $user->role->nama_role ?? 'Unknown',
                'access_token' => $token,
                'token_type' => 'Bearer'
            ]
        ], 200);
    }

    /**
     * Logout user dan hapus access token saat ini.
     *
     * @param Request $request HTTP request dengan bearer token
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil.'
        ]);
    }

    /**
     * Dapatkan profil user yang sedang login.
     *
     * @param Request $request HTTP request dengan bearer token
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user()
        ]);
    }
}