@extends('layouts.app')

@section('title', 'Manajemen Pengguna')

@section('content')
<div class="container-fluid px-4">

    {{-- PANGGIL CSS EKSTERNAL --}}
    <link rel="stylesheet" href="{{ asset('css/admin/index.css') }}">
    
    {{-- SAPAAN --}}
    <div class="mt-4 mb-4">
        <h2 class="fw-bold text-dark">Selamat Datang, {{ Auth::user()->nama_lengkap }}</h2>
        <p class="text-muted">
            Anda login sebagai <strong>{{ Auth::user()->role->nama_role }}</strong>. 
            Kelola data pengguna aplikasi di sini.
        </p>
    </div>

    <div class="row">
        {{-- KOLOM KIRI: DIPERKECIL (Jadi col-lg-3) --}}
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="fa-solid fa-user-plus me-2"></i>Tambah Anggota</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.users.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control" placeholder="Nama Lengkap..." required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="email@polban.ac.id" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fa-solid fa-lock"></i></span>
                                <input type="password" name="password" class="form-control" placeholder="Min 6 karakter" required>
                            </div>
                        </div>
                        
                        <div class="alert alert-light border-start border-4 border-info py-2 small text-muted">
                            <i class="fa-solid fa-circle-info me-1 text-info"></i> Role: <strong>Dosen</strong>.
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold">
                            <i class="fa-solid fa-save me-1"></i> Simpan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: DIPERBESAR (Jadi col-lg-9) --}}
        <div class="col-lg-9">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark">Daftar Pengguna</h6>
                    <span class="badge bg-light text-dark border">{{ $users->total() }} User</span>
                </div>
                <div class="card-body p-0">
                    
                    {{-- Filter & Search --}}
                    <div class="px-3 py-3 bg-light border-bottom">
                        <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-center">
                            <div class="col-md-5">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                    <input type="text" name="search" class="form-control border-start-0" placeholder="Cari nama..." value="{{ request('search') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select name="role_id" class="form-select">
                                    <option value="">Semua Role</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                                            {{ $role->nama_role }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 d-flex gap-1">
                                <button type="submit" class="btn btn-primary flex-fill">Cari</button>
                                
                                @if(request('search') || request('role_id'))
                                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary" style="min-width: 70px;">
                                        Reset
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>

                    @if($users->count() > 0)
                        <div class="table-responsive">
                            {{-- Perhatikan class 'table-fixed' dan 'table-spacious' --}}
                            <table class="table table-hover mb-0 table-fixed table-spacious">
                                <thead class="bg-light text-muted small text-uppercase">
                                    <tr>
                                        <th class="ps-4" style="width: 30%;">Nama Lengkap</th>
                                        <th style="width: 30%;">Email</th>
                                        <th style="width: 25%;">Role</th>
                                        <th class="text-center" style="width: 15%;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $user)
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark text-truncate" title="{{ $user->nama_lengkap }}">
                                            {{ $user->nama_lengkap }}
                                        </td>
                                        <td class="text-muted small text-truncate" title="{{ $user->email }}">
                                            {{ $user->email }}
                                        </td>
                                        <td>
                                            @php
                                                $roleColor = match($user->role->nama_role ?? '') {
                                                    'Tata Usaha' => 'bg-danger',
                                                    'Koordinator Program Studi' => 'bg-primary',
                                                    'Dosen' => 'bg-success',
                                                    'Ketua Jurusan' => 'bg-warning text-dark',
                                                    'Sekretaris Jurusan' => 'bg-warning text-dark',
                                                    'Administrasi' => 'bg-dark',
                                                    default => 'bg-secondary'
                                                };
                                            @endphp
                                            <span class="badge {{ $roleColor }} rounded-pill fw-normal px-3">
                                                {{ $user->role->nama_role ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-warning btn-edit-user rounded-circle"
                                                        data-id="{{ $user->id }}"
                                                        data-url="{{ route('admin.users.edit', $user->id) }}"
                                                        title="Edit User">
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>
                                                
                                                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="d-inline form-delete">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger btn-delete-user rounded-circle"
                                                            title="Hapus User">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="p-3 border-top">
                            {{ $users->links('partials.pagination') }}
                        </div>
                    @else
                        {{-- Tampilan Kosong --}}
                        <div class="text-center py-5">
                            <div class="mb-3 text-muted">
                                <i class="fa-regular fa-folder-open fa-3x"></i>
                            </div>
                            <h6 class="fw-bold text-dark">Data Tidak Ditemukan</h6>
                            <p class="text-muted small mb-0">Silakan gunakan kata kunci lain atau reset filter pencarian.</p>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div class="mt-5 mb-4 text-center">
        <p class="text-muted small fw-bold" style="letter-spacing: 0.5px;">
            Sistem Manajemen Surat - Politeknik Negeri Bandung
        </p>
    </div>

</div>

{{-- Placeholder Modal Edit --}}
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4" id="editUserModalContent">
            {{-- Content loaded via JS --}}
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    @if(session('success'))
        Swal.fire({
            toast: true,
            icon: 'success',
            title: "{{ session('success') }}",
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    @endif

    @if($errors->any())
        Swal.fire({
            toast: true,
            icon: 'error',
            title: "Terdapat Kesalahan Input",
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000
        });
    @endif

    document.addEventListener('DOMContentLoaded', function() {
        // Handler Edit
        document.body.addEventListener('click', function(e) {
            if (e.target.closest('.btn-edit-user')) {
                const btn = e.target.closest('.btn-edit-user');
                const url = btn.dataset.url;
                
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
                
                fetch(url)
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('editUserModalContent').innerHTML = html;
                        const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
                        modal.show();
                        btn.innerHTML = '<i class="fa-solid fa-pen"></i>';
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        btn.innerHTML = '<i class="fa-solid fa-pen"></i>';
                        Swal.fire('Error', 'Gagal memuat data edit.', 'error');
                    });
            }
        });

        // Handler Hapus
        document.body.addEventListener('click', function(e) {
            if (e.target.closest('.btn-delete-user')) {
                e.preventDefault();
                const form = e.target.closest('.form-delete');
                
                Swal.fire({
                    title: 'Hapus Pengguna?',
                    text: "Data yang dihapus tidak dapat dikembalikan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            }
        });
    });
</script>
@endpush