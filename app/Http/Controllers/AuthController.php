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

    // Proses login user
    public function login(Request $request)
    {
        // Validasi input form
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required'],
        ]);

        // Tentukan apakah login menggunakan email atau nama_lengkap
        $loginField = filter_var($credentials['email'], FILTER_VALIDATE_EMAIL) ? 'email' : 'nama_lengkap';

        // Coba login dengan data yang diberikan
        $attempt = Auth::attempt(
            [$loginField => $credentials['email'], 'password' => $credentials['password']],
            $request->filled('remember')
        );

        // Jika berhasil login
        if ($attempt) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        // Jika gagal, cek kemungkinan password masih disimpan dalam bentuk plaintext (misal dari seeder)
        $userQuery = $loginField === 'email'
            ? ['email' => $credentials['email']]
            : ['nama_lengkap' => $credentials['email']];

        $user = User::where($userQuery)->first();

        // Jika ditemukan dan password cocok (plaintext)
        if ($user && $user->password === $credentials['password']) {
            // Hash password agar aman (otomatis oleh cast 'hashed' di model User)
            $user->password = $credentials['password'];
            $user->save();

            // Coba login ulang setelah password di-hash
            $attempt = Auth::attempt(
                [$loginField => $credentials['email'], 'password' => $credentials['password']],
                $request->filled('remember')
            );

            if ($attempt) {
                $request->session()->regenerate();
                return redirect()->intended(route('dashboard'));
            }
        }

        // Jika tetap gagal login
        return back()
            ->withInput($request->only('email'))
            ->with('error', 'Kredensial tidak cocok.');
    }

    // Proses logout user
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('auth.login');
    }
}
