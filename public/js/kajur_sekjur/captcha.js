let captchaVerified = false;

function onCaptchaSuccess() {
    captchaVerified = true;

    document.getElementById("captchaBox").style.display = "none";

    setTimeout(() => {
        document.getElementById("parafImageUpload").click();
    }, 150);

    grecaptcha.reset();
}
