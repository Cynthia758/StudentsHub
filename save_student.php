
<?php
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Invalid request method.");
}

function inputValue($key) {
    $value = $_POST[$key] ?? "";
    return is_string($value) ? trim($value) : "";
}

function showMessage($title, $message, $success = false) {
    $color = $success ? "#92702d" : "#b42318";
    $icon = $success ? "✓" : "!";

    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . htmlspecialchars($title, ENT_QUOTES, "UTF-8") . ' | StudentHub</title>
        <link rel="stylesheet" href="css/success.css">
    </head>
    <body>
        <main class="success-container">
            <div class="success-icon" style="color:' . $color . '">' . $icon . '</div>
            <h1>' . htmlspecialchars($title, ENT_QUOTES, "UTF-8") . '</h1>
            <p class="description">' . htmlspecialchars($message, ENT_QUOTES, "UTF-8") . '</p>
            <div class="actions">
                <a class="btn" href="register.php">Back to Registration</a>
                <a class="btn btn-secondary" href="index.html">Home</a>
            </div>
        </main>
    </body>
    </html>';
}

$name = inputValue("name");
$email = inputValue("email");
$studentId = inputValue("student_id");
$department = inputValue("department");
$semesterText = inputValue("semester");
$password = $_POST["password"] ?? "";
$confirmPassword = $_POST["confirm_password"] ?? "";

$errors = [];

if (!preg_match("/^[a-zA-Z ]{3,50}$/", $name)) {
    $errors[] = "Enter a valid name containing 3 to 50 letters and spaces.";
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
    $errors[] = "Enter a valid email address.";
}

if (!preg_match("/^[0-9]{2}[A-Z]{3}[0-9]{3}$/", $studentId)) {
    $errors[] = "Invalid Student ID. Example: 25DCS073.";
}

$departments = [
    "Computer Engineering",
    "Information Technology",
    "Computer Science & Engineering",
    "Mechanical Engineering",
    "Civil Engineering"
];

if (!in_array($department, $departments, true)) {
    $errors[] = "Select a valid department.";
}

if (!preg_match("/^Semester ([1-8])$/", $semesterText, $matches)) {
    $errors[] = "Select a valid semester.";
} else {
    $semester = (int) $matches[1];
}

if (
    !is_string($password) ||
    !preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,72}$/', $password)
) {
    $errors[] = "Password must be 8–72 characters and include a letter, number and special character.";
}

if (!is_string($confirmPassword) || $password !== $confirmPassword) {
    $errors[] = "Passwords do not match.";
}

if ($errors) {
    http_response_code(400);
    showMessage("Registration Failed", implode(" ", $errors));
    exit;
}

try {
    $check = $conn->prepare(
        "SELECT student_id FROM students
         WHERE email = ? OR student_id = ?
         LIMIT 1"
    );

    $check->bind_param("ss", $email, $studentId);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->close();
        http_response_code(409);
        showMessage(
            "Account Already Exists",
            "This email address or student ID is already registered. Please use different details."
        );
        exit;
    }

    $check->close();

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        "INSERT INTO students
         (student_id, name, email, department, semester, password)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "ssssis",
        $studentId,
        $name,
        $email,
        $department,
        $semester,
        $passwordHash
    );

    $stmt->execute();
    $stmt->close();

    showMessage(
        "Registration Successful!",
        "Welcome to StudentHub, " . $name . "! Your account has been created successfully.",
        true
    );

} catch (mysqli_sql_exception $e) {
    error_log("Student registration failed: " . $e->getMessage());

    if ($e->getCode() === 1062) {
        http_response_code(409);
        showMessage(
            "Account Already Exists",
            "This email address or student ID is already registered."
        );
    } else {
        http_response_code(500);
        showMessage(
            "Registration Error",
            "We could not create your account. Please try again."
        );
    }
}
?>
