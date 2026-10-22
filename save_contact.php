
<?php
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Invalid request method.");
}

function getInput($key) {
    $value = $_POST[$key] ?? "";
    return is_string($value) ? trim($value) : "";
}

$name = getInput("name");
$email = getInput("email");
$subject = getInput("subject");
$message = getInput("message");

$errors = [];

if ($name === "" || strlen($name) > 100) {
    $errors[] = "Name is required and must be at most 100 characters.";
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
    $errors[] = "Please enter a valid email address.";
}

if ($subject === "" || strlen($subject) > 150) {
    $errors[] = "Subject is required and must be at most 150 characters.";
}

if ($message === "" || strlen($message) > 2000) {
    $errors[] = "Message is required and must be at most 2000 characters.";
}

function escapeOutput($value) {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

if (!empty($errors)) {
    http_response_code(400);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Contact Form Error</title>
    </head>
    <body>
        <h2>Unable to Send Message</h2>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= escapeOutput($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <a href="contact.html">Return to Contact Form</a>
    </body>
    </html>
    <?php
    exit;
}

$dataDirectory = __DIR__ . "/data";

if (!is_dir($dataDirectory) &&
    !mkdir($dataDirectory, 0750, true)) {
    http_response_code(500);
    exit("Unable to prepare data storage.");
}

$file = $dataDirectory . "/contacts.csv";
$handle = fopen($file, "c+");

if ($handle === false) {
    http_response_code(500);
    exit("Unable to save your message. Please try again.");
}

if (!flock($handle, LOCK_EX)) {
    fclose($handle);
    http_response_code(500);
    exit("Unable to save your message. Please try again.");
}

fseek($handle, 0, SEEK_END);

if (ftell($handle) === 0) {
    fputcsv($handle, ["Name", "Email", "Subject", "Message"]);
}

$saved = fputcsv($handle, [
    $name,
    $email,
    $subject,
    $message
]);

fflush($handle);
flock($handle, LOCK_UN);
fclose($handle);

if ($saved === false) {
    http_response_code(500);
    exit("Your message could not be saved.");
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Sent | StudentHub</title>
    <link rel="stylesheet" href="css/success.css">
</head>
<body>
    <main class="success-container">
        <div class="success-icon" aria-hidden="true">✓</div>

        <p class="eyebrow">Thank You</p>

        <h1>Message Sent Successfully!</h1>

        <p class="description">
            Thank you,
            <?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?>.
        </p>

        <p class="description">
            Your message has been saved. We'll get back to you as soon as possible.
        </p>

        <div class="actions">
            <a href="contact.html" class="btn">Back to Contact</a>
            <a href="index.html" class="btn btn-secondary">Return Home</a>
        </div>

        <p class="footer-note">StudentHub · Empowering students through technology and innovation.</p>
    </main>
</body>
</html>
