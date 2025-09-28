<?php
define("IS_PUBLIC_PAGE", true);
require_once "bootstrap.php";

if (AUTH_ENABLED === false) {
    redirect_with_message("index.php", "Authentication is disabled, registration is not available.", "error");
}
if (ALLOW_REGISTRATION !== true) {
    redirect_with_message("login.php", "Registration is currently disabled.", "error");
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $password_confirm = $_POST["password_confirm"] ?? "";

    $users = AUTH_USERS;

    if (empty($username) || empty($password)) {
        $error_message = "Username and password cannot be empty.";
    } elseif (strtolower($username) === 'admin') {
        $error_message = "The username 'admin' is reserved.";
    } elseif (strlen($username) < 3) {
        $error_message = "Username must be at least 3 characters long.";
    } elseif (preg_match("/[^A-Za-z0-9_]/", $username)) {
        $error_message = "Username can only contain letters, numbers, and underscores (_).";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } elseif ($password !== $password_confirm) {
        $error_message = "Password confirmation does not match.";
    } elseif (isset($users[$username])) {
        $error_message = "This username already exists.";
    } else {
        // TẠO USER VỚI CẤU TRÚC MỚI ĐẦY ĐỦ
        $users[$username] = [
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'tfa_secret' => null,
            'tfa_enabled' => false,
            'is_locked' => false, // === NEW: Add lock status for new users
        ];

        $content = "<?php\n\nreturn " . var_export($users, true) . ";\n";

        // === MODIFIED: Use file locking for safer writes ===
        $file_handle = fopen(USERS_FILE, 'w');
        if (flock($file_handle, LOCK_EX)) {
            fwrite($file_handle, $content);
            flock($file_handle, LOCK_UN);
            fclose($file_handle);

            // === NEW: Create the new user's database ===
            try {
                $newUserDb = get_db_connection($username);
                // The connection function already handles initialization
                redirect_with_message("login.php", "Registration successful! You can now log in.", "success");
            } catch (Exception $e) {
                 $error_message = "User created, but failed to initialize user database: " . $e->getMessage();
                 // Optionally, you might want to roll back the user creation here
            }

        } else {
            if ($file_handle) fclose($file_handle);
            $error_message = "An error occurred. Could not get a lock on the user file.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Register</title>
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
        max-width: 400px;
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
        margin: 0 0 25px 0;
        font-size: 2em;
        font-weight: 600;
        color: var(--text-primary);
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

    .btn-submit:hover {
        background-color: var(--accent-color-hover);
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 122, 255, 0.2);
    }

    .message-box {
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: center;
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

    .login-link {
        margin-top: 25px;
        color: var(--text-secondary);
        font-size: 0.9em;
    }

    .login-link a {
        color: var(--accent-color);
        text-decoration: none;
        font-weight: 500;
    }
    </style>
</head>

<body>
    <div class="login-container">
        <h1>Create Account</h1>
        <?php if (!empty($error_message)): ?>
        <div class="message-box error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <form method="POST" action="register.php">
            <div class="form-group">
                <i class="icon fas fa-user"></i>
                <input type="text" name="username" placeholder="Username"
                    value="<?php echo isset($_POST["username"]) ? htmlspecialchars($_POST["username"]) : ""; ?>"
                    required>
            </div>
            <div class="form-group">
                <i class="icon fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password (min. 6 characters)" required>
            </div>
            <div class="form-group">
                <i class="icon fas fa-check-circle"></i>
                <input type="password" name="password_confirm" placeholder="Confirm Password" required>
            </div>
            <button type="submit" class="btn-submit">Register</button>
        </form>
        <p class="login-link">
            Already have an account? <a href="login.php">Log In</a>
        </p>
    </div>
</body>

</html>