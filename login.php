<?php
define("IS_PUBLIC_PAGE", true);
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

// --- LOGIC KIỂM TRA BRUTE-FORCE ---
define('LOGIN_ATTEMPT_LIMIT', 5);
define('LOGIN_BLOCK_TIME_MINUTES', 15);

$ip_address = $_SERVER['REMOTE_ADDR'];
// === MODIFIED: Always check login attempts against the admin DB ===
$admin_pdo = get_db_connection('admin');
$stmt = $admin_pdo->prepare("SELECT failed_attempts, last_attempt_at FROM login_attempts WHERE ip_address = ?");
$stmt->execute([$ip_address]);
$attempt_info = $stmt->fetch();

if ($attempt_info && $attempt_info['failed_attempts'] >= LOGIN_ATTEMPT_LIMIT) {
    $time_since_last_attempt = time() - $attempt_info['last_attempt_at'];
    if ($time_since_last_attempt < (LOGIN_BLOCK_TIME_MINUTES * 60)) {
        $remaining_time = ceil(((LOGIN_BLOCK_TIME_MINUTES * 60) - $time_since_last_attempt) / 60);
        $error_message = "Too many failed login attempts. Please try again in {$remaining_time} minutes.";
        goto display_page;
    } else {
        $stmt = $admin_pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
        $stmt->execute([$ip_address]);
    }
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
    $user_data = $users[$username] ?? null;

    // === NEW: Check if account is locked ===
    if (isset($user_data['is_locked']) && $user_data['is_locked'] === true) {
        $error_message = "This account has been locked by an administrator.";
    } elseif (isset($user_data) && is_array($user_data) && password_verify($password, $user_data['password'])) {
        // Mật khẩu đúng, xóa các lần thử thất bại
        $stmt = $admin_pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
        $stmt->execute([$ip_address]);
        
        // Kiểm tra xem người dùng có bật 2FA không
        if (isset($user_data['tfa_enabled']) && $user_data['tfa_enabled'] === true) {
            // Lưu trạng thái bước 1 và chuyển hướng đến trang xác thực 2FA
            $_SESSION['tfa_user'] = $username;
            $_SESSION['tfa_passed_step1'] = true;
            header("Location: verify_2fa.php");
            exit();
        } else {
            // Đăng nhập thành công (không có 2FA)
            $_SESSION["is_logged_in"] = true;
            $_SESSION["username"] = $username;
            header("Location: index.php");
            exit();
        }

    } else {
        // Mật khẩu sai, ghi lại lần thử thất bại
        $stmt = $admin_pdo->prepare(
            "INSERT INTO login_attempts (ip_address, last_attempt_at, failed_attempts) VALUES (?, ?, 1)
             ON CONFLICT(ip_address) DO UPDATE SET
             failed_attempts = failed_attempts + 1,
             last_attempt_at = excluded.last_attempt_at"
        );
        $stmt->execute([$ip_address, time()]);
        $error_message = "Invalid username or password.";
    }
}

display_page:
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
        color: var(--accent-color);
        margin-bottom: 5px;
    }

    h1 {
        margin: 0 0 5px 0;
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

    .form-group .icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
        pointer-events: none;
    }

    .form-group .password-toggle {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
        cursor: pointer;
        pointer-events: auto;
    }

    .form-group .password-toggle:hover {
        color: var(--text-primary);
    }

    .form-group input {
        width: 100%;
        padding: 14px 15px 14px 45px;
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
        <div class="message-box error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
        <div class="message-box success"><?php echo htmlspecialchars($success_message); ?></div>
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
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }
    });
    </script>
</body>

</html>