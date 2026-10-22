
<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";

if (($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Access denied. Administrator privileges required.");
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";
$messageType = "success";

function redirectWithMessage($message, $type = "success") {
    header("Location: student_management.php?message=" . urlencode($message) . "&type=" . urlencode($type));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")) {
        http_response_code(403);
        exit("Invalid request token. Refresh the page and try again.");
    }

    $action = $_POST["action"] ?? "";
    $studentId = trim($_POST["student_id"] ?? "");
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $department = trim($_POST["department"] ?? "");
    $semester = filter_var($_POST["semester"] ?? null, FILTER_VALIDATE_INT);
    $password = $_POST["password"] ?? "";

    try {
        if ($action === "add") {
            if (
                $studentId === "" ||
                $name === "" ||
                !filter_var($email, FILTER_VALIDATE_EMAIL) ||
                $department === "" ||
                $semester === false ||
                $semester < 1 ||
                $semester > 8 ||
                strlen($password) < 8 ||
                strlen($password) > 72 ||
                !preg_match('/[A-Za-z]/', $password) ||
                !preg_match('/[0-9]/', $password) ||
                !preg_match('/[^A-Za-z0-9]/', $password)
            ) {
                redirectWithMessage("Enter valid details. Password must be 8–72 characters and contain letters, numbers and a special character.", "error");
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO students (student_id, name, email, department, semester, password, role)
                 VALUES (?, ?, ?, ?, ?, ?, 'student')"
            );
            $stmt->bind_param("ssssis", $studentId, $name, $email, $department, $semester, $hash);
            $stmt->execute();
            $stmt->close();

            redirectWithMessage("Student added successfully.");
        }

        if ($action === "edit") {
            if (
                $studentId === "" ||
                $name === "" ||
                !filter_var($email, FILTER_VALIDATE_EMAIL) ||
                $department === "" ||
                $semester === false ||
                $semester < 1 ||
                $semester > 8
            ) {
                redirectWithMessage("Please enter valid student details.", "error");
            }

            $stmt = $conn->prepare(
                "UPDATE students
                 SET name = ?, email = ?, department = ?, semester = ?
                 WHERE student_id = ?"
            );
            $stmt->bind_param("sssis", $name, $email, $department, $semester, $studentId);
            $stmt->execute();
            $changed = $stmt->affected_rows;
            $stmt->close();

            if ($changed > 0) {
                redirectWithMessage("Student updated successfully.");
            }

            $check = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
            $check->bind_param("s", $studentId);
            $check->execute();
            $exists = $check->get_result()->num_rows > 0;
            $check->close();

            redirectWithMessage(
                $exists ? "No changes were made." : "Student record not found.",
                $exists ? "success" : "error"
            );
        }

        if ($action === "delete") {
            if ($studentId === "") {
                redirectWithMessage("Student ID is required.", "error");
            }

            $stmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
            $stmt->bind_param("s", $studentId);
            $stmt->execute();
            $deleted = $stmt->affected_rows;
            $stmt->close();

            redirectWithMessage(
                $deleted ? "Student deleted successfully." : "Student record not found.",
                $deleted ? "success" : "error"
            );
        }

        redirectWithMessage("Unknown action.", "error");

    } catch (mysqli_sql_exception $e) {
        error_log("Student management error: " . $e->getMessage());

        if ((int)$e->getCode() === 1062) {
            redirectWithMessage("Student ID or email already exists.", "error");
        }

        redirectWithMessage("Database operation failed. Please check the details and try again.", "error");
    }
}

if (isset($_GET["message"])) {
    $message = (string)$_GET["message"];
    $messageType = ($_GET["type"] ?? "") === "error" ? "error" : "success";
}

$search = trim($_GET["search"] ?? "");
$departmentFilter = trim($_GET["department"] ?? "");
$semesterFilter = trim($_GET["semester"] ?? "");

if ($semesterFilter !== "" && !ctype_digit($semesterFilter)) {
    $semesterFilter = "";
}

$searchLike = "%" . $search . "%";

$stmt = $conn->prepare(
    "SELECT student_id, name, email, department, semester, role
     FROM students
     WHERE (
         student_id LIKE ?
         OR name LIKE ?
         OR email LIKE ?
     )
     AND (? = '' OR department = ?)
     AND (? = '' OR semester = ?)
     ORDER BY name ASC"
);

$stmt->bind_param(
    "sssssss",
    $searchLike,
    $searchLike,
    $searchLike,
    $departmentFilter,
    $departmentFilter,
    $semesterFilter,
    $semesterFilter
);
$stmt->execute();
$students = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management | StudentHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 28px;
            font-family: Poppins, sans-serif;
            background: #f5f3ef;
            color: #292720;
        }
        .container { max-width: 1200px; margin: auto; }
        .topbar, .panel {
            background: #fff;
            border: 1px solid #e8e3da;
            border-radius: 14px;
            padding: 22px;
            margin-bottom: 22px;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        h1, h2 { margin-top: 0; }
        h1 { font-size: 26px; }
        h2 { font-size: 19px; }
        .muted { color: #77746d; font-size: 13px; }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }
        label { display: block; font-size: 13px; margin-bottom: 6px; }
        input, select {
            width: 100%;
            padding: 11px;
            border: 1px solid #d8d3ca;
            border-radius: 8px;
            font: inherit;
            background: #fff;
        }
        button, .btn {
            display: inline-block;
            border: 0;
            border-radius: 8px;
            padding: 10px 15px;
            background: #403d32;
            color: white;
            font: inherit;
            font-size: 13px;
            text-decoration: none;
            cursor: pointer;
        }
        .secondary { background: #e9e5dc; color: #292720; }
        .danger { background: #a83c35; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .message { padding: 13px; border-radius: 8px; margin-bottom: 18px; }
        .success { background: #e4f4e8; color: #205b32; }
        .error { background: #fde8e6; color: #8b2620; }
        .filters {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto auto;
            gap: 10px;
            align-items: end;
        }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 760px; }
        th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #eeeae3; font-size: 13px; }
        th { background: #f8f6f2; }
        .inline-form { display: inline; }
        @media (max-width: 760px) {
            body { padding: 14px; }
            .form-grid, .filters { grid-template-columns: 1fr; }
            .topbar { align-items: flex-start; }
        }
    </style>
</head>
<body>
<div class="container">

    <header class="topbar">
        <div>
            <h1>Student Management</h1>
            <div class="muted">Add, view, edit, delete, search and filter student records.</div>
        </div>
        <div class="actions">
            <a class="btn secondary" href="admin.php">Admin Panel</a>
            <a class="btn secondary" href="dashboard.php">Dashboard</a>
            <a class="btn danger" href="logout.php">Logout</a>
        </div>
    </header>

    <?php if ($message !== ""): ?>
        <div class="message <?= $messageType === "error" ? "error" : "success" ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <section class="panel">
        <h2>Add a Student</h2>
        <p class="muted">New accounts are assigned the student role by default.</p>

        <form method="POST" action="student_management.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>">
            <input type="hidden" name="action" value="add">

            <div class="form-grid">
                <div>
                    <label for="student_id">Student ID</label>
                    <input id="student_id" name="student_id" required maxlength="30" placeholder="e.g. 25DCS073">
                </div>
                <div>
                    <label for="name">Full Name</label>
                    <input id="name" name="name" required maxlength="100">
                </div>
                <div>
                    <label for="email">Email Address</label>
                    <input id="email" name="email" type="email" required maxlength="254">
                </div>
                <div>
                    <label for="department">Department</label>
                    <input id="department" name="department" required maxlength="100" placeholder="e.g. CSE">
                </div>
                <div>
                    <label for="semester">Semester</label>
                    <select id="semester" name="semester" required>
                        <option value="">Select semester</option>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?= $i ?>">Semester <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label for="password">Initial Password</label>
                    <input id="password" name="password" type="password" required minlength="8" maxlength="72" autocomplete="new-password">
                </div>
            </div>

            <p class="muted">Password must contain letters, numbers and a special character.</p>
            <button type="submit">Add Student</button>
        </form>
    </section>

    <section class="panel">
        <h2>Search and Filter</h2>

        <form method="GET" action="student_management.php" class="filters">
            <div>
                <label for="search">Search by ID, name or email</label>
                <input id="search" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, "UTF-8") ?>" placeholder="Search students...">
            </div>
            <div>
                <label for="filter_department">Department</label>
                <input id="filter_department" name="department" value="<?= htmlspecialchars($departmentFilter, ENT_QUOTES, "UTF-8") ?>" placeholder="e.g. CSE">
            </div>
            <div>
                <label for="filter_semester">Semester</label>
                <select id="filter_semester" name="semester">
                    <option value="">All semesters</option>
                    <?php for ($i = 1; $i <= 8; $i++): ?>
                        <option value="<?= $i ?>" <?= $semesterFilter === (string)$i ? "selected" : "" ?>>
                            Semester <?= $i ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit">Apply</button>
            <a class="btn secondary" href="student_management.php">Reset</a>
        </form>
    </section>

    <section class="panel">
        <h2>Student Records</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Semester</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($students->num_rows === 0): ?>
                    <tr><td colspan="7">No matching student records found.</td></tr>
                <?php else: ?>
                    <?php while ($student = $students->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($student["student_id"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars($student["name"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars($student["email"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars($student["department"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= (int)$student["semester"] ?></td>
                            <td><?= htmlspecialchars($student["role"], ENT_QUOTES, "UTF-8") ?></td>
                            <td>
                                <div class="actions">
                                    <button type="button" class="secondary edit-button"
                                        data-id="<?= htmlspecialchars($student["student_id"], ENT_QUOTES, "UTF-8") ?>"
                                        data-name="<?= htmlspecialchars($student["name"], ENT_QUOTES, "UTF-8") ?>"
                                        data-email="<?= htmlspecialchars($student["email"], ENT_QUOTES, "UTF-8") ?>"
                                        data-department="<?= htmlspecialchars($student["department"], ENT_QUOTES, "UTF-8") ?>"
                                        data-semester="<?= (int)$student["semester"] ?>">
                                        Edit
                                    </button>

                                    <form method="POST" action="student_management.php" class="inline-form"
                                        onsubmit="return confirm('Delete this student? Related event registrations may also be deleted.');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="student_id" value="<?= htmlspecialchars($student["student_id"], ENT_QUOTES, "UTF-8") ?>">
                                        <button type="submit" class="danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel" id="edit-panel" hidden>
        <h2>Edit Student</h2>
        <form method="POST" action="student_management.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="student_id" id="edit_student_id">

            <div class="form-grid">
                <div>
                    <label for="edit_name">Full Name</label>
                    <input id="edit_name" name="name" required maxlength="100">
                </div>
                <div>
                    <label for="edit_email">Email</label>
                    <input id="edit_email" name="email" type="email" required maxlength="254">
                </div>
                <div>
                    <label for="edit_department">Department</label>
                    <input id="edit_department" name="department" required maxlength="100">
                </div>
                <div>
                    <label for="edit_semester">Semester</label>
                    <select id="edit_semester" name="semester" required>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <option value="<?= $i ?>">Semester <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <p class="muted">The student ID, password and role are not changed by this form.</p>
            <div class="actions">
                <button type="submit">Save Changes</button>
                <button type="button" class="secondary" id="cancel-edit">Cancel</button>
            </div>
        </form>
    </section>

</div>

<script>
document.querySelectorAll(".edit-button").forEach(function (button) {
    button.addEventListener("click", function () {
        document.getElementById("edit_student_id").value = this.dataset.id;
        document.getElementById("edit_name").value = this.dataset.name;
        document.getElementById("edit_email").value = this.dataset.email;
        document.getElementById("edit_department").value = this.dataset.department;
        document.getElementById("edit_semester").value = this.dataset.semester;

        const panel = document.getElementById("edit-panel");
        panel.hidden = false;
        panel.scrollIntoView({ behavior: "smooth", block: "start" });
    });
});

document.getElementById("cancel-edit").addEventListener("click", function () {
    document.getElementById("edit-panel").hidden = true;
});
</script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
