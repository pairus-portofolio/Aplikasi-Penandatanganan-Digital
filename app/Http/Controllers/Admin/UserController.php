<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Enums\RoleEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Menampilkan daftar pengguna untuk di-manage.
     */
    public function index()
    {
        if (Auth::user()->role_id != RoleEnum::ID_ADMIN) {
             return redirect()->route('dashboard')->withErrors('Anda tidak memiliki akses Admin.');
        }

        // Ambil semua user kecuali Admin itu sendiri
        $users = User::with('role')
                      ->where('role_id', '!=', RoleEnum::ID_ADMIN)
                      ->orderBy('nama_lengkap')
                      ->paginate(10);
        
        // Ambil semua role yang bisa diganti (TU sampai Sekjur)
        $rolesToAssign = Role::whereIn('id', [
            RoleEnum::ID_TU, 
            RoleEnum::ID_KAPRODI_D3, 
            RoleEnum::ID_KAPRODI_D4, 
            RoleEnum::ID_KAJUR, 
            RoleEnum::ID_SEKJUR
        ])->get();

        return view('admin.users.index', compact('users', 'rolesToAssign'));
    }

    /**
     * Menampilkan formulir edit dalam bentuk partial view untuk ModalManager.
     */
    public function edit($id)
    {
        if (Auth::user()->role_id != RoleEnum::ID_ADMIN) {
             abort(403, 'Akses Ditolak.');
        }

        $user = User::findOrFail($id);

        if ($user->role_id == RoleEnum::ID_ADMIN) {
            abort(403, 'Tidak bisa mengedit pengguna Admin.');
        }

        $rolesToAssign = Role::where('id', '!=', RoleEnum::ID_ADMIN)->get();

        // Menggunakan compact untuk mengirim data ke partial view
        return view('admin.users.modal_edit', compact('user', 'rolesToAssign'));
    }

    /**
     * Mengubah Nama Lengkap, Email, Peran, dan Password pengguna.
     */
    public function update(Request $request, $id)
    {
        if (Auth::user()->role_id != RoleEnum::ID_ADMIN) {
             return back()->withErrors('Anda tidak memiliki hak untuk melakukan aksi ini.');
        }

        $user = User::findOrFail($id);

        if ($user->role_id == RoleEnum::ID_ADMIN || $user->id == Auth::id()) {
            return back()->withErrors('Tidak bisa mengubah role/data Admin atau data Anda sendiri.');
        }

        // 1. Validasi Input
        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'email'        => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            // Pastikan role bukan Admin
            'role_id'      => ['required', 'exists:roles,id', Rule::notIn([RoleEnum::ID_ADMIN])], 
            'password'     => 'nullable|string|min:6', // Password opsional
        ]);

        // 2. Persiapan Data Update
        $data = [
            'nama_lengkap' => $validated['nama_lengkap'],
            'email'        => $validated['email'],
            'role_id'      => $validated['role_id'],
        ];

        // 3. Update Password jika diisi
        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }
        
        // 4. Lakukan Update
        $user->update($data);

        return back()->with('success', "Data pengguna {$user->nama_lengkap} berhasil diperbarui.");
    }
}