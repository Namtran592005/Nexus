<?php
define("IS_PUBLIC_PAGE", true); // Prevents auth check in bootstrap.php
require_once "bootstrap.php";

$error_message = "";

if (AUTH_ENABLED === false) {
    header("Location: index.php");
    exit();
}

if (isset($_SESSION["is_logged_in"]) && $_SESSION["is_logged_in"] === true) {
    header("Location: index.php");
    exit();
}

$success_message = "";
if (isset($_SESSION["message"])) {
    if ($_SESSION["message"]["type"] === "success") {
        $success_message = $_SESSION["message"]["text"];
    }
    unset($_SESSION["message"]);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";
    $users = AUTH_USERS;

    if (
        isset($users[$username]) &&
        password_verify($password, $users[$username])
    ) {
        $_SESSION["is_logged_in"] = true;
        $_SESSION["username"] = $username;
        header("Location: index.php");
        exit();
    } else {
        $error_message = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Login</title>
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
        max-width: 360px;
        /* Thu nhỏ container */
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

    .logo {
        font-size: 3.5em;
        /* Thu nhỏ logo */
        color: var(--accent-color);
        margin-bottom: 5px;
    }

    h1 {
        margin: 0 0 5px 0;
        font-size: 1.8em;
        /* Thu nhỏ H1 */
        font-weight: 600;
        color: var(--text-primary);
    }

    .subtitle {
        margin-bottom: 25px;
        color: var(--text-secondary);
        font-size: 1em;
        /* Thu nhỏ subtitle */
    }

    .form-group {
        position: relative;
        margin-bottom: 20px;
    }

    .form-group .icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
        pointer-events: none;
        /* Icon không bắt sự kiện click */
    }

    /* Icon hiện/ẩn mật khẩu */
    .form-group .password-toggle {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
        cursor: pointer;
        pointer-events: auto;
        /* Cho phép click */
    }

    .form-group .password-toggle:hover {
        color: var(--text-primary);
    }

    .form-group input {
        width: 100%;
        padding: 14px 15px 14px 45px;
        /* Điều chỉnh padding */
        border: 1px solid var(--border-color);
        border-radius: 10px;
        font-size: 1em;
        box-sizing: border-box;
        background-color: transparent;
        color: var(--text-primary);
        transition: all 0.2s ease;
    }

    .form-group input[type="password"] {
        padding-right: 45px;
        /* Thêm padding bên phải cho icon con mắt */
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

    .message-box.success {
        background-color: rgba(52, 199, 89, 0.2);
        color: #34c759;
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

    .register-link {
        margin-top: 20px;
        font-size: 0.9em;
        color: var(--text-secondary);
    }

    .register-link a {
        color: var(--accent-color);
        text-decoration: none;
        font-weight: 500;
    }

    .register-link a:hover {
        text-decoration: underline;
    }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="logo"><i class="fas fa-cloud-bolt"></i></div>
        <h1><?php echo APP_NAME; ?></h1>
        <p class="subtitle">Welcome back! Please log in.</p>

        <?php if (!empty($error_message)): ?>
        <div class="message-box error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
        <div class="message-box success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <i class="icon fas fa-user"></i>
                <input type="text" id="username" name="username" placeholder="Username" required autofocus>
            </div>
            <div class="form-group">
                <i class="icon fas fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="Password" required>
                <i class="fas fa-eye password-toggle" id="password-toggle"></i>
            </div>
            <button type="submit" class="btn-submit">Login</button>

            <?php if (ALLOW_REGISTRATION === true): ?>
            <p class="register-link">
                Don't have an account? <a href="register.php">Sign up</a>
            </p>
            <?php endif; ?>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('password');
        const toggleButton = document.getElementById('password-toggle');

        if (passwordInput && toggleButton) {
            toggleButton.addEventListener('click', function() {
                // Thay đổi type của input
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Thay đổi icon
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }
    });
    </script>
</body>

</html>