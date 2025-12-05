let captchaVerified = false;
let captchaModalInstance = null;

// Initialize modal instance when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    const captchaModalEl = document.getElementById('captchaModal');
    if (captchaModalEl) {
        captchaModalInstance = new bootstrap.Modal(captchaModalEl, {
            backdrop: 'static',
            keyboard: false
        });
    }
});

// Function to show captcha modal
function showCaptchaModal() {
    if (captchaModalInstance) {
        captchaModalInstance.show();
    }
}

// Callback when captcha is successfully verified
function onCaptchaSuccess() {
    captchaVerified = true;
    window.lastCaptchaToken = grecaptcha.getResponse();

    // Close the modal
    if (captchaModalInstance) {
        captchaModalInstance.hide();
    }

    // Trigger file upload after a short delay
    setTimeout(() => {
        const uploadInput = document.getElementById("parafImageUpload");
        if (uploadInput) {
            uploadInput.click();
        }
    }, 300);
}

