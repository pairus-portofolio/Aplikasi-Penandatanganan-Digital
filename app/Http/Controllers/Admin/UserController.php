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
    // Helper untuk mengambil role selain Admin
    private function getAssignableRoles()
    {
        return Role::where('nama_role', '!=', RoleEnum::ADMIN)->get();
    }

    public function index(Request $request)
    {
        if (Auth::user()->role_id != RoleEnum::ID_ADMIN) {
             return redirect()->route('dashboard')->withErrors('Akses ditolak.');
        }

        $query = User::with('role')->where('role_id', '!=', RoleEnum::ID_ADMIN);

        // Filter Search
        if ($request->has('search') && $request->search != '') {
            $query->where('nama_lengkap', 'ilike', '%' . $request->search . '%');
        }

        // Filter Role
        if ($request->has('role_id') && $request->role_id != '') {
            $query->where('role_id', $request->role_id);
        }

        $users = $query->orderBy('nama_lengkap')->paginate(10)->withQueryString();
        
        $roles = $this->getAssignableRoles();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->role_id != RoleEnum::ID_ADMIN) {
            abort(403);
        }

        // 1. Validasi (Tetap Sama)
        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                // Rule ini membolehkan email yang sudah di-soft delete untuk dipakai lagi
                \Illuminate\Validation\Rule::unique('users')->whereNull('deleted_at')
            ],
            'password'     => 'required|string|min:6',
        ]);

        // 2. CEK: Apakah email ini ada di tong sampah?
        $existingUser = User::withTrashed() // Sertakan user yang dihapus
                            ->where('email', $validated['email'])
                            ->first();

        // 3. LOGIKA: Restore atau Create Baru
        if ($existingUser && $existingUser->trashed()) {
            // SKENARIO A: User Lama Kembali -> Kita Restore
            $existingUser->restore(); 
            
            // Update data lama dengan input baru (Nama & Password baru)
            $existingUser->update([
                'nama_lengkap' => $validated['nama_lengkap'],
                'password'     => Hash::make($validated['password']),
                'role_id'      => RoleEnum::ID_DOSEN, // Pastikan role diset
            ]);

            return back()->with('success', 'Anggota lama berhasil diaktifkan kembali.');
        } else {
            // SKENARIO B: User Benar-Benar Baru -> Create
            User::create([
                'nama_lengkap' => $validated['nama_lengkap'],
                'email'        => $validated['email'],
                'password'     => Hash::make($validated['password']),
                'role_id'      => RoleEnum::ID_DOSEN, 
            ]);

            return back()->with('success', 'Anggota baru berhasil ditambahkan sebagai Dosen.');
        }
    }

    public function edit($id)
    {
        if (Auth::user()->role_id != RoleEnum::ID_ADMIN) {
             abort(403);
        }
        $user = User::findOrFail($id);
        
        // Cegah edit sesama admin
        if ($user->role_id == RoleEnum::ID_ADMIN) {
            return '<div class="alert alert-danger">Tidak dapat mengedit akun Administrasi.</div>';
        }

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

    // --- BAGIAN PENTING YANG TADI HILANG ---
    public function destroy($id)
    {
        if (Auth::user()->role_id != RoleEnum::ID_ADMIN) {
             return back()->withErrors('Akses ditolak.');
        }

        $user = User::findOrFail($id);

        // Validasi: Tidak boleh menghapus diri sendiri
        if ($user->id == Auth::id()) {
            return back()->withErrors('Anda tidak dapat menghapus akun Anda sendiri.');
        }

        // Validasi: Tidak boleh menghapus sesama Admin
        if ($user->role_id == RoleEnum::ID_ADMIN) {
            return back()->withErrors('Tidak dapat menghapus akun Administrasi.');
        }

        // Hapus (Soft Delete)
        $user->delete();

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }
}