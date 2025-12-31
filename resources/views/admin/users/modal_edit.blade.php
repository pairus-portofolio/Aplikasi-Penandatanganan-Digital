<div class="modal-header border-0 pb-0">
    <h5 class="modal-title fw-bold">Edit Pengguna</h5>
    {{-- PERBAIKAN: Tombol X (btn-close) dihapus agar hanya ada tombol Batal di bawah --}}
</div>

<div class="modal-body pt-4">
    <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label text-muted small fw-bold text-uppercase">Nama Lengkap</label>
            {{-- Tambahkan autocomplete="off" untuk mencegah saran nama otomatis --}}
            <input type="text" name="nama_lengkap" class="form-control form-control-lg bg-light border-0" 
                   value="{{ old('nama_lengkap', $user->nama_lengkap) }}" required autocomplete="off">
        </div>

        <div class="mb-3">
            <label class="form-label text-muted small fw-bold text-uppercase">Email</label>
            {{-- Tambahkan autocomplete="off" --}}
            <input type="email" name="email" class="form-control bg-light border-0" 
                   value="{{ old('email', $user->email) }}" required autocomplete="off">
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label text-muted small fw-bold text-uppercase">Role</label>
                <select name="role_id" class="form-select bg-light border-0">
                    @foreach($rolesToAssign as $role)
                        <option value="{{ $role->id }}" {{ $user->role_id == $role->id ? 'selected' : '' }}>
                            {{ $role->nama_role }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label text-muted small fw-bold text-uppercase">Password Baru</label>
                
                {{-- PERBAIKAN: Tambahkan autocomplete="new-password" --}}
                {{-- Ini memaksa browser untuk TIDAK mengisi otomatis (autofill) password yang tersimpan --}}
                <input type="password" name="password" class="form-control bg-light border-0" 
                       placeholder="(Opsional)" autocomplete="new-password">
                
                <small class="text-muted" style="font-size: 0.7rem;">Isi hanya jika ingin ubah password</small>
            </div>
        </div>

        <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary btn-lg fw-bold">
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>
<div class="modal-footer border-0 justify-content-center pb-4">
    <button type="button" class="btn btn-link text-muted text-decoration-none btn-sm" data-bs-dismiss="modal">Batal</button>
</div>