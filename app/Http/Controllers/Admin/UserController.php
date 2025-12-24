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
    private function getAssignableRoles()
    {
        return Role::whereIn('id', [
            RoleEnum::ID_TU, 
            RoleEnum::ID_KOORDINATOR_PRODI, // PERBAIKAN: Gunakan ID baru
            RoleEnum::ID_DOSEN,             // PERBAIKAN: Tambahkan ID Dosen
            RoleEnum::ID_KAJUR, 
            RoleEnum::ID_SEKJUR
        ])->get();
    }

    public function index()
    {
        if (Auth::user()->role_id != RoleEnum::ID_ADMIN) {
             return redirect()->route('dashboard')->withErrors('Anda tidak memiliki akses Admin.');
        }

        $users = User::with('role')
                      ->where('role_id', '!=', RoleEnum::ID_ADMIN)
                      ->orderBy('nama_lengkap')
                      ->paginate(10);
        
        $rolesToAssign = $this->getAssignableRoles();

        return view('admin.users.index', compact('users', 'rolesToAssign'));
    }

    public function edit($id)
    {
        if (Auth::user()->role_id != RoleEnum::ID_ADMIN) {
             abort(403, 'Akses Ditolak.');
        }

        $user = User::findOrFail($id);

        if ($user->role_id == RoleEnum::ID_ADMIN) {
            abort(403, 'Tidak bisa mengedit pengguna Admin.');
        }

        $rolesToAssign = $this->getAssignableRoles();

        return view('admin.users.modal_edit', compact('user', 'rolesToAssign'));
    }

    public function update(Request $request, $id)
    {
        if (Auth::user()->role_id != RoleEnum::ID_ADMIN) {
             return back()->withErrors('Anda tidak memiliki hak untuk melakukan aksi ini.');
        }

        $user = User::findOrFail($id);

        if ($user->role_id == RoleEnum::ID_ADMIN || $user->id == Auth::id()) {
            return back()->withErrors('Tidak bisa mengubah role/data Admin atau data Anda sendiri.');
        }

        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'email'        => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role_id'      => ['required', 'exists:roles,id', Rule::notIn([RoleEnum::ID_ADMIN])], 
            'password'     => 'nullable|string|min:6', 
        ]);

        $data = [
            'nama_lengkap' => $validated['nama_lengkap'],
            'email'        => $validated['email'],
            'role_id'      => $validated['role_id'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }
        
        $user->update($data);

        return back()->with('success', "Data pengguna {$user->nama_lengkap} berhasil diperbarui.");
    }
}