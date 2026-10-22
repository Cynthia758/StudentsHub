
<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";

if (($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Access denied. Administrator privileges required.");
}

try {
    $studentResult = $conn->query("SELECT COUNT(*) AS total FROM students");
    $studentCount = (int)$studentResult->fetch_assoc()["total"];

    $eventResult = $conn->query("SELECT COUNT(*) AS total FROM events");
    $eventCount = (int)$eventResult->fetch_assoc()["total"];

    $registrationResult = $conn->query(
        "SELECT COUNT(*) AS total FROM registrations"
    );
    $registrationCount = (int)$registrationResult->fetch_assoc()["total"];

    $userResult = $conn->query(
        "SELECT COUNT(*) AS total FROM students"
    );
    $userCount = (int)$userResult->fetch_assoc()["total"];

    $latestStmt = $conn->prepare(
        "SELECT r.registration_id, r.registration_date,
                s.student_id, s.name AS student_name, s.email,
                e.title AS event_title
         FROM registrations r
         INNER JOIN students s ON r.student_id = s.student_id
         INNER JOIN events e ON r.event_id = e.event_id
         ORDER BY r.registration_date DESC
         LIMIT 10"
    );

    $latestStmt->execute();
    $latestRegistrations = $latestStmt->get_result();

} catch (mysqli_sql_exception $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
    http_response_code(500);
    exit("Unable to load dashboard statistics. Please check the database.");
}

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | StudentHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 28px;
            background: #f5f3ef;
            color: #292720;
            font-family: Poppins, sans-serif;
        }

        main { max-width: 1200px; margin: auto; }

        .topbar, .panel, .stat {
            background: #fff;
            border: 1px solid #e8e3da;
            border-radius: 14px;
        }

        .topbar {
            padding: 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 22px;
        }

        h1 { font-size: 27px; margin: 0 0 6px; }
        h2 { font-size: 19px; margin-top: 0; }
        p { line-height: 1.6; }

        .muted { color: #77746d; font-size: 13px; }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .stat { padding: 22px; }
        .stat h2 { font-size: 14px; color: #77746d; }
        .number { font-size: 32px; font-weight: 700; }

        .panel { padding: 22px; margin-bottom: 22px; }

        .menu {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .menu a, .button {
            display: inline-block;
            padding: 12px 15px;
            border-radius: 8px;
            background: #403d32;
            color: white;
            text-decoration: none;
            font-size: 13px;
        }

        .secondary { background: #e9e5dc !important; color: #292720 !important; }
        .danger { background: #a83c35 !important; }

        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 650px; }
        th, td { padding: 13px 10px; border-bottom: 1px solid #eeeae3; text-align: left; font-size: 13px; }
        th { background: #f8f6f2; }

        @media (max-width: 850px) {
            .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .menu { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 520px) {
            body { padding: 12px; }
            .stats, .menu { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<main>
    <header class="topbar">
        <div>
            <h1>Admin Dashboard</h1>
            <p class="muted">Welcome, <?= h($_SESSION["name"] ?? "Administrator") ?>. Here's your StudentHub overview.</p>
        </div>
        <a class="button danger" href="logout.php">Logout</a>
    </header>

    <section class="stats">
        <article class="stat">
            <h2>Total Students</h2>
            <div class="number"><?= $studentCount ?></div>
        </article>

        <article class="stat">
            <h2>Total Events</h2>
            <div class="number"><?= $eventCount ?></div>
        </article>

        <article class="stat">
            <h2>Total Registrations</h2>
            <div class="number"><?= $registrationCount ?></div>
        </article>

        <article class="stat">
            <h2>Total User Accounts</h2>
            <div class="number"><?= $userCount ?></div>
            <p class="muted">Counts accounts in the students table.</p>
        </article>
    </section>

    <section class="panel">
        <h2>Management Menu</h2>
        <div class="menu">
            <a href="student_management.php">Manage Students</a>
            <a href="event_management.php">Manage Events</a>
            <a href="events_api.html">Events API Demo</a>
            <a href="dashboard.php" class="secondary">Student Dashboard</a>
            <a href="profile.html" class="secondary">Profile Page</a>
            <a href="logout.php" class="secondary">Sign Out</a>
        </div>
    </section>

    <section class="panel">
        <h2>Latest Registrations</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Registration ID</th>
                        <th>Student ID</th>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Event</th>
                        <th>Registration Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($latestRegistrations->num_rows === 0): ?>
                    <tr>
                        <td colspan="6">No registrations found yet.</td>
                    </tr>
                <?php else: ?>
                    <?php while ($row = $latestRegistrations->fetch_assoc()): ?>
                        <tr>
                            <td><?= (int)$row["registration_id"] ?></td>
                            <td><?= h($row["student_id"]) ?></td>
                            <td><?= h($row["student_name"]) ?></td>
                            <td><?= h($row["email"]) ?></td>
                            <td><?= h($row["event_title"]) ?></td>
                            <td><?= h($row["registration_date"]) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
<?php
$latestStmt->close();
$conn->close();
?>
