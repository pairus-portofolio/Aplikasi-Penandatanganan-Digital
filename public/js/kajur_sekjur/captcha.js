let captchaVerified = false;

function onCaptchaSuccess() {
    captchaVerified = true;
    window.lastCaptchaToken = grecaptcha.getResponse();

    document.getElementById("captchaBox").style.display = "none";

    setTimeout(() => {
        document.getElementById("parafImageUpload").click();
    }, 150);
}
