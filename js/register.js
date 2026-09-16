const registerForm = document.getElementById("registerForm");

registerForm.addEventListener("submit", function(event) {

    event.preventDefault();

    const name = document.getElementById("name").value.trim();
    const email = document.getElementById("email").value.trim();
    const studentId = document.getElementById("studentid").value.trim();
    const department = document.getElementById("department").value;
    const semester = document.getElementById("semester").value;
    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirm").value;

    const namePattern = /^[A-Za-z ]{3,50}$/;

    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    const studentIdPattern = /^[0-9]{2}[A-Z]{3}[0-9]{3}$/;

    const passwordPattern =
        /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;

    if (!namePattern.test(name)) {
        alert("Please enter a valid name.");
        return;
    }

    if (!emailPattern.test(email)) {
        alert("Please enter a valid email address.");
        return;
    }
    if (!studentIdPattern.test(studentId)) {
        alert("Student ID can contain only letters and numbers in a Specified Format.");
        return;
    }
    if (department === "Select Department") {
        alert("Please select your department.");
        return;
    }
    if (semester === "Select Semester") {
        alert("Please select your semester.");
        return;
    }

    if (!passwordPattern.test(password)) {
        alert(
            "Password must be at least 8 characters long and contain a letter, number and special character."
        );
        return;
    }

    if (password !== confirmPassword) {
        alert("Passwords do not match.");
        return;
    }

    alert("Registration successful!");

    registerForm.reset();

});