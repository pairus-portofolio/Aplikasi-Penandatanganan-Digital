<!DOCTYPE html>
<html>
<head><title>Login Alert</title></head>
<body>
    <h2>Halo, {{ $user->nama_lengkap }}</h2>
    <p>Sistem mendeteksi aktivitas login baru ke akun Anda pada {{ now()->format('d-m-Y H:i') }}.</p>
    <p>Jika ini adalah Anda, silakan abaikan pesan ini.</p>
    <p>Akses aplikasi: <a href="{{ route('auth.login') }}">Klik Disini</a></p>
</body>
</html>