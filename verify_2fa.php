<?php
define("IS_PUBLIC_PAGE", true);
require_once "bootstrap.php";
require_once "src/lib/TwoFactorAuth.php"; // SỬA ĐỔI Ở ĐÂY

use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\TwoFactorAuthException;

// Người dùng phải qua bước 1 (nhập password) trước
if (!isset($_SESSION['tfa_passed_step1']) || $_SESSION['tfa_passed_step1'] !== true) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['tfa_user'];
$users = AUTH_USERS;
$user_data = $users[$username] ?? null;

if (!$user_data || !$user_data['tfa_enabled']) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$error_message = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $code = $_POST["code"] ?? "";
    
    try {
        $tfa = new TwoFactorAuth(APP_NAME); // SỬA ĐỔI Ở ĐÂY
        if ($tfa->verifyCode($user_data['tfa_secret'], $code)) {
            // Xác thực thành công
            unset($_SESSION['tfa_user'], $_SESSION['tfa_passed_step1']);
            $_SESSION["is_logged_in"] = true;
            $_SESSION["username"] = $username;
            header("Location: index.php");
            exit();
        } else {
            $error_message = "Invalid authentication code.";
        }
    } catch (TwoFactorAuthException $e) {
        $error_message = "An error occurred during verification.";
    }
}
?>
<!-- PHẦN HTML GIỮ NGUYÊN NHƯ TRƯỚC -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Two-Factor Authentication</title>
    <link rel="icon" type="image/x-icon" href="./src/image/favicon.ico">
    <link rel="stylesheet" href="./src/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
    :root {
        --bg-gradient-start: #f2f2f7;
        --bg-gradient-end: #d9d9e0;
        --card-bg: rgba(255, 255, 255, 0.7);
        --text-primary: #1c1c1e;
        --text-secondary: #636366;
        --accent-color: #007aff;
        --accent-color-hover: #0056b3;
        --border-color: #d1d1d6;
        --shadow-color: rgba(0, 0, 0, 0.1);
        --card-backdrop-blur: 15px;
    }

    @media (prefers-color-scheme: dark) {
        :root {
            --bg-gradient-start: #161618;
            --bg-gradient-end: #000000;
            --card-bg: rgba(44, 44, 46, 0.6);
            --text-primary: #f2f2f7;
            --text-secondary: #8a8a8e;
            --border-color: #3a3a3c;
            --shadow-color: rgba(0, 0, 0, 0.3);
        }
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
        background: linear-gradient(45deg, var(--bg-gradient-start), var(--bg-gradient-end));
        background-size: 200% 200%;
        animation: gradientBG 15s ease infinite;
    }

    @keyframes gradientBG {
        0% {
            background-position: 0% 50%;
        }

        50% {
            background-position: 100% 50%;
        }

        100% {
            background-position: 0% 50%;
        }
    }

    .login-container {
        background-color: var(--card-bg);
        border-radius: 18px;
        box-shadow: 0 8px 32px 0 var(--shadow-color);
        backdrop-filter: blur(var(--card-backdrop-blur));
        -webkit-backdrop-filter: blur(var(--card-backdrop-blur));
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 35px;
        width: 100%;
        max-width: 380px;
        text-align: center;
        transform: scale(0.95);
        opacity: 0;
        animation: fadeInScale 0.6s cubic-bezier(0.25, 0.8, 0.25, 1) forwards;
    }

    @keyframes fadeInScale {
        to {
            transform: scale(1);
            opacity: 1;
        }
    }

    h1 {
        margin: 0 0 10px 0;
        font-size: 1.8em;
        font-weight: 600;
        color: var(--text-primary);
    }

    .subtitle {
        margin-bottom: 25px;
        color: var(--text-secondary);
        font-size: 1em;
    }

    .form-group {
        position: relative;
        margin-bottom: 20px;
    }

    .form-group input {
        width: 100%;
        padding: 14px 15px;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        font-size: 1.5em;
        letter-spacing: 0.5em;
        text-align: center;
        box-sizing: border-box;
        background-color: transparent;
        color: var(--text-primary);
        transition: all 0.2s ease;
    }

    .form-group input:focus {
        outline: none;
        border-color: var(--accent-color);
        box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.2);
    }

    .btn-submit {
        width: 100%;
        padding: 14px;
        background: var(--accent-color);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 1.1em;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-top: 10px;
    }

    .btn-submit:hover,
    .btn-submit:focus {
        background-color: var(--accent-color-hover);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 122, 255, 0.2);
        outline: none;
    }

    .message-box {
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 15px;
        text-align: center;
        font-size: 0.9em;
        font-weight: 500;
    }

    .message-box.error {
        background-color: rgba(255, 59, 48, 0.2);
        color: #ff3b30;
        animation: shake 0.5s;
    }

    @keyframes shake {

        10%,
        90% {
            transform: translate3d(-1px, 0, 0);
        }

        20%,
        80% {
            transform: translate3d(2px, 0, 0);
        }

        30%,
        50%,
        70% {
            transform: translate3d(-4px, 0, 0);
        }

        40%,
        60% {
            transform: translate3d(4px, 0, 0);
        }
    }

    .logout-link {
        margin-top: 20px;
        font-size: 0.9em;
    }

    .logout-link a {
        color: var(--text-secondary);
        text-decoration: none;
    }
    </style>
</head>

<body>
    <div class="login-container">
        <h1>Two-Factor Authentication</h1>
        <p class="subtitle">Open your authenticator app and enter the 6-digit code.</p>

        <?php if (!empty($error_message)): ?>
        <div class="message-box error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="verify_2fa.php">
            <div class="form-group">
                <input type="text" id="code" name="code" pattern="[0-9]*" maxlength="6" inputmode="numeric" required
                    autofocus autocomplete="one-time-code">
            </div>
            <button type="submit" class="btn-submit">Verify</button>
        </form>
        <p class="logout-link"><a href="logout.php">Cancel and log out</a></p>
    </div>
</body>

</html>