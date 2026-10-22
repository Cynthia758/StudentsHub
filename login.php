
<?php

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/session.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.html");
    exit;
}

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if (
    !is_string($_POST["email"] ?? null) ||
    !is_string($password) ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($email) > 254 ||
    $password === "" ||
    strlen($password) > 1024
) {
    http_response_code(400);
    exit("Please enter a valid email and password.");
}

try {
    $stmt = $conn->prepare(
        "SELECT student_id, name, email, password, role
         FROM students
         WHERE email = ?
         LIMIT 1"
    );

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $stmt->close();

    if (!$user || !password_verify($password, $user["password"])) {
        http_response_code(401);
        exit(
            'Invalid email or password. <a href="login.html">Try again</a>'
        );
    }

    session_regenerate_id(true);

    $_SESSION["user_id"] = $user["student_id"];
    $_SESSION["name"] = $user["name"];
    $_SESSION["email"] = $user["email"];
    $_SESSION["role"] = $user["role"];
    $_SESSION["last_activity"] = time();

    if ($user["role"] === "admin") {
        header("Location: admin.php");
    } else {
        header("Location: dashboard.php");
    }

    exit;

} catch (mysqli_sql_exception $e) {
    error_log("Login database error: " . $e->getMessage());
    http_response_code(500);
    exit("Unable to log in right now. Please try again later.");
}

?>
