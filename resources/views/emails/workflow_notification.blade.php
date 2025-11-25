<!DOCTYPE html>
<html>
<head><title>Notifikasi Surat</title></head>
<body>
    <h3>Halo, {{ $receiver->nama_lengkap }}</h3>

    @if($type == 'completed')
        <p>Dokumen dengan judul <strong>"{{ $document->judul_surat }}"</strong> telah selesai melewati seluruh proses paraf dan tanda tangan.</p>
        <p>Anda dapat mengunduh hasil akhirnya di sistem.</p>
    @else
        @if(in_array($receiver->role_id, [2, 3]))
            <p>Anda memiliki dokumen baru yang perlu ditinjau dan diparaf.</p>
            <p><strong>Judul Surat:</strong> {{ $document->judul_surat }}</p>
            
            <p>Silakan login untuk melakukan Review dan Paraf.</p>
        @else
            <p>Anda memiliki dokumen baru yang perlu ditandatangani.</p>
            <p><strong>Judul Surat:</strong> {{ $document->judul_surat }}</p>
            
            <p>Silakan login untuk melakukan Tanda Tangan.</p>
        @endif
    @endif

    <br>
    <p>
        <a href="{{ route('auth.login') }}" style="background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
            Masuk ke Aplikasi
        </a>
    </p>
    
    <hr>
    <small>Ini adalah pesan otomatis. Mohon tidak membalas email ini.</small>
</body>
</html>