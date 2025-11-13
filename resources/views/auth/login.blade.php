<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LetSign | Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    body { font-family: 'Poppins', sans-serif; }

    /* FIX: sembunyikan ikon reveal bawaan di Edge/IE */
    input[type="password"]::-ms-reveal,
    input[type="password"]::-ms-clear {
      display: none;
    }

    /* FIX: cegah beberapa browser menampilkan kontrol bawaan */
    input[type="password"] {
      appearance: none;
      -webkit-appearance: none;
    }
  </style>
</head>

<body class="min-h-screen bg-[#FAFAF8] flex flex-col md:flex-row items-center justify-center md:justify-between relative overflow-auto py-10">

  <!-- LOGO -->
  <div class="absolute top-4 left-4">
    <img src="{{ asset('images/logo-letsign.png') }}" alt="LetSign Logo" class="w-16 md:w-20 h-auto object-contain">
  </div>

  <!-- CONTAINER UTAMA -->
  <div class="w-full max-w-[1280px] mx-auto flex flex-col md:flex-row items-center justify-center md:justify-between gap-10 lg:gap-20 px-6 sm:px-10 md:px-14 lg:px-20">

    <!-- CONTAINER KEDUA (ilustrasi) -->
    <div class="relative flex justify-center items-center w-full md:w-1/2 px-6 md:px-0 mb-10 md:mb-0">
      <div class="relative w-[320px] sm:w-[400px] md:w-[480px] lg:w-[560px] aspect-square flex items-center justify-center">
        <div class="absolute bg-[#E6EEFF] rounded-[30%] w-[55%] h-[55%] bottom-0 left-0 -z-10"></div>
        <div class="absolute bg-[#E6EEFF] rounded-[30%] w-[55%] h-[55%] top-0 right-0 -z-10"></div>
        <div class="absolute bg-[#2146C7] rounded-[30%] w-[80%] h-[80%] -z-0"></div>
        <img src="{{ asset('images/laptop-letsign.png') }}" alt="Laptop Illustration" class="relative z-10 w-[85%] max-w-[350px] drop-shadow-xl translate-y-2">
      </div>
    </div>

    <!-- BAGIAN KANAN (form login) -->
    <div class="w-full md:w-1/2 flex justify-center md:justify-end">
      <div class="bg-white shadow-xl rounded-2xl p-6 sm:p-8 md:p-10 w-full max-w-[380px] sm:max-w-[400px]">
        <h1 class="text-3xl font-bold text-center text-black mb-6">LET SIGN</h1>

        <!-- ALERT ERROR -->
        @if (session('error'))
        <div class="mb-4 flex items-center bg-red-100 border border-red-300 text-red-700 px-4 py-2 rounded-lg text-sm">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 9v2m0 4h.01M4.93 4.93l14.14 14.14M12 2a10 10 0 100 20 10 10 0 000-20z" />
          </svg>
          {{ session('error') }}
        </div>
        @endif

        <!-- FORM LOGIN -->
        <form method="POST" action="{{ route('login.post') }}" class="flex flex-col gap-4" autocomplete="on">
          @csrf
          <div>
            <label for="email" class="text-sm font-medium text-gray-700">Username or Email</label>
            <input id="email" type="text" name="email" placeholder="Enter your username or email"
              value="{{ old('email') }}"
              class="w-full mt-2 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
              autocomplete="username">
            @error('email')
              <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
          </div>

          <div>
            <label for="password" class="text-sm font-medium text-gray-700">Password</label>
            <div class="relative mt-2">
              <input id="password" type="password" name="password"
                placeholder="Enter your password"
                class="w-full px-4 py-2 pr-11 border border-gray-300 rounded-lg 
                      focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
                autocomplete="current-password">

              <!-- toggle mata -->
              <button type="button" id="togglePassword"
                class="absolute inset-y-0 right-3 flex items-center text-gray-500 hover:text-gray-700 focus:outline-none"
                aria-label="Show password" aria-pressed="false">
                
                <!-- Eye open -->
                <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg"
                  class="w-5 h-5" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>

                <!-- Eye closed -->
                <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg"
                  class="w-5 h-5 hidden" viewBox="0 0 24 24" fill="none"
                  stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19C5 19 1 12 1 12a21.63 21.63 0 0 1 5.06-5.94" />
                  <path d="M9.9 4.24A10.94 10.94 0 0 1 12 5c7 0 11 7 11 7a21.63 21.63 0 0 1-5.06 5.94" />
                  <line x1="1" y1="1" x2="23" y2="23" />
                </svg>
              </button>
            </div>
          </div>


          <button type="submit"
            class="mt-4 bg-[#3B82F6] hover:bg-[#2563EB] text-white font-semibold py-2 rounded-lg transition-all">
            Log in
          </button>
        </form>

        <!-- PEMBATAS -->
        <div class="mt-4 flex items-center justify-center">
          <hr class="w-1/4 border-gray-300">
          <span class="mx-3 text-gray-400 text-sm">or</span>
          <hr class="w-1/4 border-gray-300">
        </div>

        <!-- LOGIN GOOGLE -->
        <a href="{{ route('google.redirect') }}"
          class="mt-4 flex items-center justify-center border border-gray-300 rounded-lg py-2 hover:bg-gray-50 transition-all">
          <img src="https://www.svgrepo.com/show/355037/google.svg" alt="Google" class="w-5 mr-2">
          <span class="text-sm font-medium text-gray-600">Sign in with Google</span>
        </a>
      </div>
    </div>
  </div>

  <!-- SCRIPT SHOW/HIDE PASSWORD -->
  <script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const eyeOpen = document.getElementById('eyeOpen');
    const eyeClosed = document.getElementById('eyeClosed');

    togglePassword.addEventListener('click', () => {
      const isHidden = passwordInput.type === 'password';
      passwordInput.type = isHidden ? 'text' : 'password';

      // perbaikan di sini:
      eyeOpen.classList.toggle('hidden', isHidden);
      eyeClosed.classList.toggle('hidden', !isHidden);

      togglePassword.setAttribute('aria-pressed', String(isHidden));
      togglePassword.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    });
  </script>

</body>
</html>
