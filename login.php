<?php
// Always start the session at the very beginning
session_start();

// If user is already logged in, redirect them to their dashboard
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    header("Location: {$role}/dashboard.php");
    exit();
}

require_once 'includes/db_connect.php';

$error_message = '';

// Check if the form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error_message = "Username and password are required.";
    } else {
        // Prepare a statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? AND is_active = 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // Verify the hashed password
            if (password_verify($password, $user['password'])) {
                // Password is correct, start the session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Redirect user to their respective dashboard
                header("Location: {$user['role']}/dashboard.php");
                exit();
            } else {
                $error_message = "Invalid username or password.";
            }
        } else {
            $error_message = "Invalid username or password.";
        }
        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpeg" href="assets/images/logo.jpg">
    <title>Login - Divya Imaging Center</title>
    <link rel="stylesheet" href="assets/css/complete.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<body class="login-page">
    <div class="login-container vibe-bg">
        <div class="login-box vibe-card">
            <div class="login-header" style="display: flex; align-items: center; justify-content: center; gap: 18px; margin-bottom: 18px;">
                <img src="assets/images/logo.jpg" alt="Divya Imaging Center Logo" style="height: 54px; width: 54px; border-radius: 12px; box-shadow: 0 2px 12px rgba(127,83,172,0.13); background: #fff; animation: logoPop 1.2s cubic-bezier(.4,0,.2,1);">
                <h2 style="margin: 0; font-size: 2rem; font-weight: 800; background: linear-gradient(90deg,#7F53AC,#FF6E7F); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; text-fill-color: transparent; letter-spacing: 1px; animation: popIn 1.1s cubic-bezier(.4,0,.2,1);">Divya Imaging Center</h2>
            </div>
            <p style="font-size: 1.1rem; color: #7F53AC; font-weight: 500; margin-bottom: 24px; letter-spacing: 0.5px; animation: fadeInBg 1.2s;">Please log in to continue</p>
            <?php if (!empty($error_message)): ?>
                <div class="error-banner"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <form action="login.php" method="post">
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-login">Login</button>
            </form>
        </div>
    </div>
    <style>
    .vibe-bg {
        min-height: 100vh;
        background: linear-gradient(120deg, #7F53AC, #647DEE, #FF6E7F, #F7971E, #43E97B, #38F9D7);
        background-size: 400% 400%;
        animation: gradientBGmove 18s ease-in-out infinite;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .vibe-card {
        position: relative;
        background: rgba(255,255,255,0.92);
        border-radius: 20px;
        box-shadow: 0 8px 32px rgba(127, 83, 172, 0.18);
        padding: 48px 36px 36px 36px;
        overflow: hidden;
        z-index: 1;
        animation: fadeInCard 1.1s cubic-bezier(.4,0,.2,1);
    }
    .vibe-card::before {
        content: '';
        position: absolute;
        top: -60px; left: -60px;
        width: 140px; height: 140px;
        background: linear-gradient(120deg, #7F53AC 0%, #FF6E7F 100%);
        opacity: 0.13;
        border-radius: 50%;
        filter: blur(18px);
        z-index: 0;
        animation: floatBlob 8s ease-in-out infinite alternate;
    }
    @keyframes logoPop {
        from { transform: scale(0.7) rotate(-10deg); opacity: 0; }
        to { transform: scale(1) rotate(0); opacity: 1; }
    }
    @keyframes gradientBGmove {
        0% { background-position: 0% 50%; }
        25% { background-position: 50% 100%; }
        50% { background-position: 100% 50%; }
        75% { background-position: 50% 0%; }
        100% { background-position: 0% 50%; }
    }
    @keyframes fadeInCard {
        from { opacity: 0; transform: translateY(40px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes floatBlob {
        0% { transform: scale(1) translateY(0); }
        100% { transform: scale(1.2) translateY(20px); }
    }
    </style>
</body>
</html>
