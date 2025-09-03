<?php
require_once 'config.php';
$error_message = '';

if (AUTH_ENABLED === false) {
    header('Location: index.php');
    exit;
}

if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$success_message = '';
if (isset($_SESSION['message'])) {
    if ($_SESSION['message']['type'] === 'success') {
        $success_message = $_SESSION['message']['text'];
    }
    unset($_SESSION['message']);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $users = AUTH_USERS;

    if (isset($users[$username]) && password_verify($password, $users[$username])) {
        $_SESSION['is_logged_in'] = true;
        $_SESSION['username'] = $username;
        header('Location: index.php');
        exit;
    } else {
        $error_message = 'Tên đăng nhập hoặc mật khẩu không chính xác.';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Đăng nhập</title>
    <link rel="icon" type="image/x-icon" href="./src/image/favicon.ico"> <!-- Bạn có thể thay favicon mới ở đây -->
    <link rel="stylesheet" href="./src/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* CSS VẪN GIỮ NGUYÊN NHƯ PHIÊN BẢN TRƯỚC VÌ NÓ ĐÃ ĐẸP VÀ TRUNG LẬP */
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
        overflow: hidden;
        background: linear-gradient(45deg, var(--bg-gradient-start), var(--bg-gradient-end));
        background-size: 400% 400%;
        animation: gradientBG 15s ease infinite;
        transition: background 0.5s ease;
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
        border-radius: 20px;
        box-shadow: 0 8px 32px 0 var(--shadow-color);
        backdrop-filter: blur(var(--card-backdrop-blur));
        -webkit-backdrop-filter: blur(var(--card-backdrop-blur));
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 40px;
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

        .logo {
        font-size: 4.5em;
        color: var(--accent-color);
        margin-bottom: 10px;
        animation: logoFloat 3s ease-in-out infinite;
        }

        @keyframes logoFloat {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-10px);
        }
        }

        h1 {
        margin: 0 0 5px 0;
        font-size: 2.2em;
        font-weight: 600;
        color: var(--text-primary);
        }

        .subtitle {
        margin-bottom: 30px;
        color: var(--text-secondary);
        font-size: 1.1em;
        }

        .form-group {
        position: relative;
        margin-bottom: 25px;
        }

        .form-group .icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-secondary);
        }

        .form-group input {
        width: 100%;
        padding: 15px 15px 15px 45px;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        font-size: 1.1em;
        box-sizing: border-box;
        background-color: transparent;
        color: var(--text-primary);
        transition: all 0.3s ease;
        }

        .form-group input:focus {
        outline: none;
        border-color: var(--accent-color);
        box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.2);
        }

        .form-group input::placeholder {
        color: var(--text-secondary);
        opacity: 0.8;
        }

        .btn-submit {
        width: 100%;
        padding: 15px;
        background-color: var(--accent-color);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 1.2em;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 10px;
        }

        .btn-submit:hover {
        background-color: var(--accent-color-hover);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 122, 255, 0.3);
        }

        .message-box {
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: center;
        font-weight: 500;
        animation: shake 0.5s;
        }

        .message-box.error {
        background-color: rgba(255, 59, 48, 0.2);
        color: #ff3b30;
        }

        .message-box.success {
        background-color: rgba(52, 199, 89, 0.2);
        color: #34c759;
        animation: none;
        }

        @keyframes shake {

        0%,
        100% {
            transform: translateX(0);
        }

        25% {
            transform: translateX(-5px);
        }

        75% {
            transform: translateX(5px);
        }
        }

        .register-link {
        margin-top: 25px;
        color: var(--text-secondary);
        }

        .register-link a {
        color: var(--accent-color);
        text-decoration: none;
        font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- THAY ĐỔI ICON Ở ĐÂY -->
        <div class="logo"><i class="fas fa-cloud-bolt"></i></div>
        <!-- THAY ĐỔI TIÊU ĐỀ Ở ĐÂY -->
        <h1><?php echo APP_NAME; ?></h1>
        <p class="subtitle">Đăng nhập để tiếp tục</p>

        <?php if (!empty($error_message)): ?>
            <div class="message-box error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="message-box success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <i class="icon fas fa-user"></i>
                <input type="text" id="username" name="username" placeholder="Tên đăng nhập" required autofocus>
            </div>
            <div class="form-group">
                <i class="icon fas fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="Mật khẩu" required>
            </div>
            <button type="submit" class="btn-submit">
                <i class="fas fa-arrow-right"></i>
            </button>
            <!-- START: THÊM LIÊN KẾT ĐĂNG KÝ -->
            <?php if (ALLOW_REGISTRATION === true): ?>
            <p class="register-link">
                Chưa có tài khoản? <a href="register.php">Tạo tài khoản</a>
            </p>
            <?php endif; ?>
            <!-- END: THÊM LIÊN KẾT ĐĂNG KÝ -->
        </form>
    </div>
</body>
</html>