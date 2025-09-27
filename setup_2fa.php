<?php
// Trang này yêu cầu người dùng phải đăng nhập
require_once "bootstrap.php";
require_once "src/lib/TwoFactorAuth.php";

// Redirect nếu người dùng chưa đăng nhập
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

use RobThree\Auth\TwoFactorAuth;


$username = $_SESSION['username'];
$users = AUTH_USERS;

// --- SỬA LỖI QUAN TRỌNG: Kiểm tra và khởi tạo dữ liệu người dùng ---
if (!isset($users[$username]) || !is_array($users[$username])) {
    // Nếu có lỗi lạ với dữ liệu người dùng, đăng xuất cho an toàn
    header('Location: logout.php');
    exit;
}
$user_data = $users[$username];
// Gán giá trị mặc định nếu các key chưa tồn tại
$user_data['tfa_enabled'] = $user_data['tfa_enabled'] ?? false;
$user_data['tfa_secret'] = $user_data['tfa_secret'] ?? null;
// --- KẾT THÚC SỬA LỖI ---

$tfa = new TwoFactorAuth(APP_NAME);


$setup_error = '';
$setup_success = '';
$secret = $user_data['tfa_secret'];

// Xử lý khi người dùng VÔ HIỆU HÓA 2FA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'disable') {
    $users[$username]['tfa_enabled'] = false;
    $users[$username]['tfa_secret'] = null;
    $content = "<?php\n\nreturn " . var_export($users, true) . ";\n";
    if (file_put_contents(USERS_FILE, $content)) {
        $setup_success = "Two-Factor Authentication has been disabled.";
        $user_data['tfa_enabled'] = false; // Cập nhật trạng thái hiện tại
        $secret = null;
    } else {
        $setup_error = "Could not update user settings.";
    }
}

// Xử lý khi người dùng KÍCH HOẠT 2FA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enable') {
    $code = $_POST['code'] ?? '';
    $temp_secret = $_SESSION['tfa_temp_secret'] ?? null;

    if (empty($temp_secret)) {
        $setup_error = "Session expired. Please refresh the page and try again.";
    } elseif ($tfa->verifyCode($temp_secret, $code)) {
        $users[$username]['tfa_enabled'] = true;
        $users[$username]['tfa_secret'] = $temp_secret;
        $content = "<?php\n\nreturn " . var_export($users, true) . ";\n";
        
        if (file_put_contents(USERS_FILE, $content)) {
            unset($_SESSION['tfa_temp_secret']);
            $setup_success = "Two-Factor Authentication has been enabled successfully!";
            $user_data['tfa_enabled'] = true; // Cập nhật trạng thái hiện tại
            $secret = $temp_secret;
        } else {
            $setup_error = "Could not save settings. Please try again.";
        }
    } else {
        $setup_error = "The verification code was incorrect. Please try again.";
        $secret = $_SESSION['tfa_temp_secret']; // Giữ lại secret cũ để người dùng thử lại
    }
}

// Nếu người dùng chưa kích hoạt và chưa có secret tạm, tạo mới
if (!$user_data['tfa_enabled'] && empty($secret)) {
    $secret = $tfa->createSecret();
    $_SESSION['tfa_temp_secret'] = $secret;
}

// Nếu người dùng chưa kích hoạt và chưa có secret tạm, tạo mới
if (!$user_data['tfa_enabled'] && empty($secret)) {
    $secret = $tfa->createSecret();
    $_SESSION['tfa_temp_secret'] = $secret;
}

// === BẮT ĐẦU ĐOẠN MÃ MỚI ===
// Tạo URL mã QR bằng dịch vụ qrserver.com
$qrData = urlencode($tfa->getQRText(APP_NAME . ':' . $username, $secret));
$qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . $qrData;
// === KẾT THÚC ĐOẠN MÃ MỚI ===

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Two-Factor Authentication</title>
    <!-- CSS GIỮ NGUYÊN NHƯ TRƯỚC -->
    <style>
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        background-color: #f0f2f5;
        color: #1c1c1e;
        display: flex;
        justify-content: center;
        padding-top: 50px;
        margin: 0 20px;
    }

    .container {
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        max-width: 500px;
        width: 100%;
    }

    h1 {
        margin-top: 0;
    }

    p {
        line-height: 1.6;
    }

    .qr-code {
        margin: 20px auto;
        display: block;
        border: 1px solid #eee;
        border-radius: 8px;
        max-width: 100%;
        height: auto;
    }

    .secret-key {
        background-color: #eee;
        padding: 10px;
        border-radius: 8px;
        font-family: monospace;
        word-break: break-all;
        margin: 20px 0;
        text-align: center;
    }

    .message {
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: center;
        font-weight: 500;
    }

    .error {
        background-color: rgba(255, 59, 48, 0.2);
        color: #ff3b30;
    }

    .success {
        background-color: rgba(52, 199, 89, 0.2);
        color: #34c759;
    }

    input[type="text"] {
        width: 100%;
        padding: 12px;
        font-size: 1.2em;
        text-align: center;
        letter-spacing: 0.2em;
        border: 1px solid #ccc;
        border-radius: 8px;
        box-sizing: border-box;
        margin-bottom: 15px;
    }

    button {
        width: 100%;
        padding: 12px;
        font-size: 1em;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .btn-primary {
        background-color: #007aff;
        color: white;
    }

    .btn-primary:hover {
        background-color: #005ecb;
    }

    .btn-danger {
        background-color: #ff3b30;
        color: white;
        margin-top: 10px;
    }

    .btn-danger:hover {
        background-color: #c53026;
    }

    a {
        color: #007aff;
        text-decoration: none;
    }
    </style>
</head>

<body>
    <div class="container">
        <h1>Two-Factor Authentication (2FA)</h1>
        <p><a href="index.php">&larr; Back to Drive</a></p>

        <?php if ($setup_error): ?>
        <p class="message error"><?php echo htmlspecialchars($setup_error); ?></p>
        <?php endif; ?>
        <?php if ($setup_success): ?>
        <p class="message success"><?php echo htmlspecialchars($setup_success); ?></p>
        <?php endif; ?>

        <?php if ($user_data['tfa_enabled']): ?>
        <p>2FA is currently <strong>enabled</strong> on your account.</p>
        <form method="POST">
            <input type="hidden" name="action" value="disable">
            <button type="submit" class="btn-danger">Disable 2FA</button>
        </form>
        <?php else: ?>
        <p>To enable 2FA, scan the QR code below with your authenticator app (like Google Authenticator, Authy, or
            1Password).</p>

        <img class="qr-code" src="<?php echo $qrCodeUrl; ?>">

        <p>If you cannot scan the code, you can manually enter this secret key:</p>
        <div class="secret-key"><?php echo htmlspecialchars($secret); ?></div>

        <hr>

        <form method="POST">
            <input type="hidden" name="action" value="enable">
            <label for="code">Enter the 6-digit code from your app to confirm:</label>
            <input type="text" id="code" name="code" pattern="[0-9]*" maxlength="6" inputmode="numeric" required
                autocomplete="off">
            <button type="submit" class="btn-primary">Enable 2FA</button>
        </form>
        <?php endif; ?>
    </div>
</body>

</html>