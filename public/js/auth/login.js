// public/js/login.js

document.addEventListener("DOMContentLoaded", function () {
    // Ambil elemen penting
    const togglePassword = document.getElementById("togglePassword"); // tombol toggle
    const passwordInput = document.getElementById("password"); // input password
    const eyeOpen = document.getElementById("eyeOpen"); // ikon mata terbuka
    const eyeClosed = document.getElementById("eyeClosed"); // ikon mata tertutup

    // Pastikan semua elemen ada
    if (!togglePassword || !passwordInput || !eyeOpen || !eyeClosed) {
        return;
    }

    // Event klik untuk show/hide password
    togglePassword.addEventListener("click", () => {
        const isHidden = passwordInput.type === "password"; // cek apakah hidden
        passwordInput.type = isHidden ? "text" : "password"; // toggle tipe

        // Toggle icon
        eyeOpen.classList.toggle("hidden", isHidden);
        eyeClosed.classList.toggle("hidden", !isHidden);

        // Update atribut ARIA untuk aksesibilitas
        togglePassword.setAttribute("aria-pressed", String(isHidden));
        togglePassword.setAttribute(
            "aria-label",
            isHidden ? "Hide password" : "Show password"
        );
    });
});
