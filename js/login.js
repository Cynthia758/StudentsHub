const loginForm = document.getElementById("loginform");
loginForm.addEventListener("submit", function(event) {

    event.preventDefault();

    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value.trim();
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (email === "") {
        alert("Please enter your email address.");
        return;
    }

    if (!emailPattern.test(email)) {
        alert("Please enter a valid email address.");
        return;
    }
    if (password === "") {
        alert("Please enter your password.");
        return;
    }
    if (password.length < 6) {
        alert("Password must contain at least 6 characters.");
        return;
    }
    alert("Login successful!");
    loginForm.reset();
});