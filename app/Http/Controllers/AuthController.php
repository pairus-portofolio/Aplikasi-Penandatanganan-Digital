<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    // Menampilkan halaman login
    public function showLogin()
    {
        return view('auth.login');
    }

    // Memproses login pengguna
    public function login(Request $request)
    {
        // Validasi input login
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required'],
        ]);

        // Menentukan apakah login memakai email atau nama lengkap
        $loginField = filter_var($credentials['email'], FILTER_VALIDATE_EMAIL) ? 'email' : 'nama_lengkap';

        // Mencoba login dengan kredensial yang dimasukkan
        $attempt = Auth::attempt(
            [$loginField => $credentials['email'], 'password' => $credentials['password']],
            $request->filled('remember')
        );

        // Jika login berhasil
        if ($attempt) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        // Mencari user untuk kemungkinan password yang masih plaintext
        $userQuery = $loginField === 'email'
            ? ['email' => $credentials['email']]
            : ['nama_lengkap' => $credentials['email']];

        $user = User::where($userQuery)->first();

        // Validasi login untuk user yang menyimpan password plaintext
        if ($user && $user->password === $credentials['password']) {
            // Mengubah plaintext password menjadi hash
            $user->password = $credentials['password'];
            $user->save();

            // Mencoba login ulang setelah password diperbaiki
            $attempt = Auth::attempt(
                [$loginField => $credentials['email'], 'password' => $credentials['password']],
                $request->filled('remember')
            );

            // Jika berhasil setelah diperbaiki
            if ($attempt) {
                $request->session()->regenerate();
                return redirect()->intended(route('dashboard'));
            }
        }
        
        // Jika login gagal, kirim pesan error
        if (!$user) {
            $error = 'Email tidak terdaftar';
        } else {
            $error = 'Password yang Anda masukkan salah.';
        }

        return back()
            ->withInput($request->only('email'))
            ->with('error', $error);
    }

    // Logout pengguna dan mengakhiri sesi
    public function logout(Request $request)
    {
        // Logout dan menghapus sesi user
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Arahkan kembali ke halaman login
        return redirect()->route('auth.login');
    }
}
