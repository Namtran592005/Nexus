<?php
require_once 'config.php';

if (AUTH_ENABLED === false) {
    redirect_with_message('index.php', 'Xác thực đang tắt, không thể đăng ký.', 'error');
}

// Kiểm tra xem tính năng đăng ký có được bật không
if (ALLOW_REGISTRATION !== true) {
    redirect_with_message('login.php', 'Tính năng đăng ký hiện đang bị tắt.', 'error');
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Lấy danh sách người dùng hiện tại
    $users = AUTH_USERS;

    // --- Bắt lỗi ---
    if (empty($username) || empty($password)) {
        $error_message = 'Tên đăng nhập và mật khẩu không được để trống.';
    } elseif (strlen($username) < 3) {
        $error_message = 'Tên đăng nhập phải có ít nhất 3 ký tự.';
    } elseif (preg_match('/[^A-Za-z0-9_]/', $username)) {
        $error_message = 'Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới (_).';
    } elseif (strlen($password) < 6) {
        $error_message = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } elseif ($password !== $password_confirm) {
        $error_message = 'Mật khẩu xác nhận không khớp.';
    } elseif (isset($users[$username])) {
        $error_message = 'Tên đăng nhập này đã tồn tại.';
    } else {
        // --- Xử lý đăng ký thành công ---
        // Thêm người dùng mới vào mảng
        $users[$username] = password_hash($password, PASSWORD_DEFAULT);
        
        // Tạo nội dung mới cho file users.php
        $content = "<?php\n\nreturn " . var_export($users, true) . ";\n";
        
        // Ghi đè file users.php
        if (file_put_contents(USERS_FILE, $content)) {
            redirect_with_message('login.php', 'Đăng ký thành công! Bây giờ bạn có thể đăng nhập.', 'success');
        } else {
            $error_message = 'Đã có lỗi xảy ra. Không thể lưu tài khoản mới.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Đăng ký</title>
    <link rel="icon" type="image/x-icon" href="./src/image/favicon.ico">
    <link rel="stylesheet" href="./src/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Dùng chung CSS với trang login -->
    <style>
        : root {
        --bg - gradient - start: #f2f2f7;
        --bg - gradient - end: #d9d9e0;
        --card - bg: rgba(255, 255, 255, 0.7);
        --text - primary: #1c1c1e; --text-secondary: # 636366;
        --accent - color: #007aff; --accent-color-hover: # 0056 b3;
        --border - color: #d1d1d6;
        --shadow - color: rgba(0, 0, 0, 0.1);
        --card - backdrop - blur: 15 px;
        }
        @media(prefers - color - scheme: dark) {
        : root {
            --bg - gradient - start: #161618; --bg-gradient-end: # 000000;
            --card - bg: rgba(44, 44, 46, 0.6);
            --text - primary: #f2f2f7;
            --text - secondary: #8a8a8e; --border-color: # 3 a3a3c;
            --shadow - color: rgba(0, 0, 0, 0.3);
        }
        }
        body {
        font - family: -apple - system, BlinkMacSystemFont, "Segoe UI", Roboto, sans - serif;
        display: flex;
        justify - content: center;
        align - items: center;
        min - height: 100 vh;
        margin: 0;
        overflow: hidden;
        background: linear - gradient(45 deg,
            var (--bg - gradient - start),
            var (--bg - gradient - end));
        background - size: 400 % 400 % ;
        animation: gradientBG 15 s ease infinite;
        }
        @keyframes gradientBG {
        0 % {
            background - position: 0 % 50 % ;
        }
        50 % {
            background - position: 100 % 50 % ;
        }
        100 % {
            background - position: 0 % 50 % ;
        }
        }
        .login - container {
        background - color: var (--card - bg);
        border - radius: 20 px;
        box - shadow: 0 8 px 32 px 0
        var (--shadow - color);
        backdrop - filter: blur(var (--card - backdrop - blur)); - webkit - backdrop - filter: blur(var (--card - backdrop - blur));
        border: 1 px solid rgba(255, 255, 255, 0.1);
        padding: 40 px;
        width: 100 % ;
        max - width: 400 px;
        text - align: center;
        transform: scale(0.95);
        opacity: 0;
        animation: fadeInScale 0.6 s cubic - bezier(0.25, 0.8, 0.25, 1) forwards;
        }
        @keyframes fadeInScale {
        to {
            transform: scale(1);opacity: 1;
        }
        }
        h1 {
        margin: 0 0 25 px 0;font - size: 2.2 em;font - weight: 600;color: var (--text - primary);
        }
        .form - group {
            position: relative;margin - bottom: 20 px;
        }
        .form - group.icon {
            position: absolute;left: 15 px;top: 50 % ;transform: translateY(-50 % );color: var (--text - secondary);
        }
        .form - group input {
            width: 100 % ;padding: 15 px 15 px 15 px 45 px;border: 1 px solid
            var (--border - color);border - radius: 12 px;font - size: 1.1 em;box - sizing: border - box;background - color: transparent;color: var (--text - primary);transition: all 0.3 s ease;
        }
        .form - group input: focus {
            outline: none;border - color: var (--accent - color);box - shadow: 0 0 0 4 px rgba(0, 122, 255, 0.2);
        }
        .btn - submit {
            width: 100 % ;padding: 15 px;background - color: var (--accent - color);color: white;border: none;border - radius: 12 px;font - size: 1.2 em;font - weight: 500;cursor: pointer;transition: all 0.3 s ease;margin - top: 10 px;
        }
        .btn - submit: hover {
            background - color: var (--accent - color - hover);
            transform: translateY(-2 px);
            box - shadow: 0 4 px 15 px rgba(0, 122, 255, 0.3);
        }
        .message - box {
            padding: 12 px;border - radius: 8 px;margin - bottom: 20 px;text - align: center;font - weight: 500;
        }
        .message - box.error {
            background - color: rgba(255, 59, 48, 0.2);
            color: #ff3b30;
            animation: shake 0.5 s;
        }
        @keyframes shake {
        0 % , 100 % {
            transform: translateX(0);
        }
        25 % {
            transform: translateX(-5 px);
        }
        75 % {
            transform: translateX(5 px);
        }
        }
        .login - link {
            margin - top: 25 px;
            color: var (--text - secondary);
        }
        .login - link a {
            color: var (--accent - color);text - decoration: none;font - weight: 500;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Tạo tài khoản</h1>

        <?php if (!empty($error_message)): ?>
            <div class="message-box error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="form-group">
                <i class="icon fas fa-user"></i>
                <input type="text" name="username" placeholder="Tên đăng nhập" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <i class="icon fas fa-lock"></i>
                <input type="password" name="password" placeholder="Mật khẩu" required>
            </div>
            <div class="form-group">
                <i class="icon fas fa-check-circle"></i>
                <input type="password" name="password_confirm" placeholder="Xác nhận mật khẩu" required>
            </div>
            <button type="submit" class="btn-submit">Đăng ký</button>
        </form>

        <p class="login-link">
            Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a>
        </p>
    </div>
</body>
</html>