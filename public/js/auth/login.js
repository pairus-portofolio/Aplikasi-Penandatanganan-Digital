// Menunggu seluruh elemen halaman siap
document.addEventListener("DOMContentLoaded", function () {
    // Ambil elemen terkait toggle password
    const togglePassword = document.getElementById("togglePassword");
    const passwordInput = document.getElementById("password");
    const eyeOpen = document.getElementById("eyeOpen");
    const eyeClosed = document.getElementById("eyeClosed");

    // Cegah error jika elemen tidak ditemukan
    if (!togglePassword || !passwordInput || !eyeOpen || !eyeClosed) {
        return;
    }

    // Logika menampilkan/menyembunyikan password
    togglePassword.addEventListener("click", () => {
        const isHidden = passwordInput.type === "password";
        passwordInput.type = isHidden ? "text" : "password";

        // Ganti ikon mata
        eyeOpen.classList.toggle("hidden", isHidden);
        eyeClosed.classList.toggle("hidden", !isHidden);

        // Update atribut aksesibilitas
        togglePassword.setAttribute("aria-pressed", String(isHidden));
        togglePassword.setAttribute(
            "aria-label",
            isHidden ? "Hide password" : "Show password"
        );
    });
});
