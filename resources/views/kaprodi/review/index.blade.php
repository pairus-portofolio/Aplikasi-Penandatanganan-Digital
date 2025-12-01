@extends('layouts.app')

@section('title', 'Daftar Review Surat')

@section('content')

  <h1 class="page-title">Daftar Surat Perlu Direview</h1>

  <div class="table-shell">
    <table>
      <thead>
        <tr>
          <th>Judul Surat</th>
          <th>Pengunggah</th>
          <th>Tanggal Pembuat</th>
          <th>Status</th>
          <th>Aksi</th> </tr>
      </thead>
        <tbody>
            @forelse($daftarSurat as $tugas)
                <tr>
                    <td>{{ $tugas['nama'] }}</td>
                    <td>{{ $tugas['pengunggah'] }}</td>
                    <td>{{ $tugas['tanggal'] }}</td>
                    <td>
                        <span class="pill {{ $tugas['status_class'] }}">
                            {{ $tugas['status'] }}
                        </span>
                    </td>

                    <td>
                        {{-- FIX: PAKSA LINK KE HALAMAN REVIEW --}}
                        {{-- Mengabaikan $tugas['action_url'] bawaan controller --}}
                        <a href="{{ route('kaprodi.review.show', $tugas['id_raw']) }}" class="btn-action {{ $tugas['action_class'] }}">
                            {{-- Label tetap 'Kerjakan' atau 'Lihat' --}}
                            {{ $tugas['action_label'] }}
                        </a>
                    </td>
                </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align:center; padding: 20px; color: gray;">
                    Tidak ada surat yang perlu direview.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
  </div>

  <div style="margin-top: 20px;">
      {{ $daftarSurat->links('partials.pagination') }}
  </div>

@endsection

@section('popup')

@endsection