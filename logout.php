<?php
// No need to define IS_PUBLIC_PAGE, as we want the auth check to run
// to ensure only logged-in users can log out.
require_once "bootstrap.php";

if (AUTH_ENABLED === false) {
    header("Location: index.php");
    exit();
}

// The session_start() is already in bootstrap.php
$_SESSION = [];
session_destroy();

redirect_with_message("login.php", "Bạn đã đăng xuất thành công.", "success");
