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

        // Menentukan field login (Hanya Email)
        $loginField = 'email';

        // Mencoba login dengan kredensial yang dimasukkan
        $attempt = Auth::attempt(
            ['email' => $credentials['email'], 'password' => $credentials['password']],
            $request->filled('remember')
        );

        // Jika login berhasil
        if ($attempt) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        // Mencari user untuk kemungkinan password yang masih plaintext
        $user = User::where('email', $credentials['email'])->first();

        // Validasi login untuk user yang menyimpan password plaintext
        if ($user && $user->password === $credentials['password']) {
            // Mengubah plaintext password menjadi hash
            $user->password = $credentials['password'];
            $user->save();

            // Mencoba login ulang setelah password diperbaiki
            $attempt = Auth::attempt(
                ['email' => $credentials['email'], 'password' => $credentials['password']],
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
