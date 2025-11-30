<!DOCTYPE html>
<html>
<head><title>Notifikasi Surat</title></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    
    <h3>Halo, {{ $receiver->nama_lengkap }}</h3>

    {{-- KASUS 1: REVISI --}}
    @if($type == 'revision_request')
        <div style="background-color: #fff3cd; border: 1px solid #ffeeba; padding: 15px; border-radius: 5px; color: #856404;">
            <h4 style="margin-top: 0;">⚠️ Permintaan Revisi Dokumen</h4>
            <p>Dokumen <strong>"{{ $document->judul_surat }}"</strong> dikembalikan karena memerlukan perbaikan.</p>
            
            <p><strong>Catatan Revisi:</strong><br>
            <em style="background-color: #fff; padding: 5px; display: block; border: 1px solid #ddd;">
                {{ $notes ?? 'Tidak ada catatan khusus.' }}
            </em></p>
            
            <p>Silakan login untuk mengunggah perbaikan.</p>
        </div>

    {{-- KASUS 2: SELESAI --}}
    @elseif($type == 'completed')
        <p>Dokumen dengan judul <strong>"{{ $document->judul_surat }}"</strong> telah selesai melewati seluruh proses paraf dan tanda tangan.</p>
        <p>Anda dapat mengunduh hasil akhirnya di sistem.</p>

    {{-- KASUS 3: GILIRAN PROSES (Next Turn) --}}
    @else
        @if(in_array($receiver->role_id, [2, 3])) {{-- Kaprodi --}}
            <p>Anda memiliki dokumen baru yang perlu ditinjau dan diparaf.</p>
            <p><strong>Judul Surat:</strong> {{ $document->judul_surat }}</p>
            <p>Silakan login untuk melakukan Review dan Paraf.</p>
        @else {{-- Kajur/Sekjur --}}
            <p>Anda memiliki dokumen baru yang perlu ditandatangani.</p>
            <p><strong>Judul Surat:</strong> {{ $document->judul_surat }}</p>
            <p>Silakan login untuk melakukan Tanda Tangan.</p>
        @endif
    @endif

    <br>
    <p>
        {{-- [FIX] Ganti route('auth.login') menjadi route('login') --}}
        <a href="{{ route('auth.login') }}" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
            Masuk ke Aplikasi
        </a>
    </p>
    
    <hr>
    <small style="color: #6c757d;">Ini adalah pesan otomatis. Mohon tidak membalas email ini.</small>
</body>
</html>