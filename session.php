
<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = !empty($_SERVER["HTTPS"]) &&
        $_SERVER["HTTPS"] !== "off";

    ini_set("session.use_strict_mode", "1");
    ini_set("session.use_only_cookies", "1");

    session_set_cookie_params([
        "lifetime" => 0,
        "path" => "/StudentHub/",
        "secure" => $isHttps,
        "httponly" => true,
        "samesite" => "Lax"
    ]);

    session_start();
}

$timeout = 1800;

if (isset($_SESSION["last_activity"]) &&
    time() - $_SESSION["last_activity"] > $timeout) {

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();

        setcookie(session_name(), "", [
            "expires" => time() - 42000,
            "path" => $params["path"],
            "secure" => $params["secure"],
            "httponly" => $params["httponly"],
            "samesite" => $params["samesite"]
        ]);
    }

    session_destroy();

    header("Location: login.html?timeout=1");
    exit;
}

if (isset($_SESSION["user_id"])) {
    $_SESSION["last_activity"] = time();
}
?>
