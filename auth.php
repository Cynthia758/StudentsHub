
<?php

require_once __DIR__ . "/session.php";

if (empty($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

?>
