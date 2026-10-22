
<?php

require_once __DIR__ . "/auth.php";

if (($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Access denied. Administrator privileges required.");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>StudentHub | Admin</title>

<link rel="stylesheet" href="css/admin.css">

</head>

<body>

<header class="dashboard-header">

    <div class="logo">
        <a href="index.html">
            StudentHub Admin
        </a>
    </div>

    <div class="dashboard-nav">
        <a href="login.html">
            Logout
        </a>
        <button id="themeToggle" type="button">🌙 Dark</button>

    </div>

</header>
<section class="dashboard">

<aside class="sidebar">

<h3>

Admin Panel

</h3>

<?php if (($_SESSION["role"] ?? "") === "admin"): ?>
    <a href="admin_dashboard.php">Admin Dashboard</a>
    <a href="student_management.php">Manage Students</a>
    <a href="event_management.php">Manage Events</a>
<?php else: ?>
    <a href="dashboard.php">My Dashboard</a>
    <a href="events.html">Events</a>
    <a href="profile.html">My Profile</a>
<?php endif; ?>

</aside>



<main class="dashboard-content">

<section class="welcome-card">

<h1>

Welcome, Admin

</h1>

<p>

Manage students, events and announcements.

</p>

</section>



<section class="stats">

<div class="stat-card">

<h2>5200</h2>

<p>Students</p>

</div>

<div class="stat-card">

<h2>165</h2>

<p>Faculty</p>

</div>

<div class="stat-card">

<h2>32</h2>

<p>Events</p>

</div>

<div class="stat-card">

<h2>184</h2>

<p>Feedbacks</p>

</div>

</section>



<section class="dashboard-section">

<h2>

Recent Activities

</h2>

<ul>

<li>✔ New Event Added</li>

<li>✔ Student Registered</li>

<li>✔ Announcement Published</li>

<li>✔ Feedback Reviewed</li>

</ul>

</section>

</main>

</section>
<script src="js/theme.js"></script>
</body>

</html>