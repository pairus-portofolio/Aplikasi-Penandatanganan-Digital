<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Judul halaman login -->
  <title>LetSign | Login</title>

  <!-- Import font utama -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Import Tailwind melalui CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Style dasar untuk halaman login -->
  <style>
    body { font-family: 'Poppins', sans-serif; }

    /* Sembunyikan icon default untuk input password di Edge/IE */
    input[type="password"]::-ms-reveal,
    input[type="password"]::-ms-clear {
      display: none;
    }

    /* Hilangkan style default browser untuk input password */
    input[type="password"] {
      appearance: none;
      -webkit-appearance: none;
    }
  </style>
</head>

<body class="min-h-screen bg-[#FAFAF8] flex flex-col md:flex-row items-center justify-center md:justify-between relative overflow-auto py-10">

  <!-- Logo pojok kiri atas -->
  <div class="absolute top-4 left-4">
    <img src="{{ asset('images/logo-letsign.png') }}" alt="LetSign Logo" class="w-16 md:w-20 h-auto object-contain">
  </div>

  <!-- Container utama layout login -->
  <div class="w-full max-w-[1280px] mx-auto flex flex-col md:flex-row items-center justify-center md:justify-between gap-10 lg:gap-20 px-6 sm:px-10 md:px-14 lg:px-20">

    <!-- Ilustrasi laptop pada sisi kiri -->
    <div class="relative flex justify-center items-center w-full md:w-1/2 px-6 md:px-0 mb-10 md:mb-0">
      <div class="relative w-[320px] sm:w-[400px] md:w-[480px] lg:w-[560px] aspect-square flex items-center justify-center">

        <!-- Ornamen background ilustrasi -->
        <div class="absolute bg-[#E6EEFF] rounded-[30%] w-[55%] h-[55%] bottom-0 left-0 -z-10"></div>
        <div class="absolute bg-[#E6EEFF] rounded-[30%] w-[55%] h-[55%] top-0 right-0 -z-10"></div>
        <div class="absolute bg-[#2146C7] rounded-[30%] w-[80%] h-[80%] -z-0"></div>

        <!-- Gambar ilustrasi laptop -->
        <img src="{{ asset('images/laptop-letsign.png') }}" alt="Laptop Illustration" class="relative z-10 w-[85%] max-w-[350px] drop-shadow-xl translate-y-2">
      </div>
    </div>

    <!-- Container form login -->
    <div class="w-full md:w-1/2 flex justify-center md:justify-end">

      <!-- Card form login -->
      <div class="bg-white shadow-xl rounded-2xl p-6 sm:p-8 md:p-10 w-full max-w-[380px] sm:max-w-[400px]">

        <!-- Judul aplikasi -->
        <h1 class="text-3xl font-bold text-center text-black mb-6">LET SIGN</h1>

        <!-- Menampilkan error login jika ada -->
        @if (session('error'))
        <div class="mb-4 flex items-center bg-red-100 border border-red-300 text-red-700 px-4 py-2 rounded-lg text-sm">
          <div class="w-5 h-5 mr-2 flex-shrink-0">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-red-700">
              <path d="M10.29 3.86L1.82 18a1 1 0 0 0 .86 1.5h18.64a1 1 0 0 0 .86-1.5L13.71 3.86a1 1 0 0 0-1.72 0z"></path>
              <line x1="12" y1="9" x2="12" y2="13"></line>
              <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
          </div>
          <span>{{ session('error') }}</span>
        </div>
        @endif

        <!-- Form proses login -->
        <form method="POST" action="{{ route('login.post') }}" class="flex flex-col gap-4" autocomplete="on">
          @csrf

          <!-- Input username/email -->
          <div>
            <label for="email" class="text-sm font-medium text-gray-700">Username or Email</label>
            <input id="email" type="text" name="email"
              placeholder="Enter your username or email"
              value="{{ old('email') }}"
              class="w-full mt-2 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 transition"
              autocomplete="username">
            @error('email')
              <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
          </div>

          <!-- Input password + tombol reveal -->
          <div>
            <label for="password" class="text-sm font-medium text-gray-700">Password</label>

            <div class="relative mt-2">
              <input id="password" type="password" name="password"
                placeholder="Enter your password"
                class="w-full px-4 py-2 pr-11 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 transition"
                autocomplete="current-password">

              <!-- Tombol show/hide password -->
              <button type="button" id="togglePassword"
                class="absolute inset-y-0 right-3 flex items-center text-gray-500 hover:text-gray-700"
                aria-label="Show password" aria-pressed="false">

                <!-- Icon mata terbuka -->
                <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg"
                    class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.6"
                    stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>

                <!-- Icon mata tertutup -->
                <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg"
                    class="w-5 h-5 hidden" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.6"
                    stroke-linecap="round" stroke-linejoin="round">
                  <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19C5 19 1 12 1 12a21.63 21.63 0 0 1 5.06-5.94" />
                  <path d="M9.9 4.24A10.94 10.94 0 0 1 12 5c7 0 11 7 11 7a21.63 21.63 0 0 1-5.06 5.94" />
                  <line x1="1" y1="1" x2="23" y2="23" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Tombol submit login -->
          <button type="submit"
            class="mt-4 bg-[#3B82F6] hover:bg-[#2563EB] text-white font-semibold py-2 rounded-lg transition-all">
            Log in
          </button>
        </form>

        <!-- Garis pemisah login & Google -->
        <div class="mt-4 flex items-center justify-center">
          <hr class="w-1/4 border-gray-300">
          <span class="mx-3 text-gray-400 text-sm">or</span>
          <hr class="w-1/4 border-gray-300">
        </div>

        <!-- Tombol login Google -->
        <a href="{{ route('google.redirect') }}"
          class="mt-4 flex items-center justify-center border border-gray-300 rounded-lg py-2 hover:bg-gray-50 transition">
          <img src="https://www.svgrepo.com/show/355037/google.svg" class="w-5 mr-2">
          <span class="text-sm font-medium text-gray-600">Sign in with Google</span>
        </a>

      </div>
    </div>
  </div>

  <!-- Script toggle password -->
  <script src="{{ asset('js/auth/login.js') }}"></script>

</body>
</html>
