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
    // Helper untuk dropdown filter (kecuali admin)
    private function getAssignableRoles()
    {
        return Role::where('id', '!=', RoleEnum::ID_ADMIN)->get();
    }

    public function index(Request $request)
    {
        if (Auth::user()->role_id != RoleEnum::ID_ADMIN) {
             return redirect()->route('dashboard')->withErrors('Akses ditolak.');
        }

        $query = User::with('role')->where('role_id', '!=', RoleEnum::ID_ADMIN);

        // Filter: Pencarian Nama
        if ($request->has('search') && $request->search != '') {
            $query->where('nama_lengkap', 'ilike', '%' . $request->search . '%');
        }

        // Filter: Role
        if ($request->has('role_id') && $request->role_id != '') {
            $query->where('role_id', $request->role_id);
        }

        $users = $query->orderBy('nama_lengkap')->paginate(10)->withQueryString();
        
        $roles = $this->getAssignableRoles();

        return view('admin.users.index', compact('users', 'roles'));
    }

    // PERBAIKAN: Tambahkan fungsi store ini
    public function store(Request $request)
    {
        if (Auth::user()->role_id != RoleEnum::ID_ADMIN) {
            abort(403);
        }

        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|string|min:6',
        ]);

        // Logic: Tambah user baru, role otomatis diset jadi ID_DOSEN (3)
        User::create([
            'nama_lengkap' => $validated['nama_lengkap'],
            'email'        => $validated['email'],
            'password'     => Hash::make($validated['password']),
            'role_id'      => RoleEnum::ID_DOSEN, 
        ]);

        return back()->with('success', 'Anggota baru berhasil ditambahkan sebagai Dosen.');
    }

    public function edit($id)
    {
        if (Auth::user()->role_id != RoleEnum::ID_ADMIN) {
             abort(403);
        }
        $user = User::findOrFail($id);
        $rolesToAssign = $this->getAssignableRoles();

        return view('admin.users.modal_edit', compact('user', 'rolesToAssign'));
    }

    public function update(Request $request, $id)
    {
        if (Auth::user()->role_id != RoleEnum::ID_ADMIN) {
             return back()->withErrors('Akses ditolak.');
        }

        $user = User::findOrFail($id);

        if ($user->role_id == RoleEnum::ID_ADMIN) {
            return back()->withErrors('Tidak bisa mengubah data Administrasi.');
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

        return back()->with('success', "Data {$user->nama_lengkap} berhasil diperbarui.");
    }
}