<?php
/**
 * Admin Login Page
 * Smart Student Management System
 */

session_start();

// Already logged in → redirect to dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: ../dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../includes/db.php';

    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT id, username, password, full_name FROM admin_users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);
            $_SESSION['admin_id']        = $admin['id'];
            $_SESSION['admin_username']  = $admin['username'];
            $_SESSION['admin_fullname']  = $admin['full_name'];

            // Update last login
            $upd = $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = :id");
            $upd->execute([':id' => $admin['id']]);

            logActivity('LOGIN', 'Admin logged in');
            header('Location: ../dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password. Please try again.';
        }
    } else {
        $error = 'Both username and password are required.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — StudentMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus Jakarta Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #4F46E5;
            --primary-dark: #3730A3;
            --secondary: #06B6D4;
            --bg: #F9FAFB;
            --text: #111827;
            --muted: #6B7280;
            --border: #E5E7EB;
            --white: #ffffff;
            --error: #EF4444;
            --shadow: 0 20px 60px rgba(79,70,229,0.15);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow: hidden;
        }

        /* Animated background */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 10%, rgba(79,70,229,0.12) 0%, transparent 60%),
                radial-gradient(ellipse 60% 40% at 80% 90%, rgba(6,182,212,0.10) 0%, transparent 60%);
            z-index: 0;
        }

        .login-card {
            position: relative;
            z-index: 1;
            background: var(--white);
            border-radius: 24px;
            padding: 48px 44px;
            width: 100%;
            max-width: 440px;
            box-shadow: var(--shadow);
            border: 1px solid rgba(79,70,229,0.08);
            animation: slideUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .login-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 32px;
        }

        .login-logo .icon-wrap {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            box-shadow: 0 8px 24px rgba(79,70,229,0.35);
        }

        .login-logo .brand { font-weight: 700; font-size: 22px; color: var(--text); }
        .login-logo .brand span { color: var(--primary); }

        h1 { font-size: 26px; font-weight: 700; color: var(--text); margin-bottom: 6px; }
        .subtitle { color: var(--muted); font-size: 14px; margin-bottom: 32px; }

        .form-group { margin-bottom: 20px; }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
            letter-spacing: 0.02em;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 15px;
            transition: color 0.2s;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 14px;
            color: var(--text);
            background: #FAFAFA;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79,70,229,0.08);
            background: white;
        }

        input:focus + i, .input-wrap:focus-within i { color: var(--primary); }

        /* Fix icon layering - icon before input */
        .input-wrap input { order: 2; }
        .input-wrap i { pointer-events: none; z-index: 1; }

        .toggle-pass {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--muted);
            font-size: 15px;
            border: none;
            background: none;
            padding: 0;
        }

        .toggle-pass:hover { color: var(--primary); }

        .error-box {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            border-radius: 10px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--error);
            font-size: 13px;
            margin-bottom: 20px;
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%,100% { transform: translateX(0); }
            25% { transform: translateX(-6px); }
            75% { transform: translateX(6px); }
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary) 0%, #6366F1 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
            box-shadow: 0 4px 16px rgba(79,70,229,0.35);
            margin-top: 8px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(79,70,229,0.45);
        }

        .btn-login:active { transform: translateY(0); }

        .hint {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: var(--muted);
        }

        .hint strong { color: var(--primary); }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-logo">
        <div class="icon-wrap"><i class="fas fa-graduation-cap"></i></div>
        <div class="brand">Student<span>MS</span></div>
    </div>

    <h1>Welcome Back</h1>
    <p class="subtitle">Sign in to your admin dashboard</p>

    <?php if ($error): ?>
        <div class="error-box">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Username</label>
            <div class="input-wrap">
                <i class="fas fa-user"></i>
                <input type="text" id="username" name="username"
                       placeholder="Enter your username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       autocomplete="username" required>
            </div>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="input-wrap">
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password"
                       placeholder="Enter your password"
                       autocomplete="current-password" required>
                <button type="button" class="toggle-pass" onclick="togglePassword()" tabindex="-1">
                    <i class="fas fa-eye" id="toggleIcon"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-login">
            <i class="fas fa-sign-in-alt"></i>&nbsp; Sign In
        </button>
    </form>

    <p class="hint">Demo credentials: <strong>admin</strong> / <strong>admin123</strong></p>
</div>

<script>
function togglePassword() {
    const pw   = document.getElementById('password');
    const icon = document.getElementById('toggleIcon');
    if (pw.type === 'password') {
        pw.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        pw.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
</body>
</html>
