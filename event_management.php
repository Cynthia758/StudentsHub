
<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";

if (($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Access denied. Administrator privileges required.");
}

$uploadDir = __DIR__ . "/uploads/event_posters/";
$maxSize = 2 * 1024 * 1024;
$allowedMimes = [
    "image/jpeg" => "jpg",
    "image/png"  => "png",
    "image/webp" => "webp"
];

if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
    exit("Unable to create the poster upload directory.");
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function eventMessage(string $message, string $type = "success"): never {
    header(
        "Location: event_management.php?message=" .
        urlencode($message) . "&type=" . urlencode($type)
    );
    exit;
}

function savePoster(array $file, string $uploadDir, int $maxSize, array $allowedMimes): ?string {
    if (!isset($file["error"]) || is_array($file["error"])) {
        throw new RuntimeException("Invalid poster upload.");
    }

    if ($file["error"] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file["error"] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Poster upload failed.");
    }

    if ($file["size"] <= 0 || $file["size"] > $maxSize) {
        throw new RuntimeException("Poster must be smaller than or equal to 2 MB.");
    }

    if (!is_uploaded_file($file["tmp_name"])) {
        throw new RuntimeException("Invalid uploaded file.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file["tmp_name"]);

    if (!isset($allowedMimes[$mime])) {
        throw new RuntimeException("Only JPG, PNG, and WEBP posters are allowed.");
    }

    $imageInfo = @getimagesize($file["tmp_name"]);

    if ($imageInfo === false || $imageInfo["mime"] !== $mime) {
        throw new RuntimeException("The uploaded file is not a valid image.");
    }

    $filename = bin2hex(random_bytes(16)) . "." . $allowedMimes[$mime];

    if (!move_uploaded_file($file["tmp_name"], $uploadDir . $filename)) {
        throw new RuntimeException("Could not save the poster.");
    }

    return $filename;
}

function removePoster(?string $filename, string $uploadDir): void {
    if (
        $filename !== null &&
        $filename !== "" &&
        basename($filename) === $filename
    ) {
        $path = $uploadDir . $filename;

        if (is_file($path)) {
            unlink($path);
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")) {
        http_response_code(403);
        exit("Invalid request token. Refresh the page and try again.");
    }

    $action = $_POST["action"] ?? "";
    $eventId = filter_var($_POST["event_id"] ?? null, FILTER_VALIDATE_INT);

    try {
        if ($action === "add" || $action === "update") {
            $title = trim($_POST["title"] ?? "");
            $description = trim($_POST["description"] ?? "");
            $date = trim($_POST["event_date"] ?? "");
            $time = trim($_POST["event_time"] ?? "");
            $venue = trim($_POST["venue"] ?? "");
            $capacity = filter_var($_POST["capacity"] ?? null, FILTER_VALIDATE_INT);

            $dateObj = DateTime::createFromFormat("!Y-m-d", $date);
            $timeObj = DateTime::createFromFormat("!H:i", $time);

            if (
                $title === "" || strlen($title) > 150 ||
                $description === "" ||
                $venue === "" || strlen($venue) > 200 ||
                !$dateObj || $dateObj->format("Y-m-d") !== $date ||
                !$timeObj || $timeObj->format("H:i") !== $time ||
                $capacity === false || $capacity < 1 || $capacity > 100000
            ) {
                eventMessage("Please enter valid event details.", "error");
            }

            $newPoster = savePoster(
                $_FILES["poster"] ?? [],
                $uploadDir,
                $maxSize,
                $allowedMimes
            );

            if ($action === "add") {
                $stmt = $conn->prepare(
                    "INSERT INTO events
                    (title, description, event_date, event_time, venue, capacity, poster)
                    VALUES (?, ?, ?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "sssssis",
                    $title, $description, $date, $time, $venue, $capacity, $newPoster
                );

                try {
                    $stmt->execute();
                } catch (Throwable $e) {
                    if ($newPoster !== null) {
                        removePoster($newPoster, $uploadDir);
                    }
                    throw $e;
                }

                $stmt->close();
                eventMessage("Event added successfully.");
            }

            if ($eventId === false || $eventId < 1) {
                if ($newPoster !== null) {
                    removePoster($newPoster, $uploadDir);
                }
                eventMessage("Invalid event ID.", "error");
            }

            $stmt = $conn->prepare("SELECT poster FROM events WHERE event_id = ?");
            $stmt->bind_param("i", $eventId);
            $stmt->execute();
            $oldEvent = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$oldEvent) {
                if ($newPoster !== null) {
                    removePoster($newPoster, $uploadDir);
                }
                eventMessage("Event not found.", "error");
            }

            $poster = $newPoster ?? $oldEvent["poster"];

            $stmt = $conn->prepare(
                "UPDATE events SET
                title = ?, description = ?, event_date = ?, event_time = ?,
                venue = ?, capacity = ?, poster = ?
                WHERE event_id = ?"
            );

            $stmt->bind_param(
                "sssssis i",
                $title, $description, $date, $time, $venue, $capacity, $poster, $eventId
            );

            try {
                $stmt->execute();
            } catch (Throwable $e) {
                if ($newPoster !== null) {
                    removePoster($newPoster, $uploadDir);
                }
                throw $e;
            }

            $stmt->close();

            if ($newPoster !== null) {
                removePoster($oldEvent["poster"], $uploadDir);
            }

            eventMessage("Event updated successfully.");
        }

        if ($action === "delete") {
            if ($eventId === false || $eventId < 1) {
                eventMessage("Invalid event ID.", "error");
            }

            $stmt = $conn->prepare("SELECT poster FROM events WHERE event_id = ?");
            $stmt->bind_param("i", $eventId);
            $stmt->execute();
            $event = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$event) {
                eventMessage("Event not found.", "error");
            }

            $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
            $stmt->bind_param("i", $eventId);
            $stmt->execute();
            $deleted = $stmt->affected_rows;
            $stmt->close();

            if ($deleted > 0) {
                removePoster($event["poster"], $uploadDir);
                eventMessage("Event deleted successfully.");
            }

            eventMessage("Event was not deleted.", "error");
        }

        eventMessage("Unknown action.", "error");

    } catch (RuntimeException $e) {
        eventMessage($e->getMessage(), "error");
    } catch (mysqli_sql_exception $e) {
        error_log("Event management database error: " . $e->getMessage());
        eventMessage(
            (int)$e->getCode() === 1451
                ? "Cannot delete this event because related records prevent deletion."
                : "Database operation failed. Check the event details and try again.",
            "error"
        );
    } catch (Throwable $e) {
        error_log("Event management error: " . $e->getMessage());
        eventMessage("The operation failed. Please try again.", "error");
    }
}

$message = $_GET["message"] ?? "";
$messageType = ($_GET["type"] ?? "") === "error" ? "error" : "success";

$events = $conn->query("SELECT * FROM events ORDER BY event_date DESC, event_time DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Event Management | StudentHub</title>
<style>
* { box-sizing: border-box; }
body { margin: 0; padding: 25px; font-family: Arial, sans-serif; background: #f5f3ef; color: #292720; }
main { max-width: 1150px; margin: auto; }
section { background: white; border: 1px solid #e8e3da; border-radius: 12px; padding: 22px; margin-bottom: 22px; }
header { display: flex; justify-content: space-between; gap: 15px; align-items: center; flex-wrap: wrap; }
h1 { font-size: 26px; } h2 { font-size: 20px; }
.grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
label { display: block; font-size: 14px; margin: 10px 0 6px; }
input, textarea { width: 100%; padding: 10px; border: 1px solid #d8d3ca; border-radius: 7px; font: inherit; }
textarea { min-height: 85px; resize: vertical; }
button, .link { border: 0; border-radius: 7px; padding: 10px 14px; background: #403d32; color: white; cursor: pointer; text-decoration: none; display: inline-block; font-size: 13px; }
.secondary { background: #e9e5dc; color: #292720; } .danger { background: #a83c35; }
.notice { padding: 12px; border-radius: 7px; margin: 15px 0; }
.success { background: #e4f4e8; color: #205b32; } .error { background: #fde8e6; color: #8b2620; }
.table-wrap { overflow-x: auto; } table { border-collapse: collapse; width: 100%; min-width: 900px; }
th, td { text-align: left; border-bottom: 1px solid #eeeae3; padding: 12px 9px; font-size: 13px; vertical-align: top; }
th { background: #f8f6f2; } td img { width: 100px; height: auto; border-radius: 5px; }
.actions { display: flex; gap: 6px; flex-wrap: wrap; }
.edit-form { min-width: 250px; }
@media (max-width: 650px) { body { padding: 12px; } .grid { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<main>
<header>
    <div>
        <h1>Event Management</h1>
        <p>Manage StudentHub events and event posters.</p>
    </div>
    <nav class="actions">
        <a class="link secondary" href="admin.php">Admin Panel</a>
        <a class="link secondary" href="student_management.php">Student Management</a>
        <a class="link danger" href="logout.php">Logout</a>
    </nav>
</header>

<?php if ($message !== ""): ?>
    <div class="notice <?= $messageType ?>">
        <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
    </div>
<?php endif; ?>

<section>
<h2>Add Event</h2>
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>">
    <input type="hidden" name="action" value="add">
    <div class="grid">
        <div><label>Event Title</label><input name="title" maxlength="150" required></div>
        <div><label>Venue</label><input name="venue" maxlength="200" required></div>
        <div><label>Date</label><input type="date" name="event_date" required></div>
        <div><label>Time</label><input type="time" name="event_time" required></div>
        <div><label>Capacity</label><input type="number" name="capacity" min="1" max="100000" required></div>
        <div><label>Poster (JPG, PNG, WEBP; max 2 MB)</label><input type="file" name="poster" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></div>
    </div>
    <label>Description</label><textarea name="description" required></textarea>
    <p><button type="submit">Add Event</button></p>
</form>
</section>

<section>
<h2>Existing Events</h2>
<div class="table-wrap">
<table>
<thead>
<tr><th>Poster</th><th>Event Details</th><th>Edit Event</th><th>Delete</th></tr>
</thead>
<tbody>
<?php if ($events->num_rows === 0): ?>
<tr><td colspan="4">No events found.</td></tr>
<?php else: ?>
<?php while ($event = $events->fetch_assoc()): ?>
<tr>
<td>
<?php if (!empty($event["poster"])): ?>
<img src="<?= "uploads/event_posters/" . rawurlencode(basename($event["poster"])) ?>" alt="Event poster">
<?php else: ?>No poster<?php endif; ?>
</td>
<td>
<strong><?= htmlspecialchars($event["title"], ENT_QUOTES, "UTF-8") ?></strong><br>
<?= nl2br(htmlspecialchars($event["description"], ENT_QUOTES, "UTF-8")) ?><br>
Date: <?= htmlspecialchars($event["event_date"], ENT_QUOTES, "UTF-8") ?><br>
Time: <?= htmlspecialchars(substr((string)$event["event_time"], 0, 5), ENT_QUOTES, "UTF-8") ?><br>
Venue: <?= htmlspecialchars($event["venue"], ENT_QUOTES, "UTF-8") ?><br>
Capacity: <?= (int)$event["capacity"] ?>
</td>
<td>
<form class="edit-form" method="POST" enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>">
<input type="hidden" name="action" value="update">
<input type="hidden" name="event_id" value="<?= (int)$event["event_id"] ?>">
<label>Title</label><input name="title" maxlength="150" required value="<?= htmlspecialchars($event["title"], ENT_QUOTES, "UTF-8") ?>">
<label>Description</label><textarea name="description" required><?= htmlspecialchars($event["description"], ENT_QUOTES, "UTF-8") ?></textarea>
<label>Date</label><input type="date" name="event_date" required value="<?= htmlspecialchars($event["event_date"], ENT_QUOTES, "UTF-8") ?>">
<label>Time</label><input type="time" name="event_time" required value="<?= htmlspecialchars(substr((string)$event["event_time"], 0, 5), ENT_QUOTES, "UTF-8") ?>">
<label>Venue</label><input name="venue" maxlength="200" required value="<?= htmlspecialchars($event["venue"], ENT_QUOTES, "UTF-8") ?>">
<label>Capacity</label><input type="number" name="capacity" min="1" max="100000" required value="<?= (int)$event["capacity"] ?>">
<label>Replace poster (optional)</label><input type="file" name="poster" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
<button type="submit">Save Changes</button>
</form>
</td>
<td>
<form method="POST" onsubmit="return confirm('Are you sure you want to delete this event?');">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="event_id" value="<?= (int)$event["event_id"] ?>">
<button class="danger" type="submit">Delete</button>
</form>
</td>
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
<?php $conn->close(); ?>
