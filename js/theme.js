document.addEventListener("DOMContentLoaded", function () {

    const themeToggle = document.getElementById("themeToggle");

    if (!themeToggle) {
        return;
    }

    themeToggle.addEventListener("click", function () {

        document.body.classList.toggle("dark-mode");

        if (document.body.classList.contains("dark-mode")) {
            themeToggle.textContent = "☀️ Light";
        } else {
            themeToggle.textContent = "🌙 Dark";
        }

    });

});