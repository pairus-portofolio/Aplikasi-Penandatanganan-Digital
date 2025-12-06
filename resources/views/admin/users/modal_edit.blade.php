{{-- resources/views/admin/users/modal_edit.blade.php --}}

<form method="POST" action="{{ route('admin.users.update', $user->id) }}">
    @csrf
    @method('PUT')

    {{-- KONTEN MODAL: Nama Lengkap, Email, Peran, Password --}}

    <div class="mb-3">
        <label for="nama_lengkap" class="form-label fw-bold">Nama Lengkap</label>
        <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control form-control-sm"
               value="{{ old('nama_lengkap', $user->nama_lengkap) }}" required>
    </div>

    <div class="mb-3">
        <label for="email" class="form-label fw-bold">Email</label>
        <input type="email" name="email" id="email" class="form-control form-control-sm"
               value="{{ old('email', $user->email) }}" required>
    </div>

    <div class="mb-3">
        <label for="role_id" class="form-label fw-bold">Peran (Role)</label>
        <select name="role_id" id="role_id" class="form-select form-select-sm" required>
            @foreach($rolesToAssign as $role)
                {{-- Pengecekan role Admin dihapus, karena sudah difilter di Controller --}}
                <option value="{{ $role->id }}" {{ $user->role_id == $role->id ? 'selected' : '' }}>
                    {{ $role->nama_role }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label fw-bold">Password Baru (Opsional)</label>
        <input type="password" name="password" id="password" class="form-control form-control-sm"
               placeholder="Isi untuk mengganti password (min. 6 karakter)">
        <small class="text-muted">Kosongkan jika tidak ingin mengubah.</small>
    </div>

    {{-- FOOTER MODAL KUSTOM (Hanya ada tombol Batal dan Simpan) --}}
    <div class="d-flex justify-content-end gap-2 mt-4">
        {{-- Tombol Batal HANYA menutup modal --}}
        <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary rounded-pill px-3">Simpan Perubahan</button>
    </div>
</form>