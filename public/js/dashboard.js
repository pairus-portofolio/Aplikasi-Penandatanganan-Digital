(function () {
    const hb = document.getElementById("hb");
    const html = document.documentElement;
    const overlay = document.getElementById("overlay");

    const isMobile = () => window.matchMedia("(max-width: 992px)").matches;

    hb.addEventListener("click", () => {
        if (isMobile()) {
            document.body.classList.toggle("show-sb");
        } else {
            html.classList.toggle("collapsed");
        }
    });

    overlay.addEventListener("click", () =>
        document.body.classList.remove("show-sb")
    );
    window.addEventListener("resize", () => {
        if (!isMobile()) document.body.classList.remove("show-sb");
    });

    // Popup Logout
    const logoutBtn = document.getElementById("logoutBtn");
    const popup = document.getElementById("logoutPopup");
    const cancelBtn = document.getElementById("cancelLogout");
    logoutBtn.addEventListener("click", () => popup.classList.add("show"));
    cancelBtn.addEventListener("click", () => popup.classList.remove("show"));
})();
