
<?php
require_once __DIR__ . "/../session.php";
require_once __DIR__ . "/../db.php";

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store");

function respond(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if (empty($_SESSION["user_id"])) {
    respond(401, ["success" => false, "message" => "Please log in first."]);
}

if (($_SESSION["role"] ?? "") !== "admin") {
    respond(403, ["success" => false, "message" => "Administrator access required."]);
}

if (empty($_SESSION["api_csrf"])) {
    $_SESSION["api_csrf"] = bin2hex(random_bytes(32));
}

$method = $_SERVER["REQUEST_METHOD"];
$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    $input = [];
}

try {
    if ($method === "GET") {
        $result = $conn->query(
            "SELECT event_id, title, description, event_date,
                    event_time, venue, capacity
             FROM events
             ORDER BY event_date DESC, event_time DESC"
        );

        $events = $result->fetch_all(MYSQLI_ASSOC);

        respond(200, [
            "success" => true,
            "csrfToken" => $_SESSION["api_csrf"],
            "events" => $events
        ]);
    }

    if (!in_array($method, ["POST", "PUT", "DELETE"], true)) {
        header("Allow: GET, POST, PUT, DELETE");
        respond(405, ["success" => false, "message" => "Method not allowed."]);
    }

    $token = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "";

    if (!is_string($token) ||
        !hash_equals($_SESSION["api_csrf"], $token)) {
        respond(403, ["success" => false, "message" => "Invalid security token. Refresh the page."]);
    }

    if ($method === "POST" || $method === "PUT") {
        $title = trim($input["title"] ?? "");
        $description = trim($input["description"] ?? "");
        $date = $input["event_date"] ?? "";
        $time = $input["event_time"] ?? "";
        $venue = trim($input["venue"] ?? "");
        $capacity = filter_var($input["capacity"] ?? null, FILTER_VALIDATE_INT);

        $dateObj = is_string($date)
            ? DateTime::createFromFormat("!Y-m-d", $date)
            : false;

        $timeObj = is_string($time)
            ? DateTime::createFromFormat("!H:i", $time)
            : false;

        if (
            !is_string($title) || $title === "" || strlen($title) > 150 ||
            !is_string($description) || $description === "" ||
            !is_string($venue) || $venue === "" || strlen($venue) > 200 ||
            !$dateObj || $dateObj->format("Y-m-d") !== $date ||
            !$timeObj || $timeObj->format("H:i") !== $time ||
            $capacity === false || $capacity < 1 || $capacity > 100000
        ) {
            respond(422, ["success" => false, "message" => "Please provide valid event details."]);
        }

        if ($method === "POST") {
            $stmt = $conn->prepare(
                "INSERT INTO events
                (title, description, event_date, event_time, venue, capacity)
                VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "sssssi",
                $title, $description, $date, $time, $venue, $capacity
            );
            $stmt->execute();
            $newId = $conn->insert_id;
            $stmt->close();

            respond(201, [
                "success" => true,
                "message" => "Event added successfully.",
                "event_id" => $newId
            ]);
        }

        $id = filter_var($input["event_id"] ?? null, FILTER_VALIDATE_INT);

        if ($id === false || $id === null || $id < 1) {
            respond(422, ["success" => false, "message" => "A valid event ID is required."]);
        }

        $stmt = $conn->prepare(
            "UPDATE events
             SET title = ?, description = ?, event_date = ?,
                 event_time = ?, venue = ?, capacity = ?
             WHERE event_id = ?"
        );
        $stmt->bind_param(
            "sssssii",
            $title, $description, $date, $time, $venue, $capacity, $id
        );
        $stmt->execute();
        $changed = $stmt->affected_rows;
        $stmt->close();

        $check = $conn->prepare("SELECT event_id FROM events WHERE event_id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();

        if (!$exists) {
            respond(404, ["success" => false, "message" => "Event not found."]);
        }

        respond(200, [
            "success" => true,
            "message" => $changed > 0
                ? "Event updated successfully."
                : "No changes were made."
        ]);
    }

    if ($method === "DELETE") {
        $id = filter_var($input["event_id"] ?? null, FILTER_VALIDATE_INT);

        if ($id === false || $id === null || $id < 1) {
            respond(422, ["success" => false, "message" => "A valid event ID is required."]);
        }

        $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();

        if ($deleted === 0) {
            respond(404, ["success" => false, "message" => "Event not found."]);
        }

        respond(200, ["success" => true, "message" => "Event deleted successfully."]);
    }

} catch (mysqli_sql_exception $e) {
    error_log("Events API database error: " . $e->getMessage());

    if ((int)$e->getCode() === 1451) {
        respond(409, ["success" => false, "message" => "This event has related records and cannot be deleted."]);
    }

    respond(500, ["success" => false, "message" => "A database operation failed."]);
} catch (Throwable $e) {
    error_log("Events API error: " . $e->getMessage());
    respond(500, ["success" => false, "message" => "An unexpected error occurred."]);
}
?>
