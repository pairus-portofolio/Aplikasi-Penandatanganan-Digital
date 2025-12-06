@extends('layouts.app')

@section('title', 'Manajemen Pengguna')

@section('content')

  <h1 class="page-title">Manajemen Pengguna</h1>

  @if(session('success'))
    {{-- Menggunakan Toast untuk notifikasi sukses --}}
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            Swal.fire({ toast: true, icon: 'success', title: '{{ session('success') }}', position: 'top-end', timer: 2500, showConfirmButton: false });
        });
    </script>
  @endif

  @if ($errors->any())
    {{-- Menggunakan Swal Fire untuk notifikasi error --}}
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            Swal.fire({ icon: 'error', title: 'Aksi Gagal', html: `{!! implode('<br>', $errors->all()) !!}` });
        });
    </script>
  @endif
  
  {{-- ALERT UNTUK INFORMASI TELAH DIHAPUS --}}

  <div class="table-shell">
    <table>
      <thead>
        <tr>
          <th>Nama Lengkap</th>
          <th>Email</th>
          <th>Role Saat Ini</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse($users as $user)
          <tr>
            <td>{{ $user->nama_lengkap }}</td>
            <td>{{ $user->email }}</td>
            <td>
                <span class="pill abu">{{ $user->role->nama_role ?? 'Tidak Ada Role' }}</span>
            </td>
            <td>
              {{-- Tombol Edit yang memicu ModalManager --}}
              <button type="button" 
                      class="btn-action btn-primary"
                      data-modal-url="{{ route('admin.users.edit', $user->id) }}"
                      data-modal-title="Edit Pengguna: {{ $user->nama_lengkap }}" 
                      data-modal="edit-user"> {{-- Gunakan kunci 'edit-user' --}}
                Edit
              </button>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="4" style="text-align:center;color:#94a3b8;padding:22px">
              Tidak ada pengguna yang perlu diatur.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div style="margin-top: 20px;">
      {{ $users->links('partials.pagination') }}
  </div>

@endsection

@push('scripts')
    {{-- Menghapus script manual sebelumnya karena logika sudah dipindahkan ke modal-manager.js --}}
@endpush

@push('styles')
    {{-- Custom styles untuk override agar form select tampil rapi --}}
    <style>
        .page-title { margin-bottom: 25px; }
        .form-select-sm {
            height: calc(1.5em + 0.75rem + 2px);
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
            border-radius: 0.2rem;
            box-shadow: none !important;
            border-color: #e5e7eb;
        }
        .form-select-sm:focus {
            border-color: #2563eb;
        }
        .btn-action.btn-primary {
             padding: 6px 12px;
             font-size: 13px;
        }
    </style>
@endpush