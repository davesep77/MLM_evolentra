<?php
session_start();
require 'config_db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = $_POST['email'];
    $password = $_POST['password'];

    try {
        $stmt = $conn->prepare("SELECT id, username, password, role FROM mlm_users WHERE email = :input OR username = :input");
        $stmt->execute(['input' => $input]);

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "User not found.";
        }
    } catch (PDOException $e) {
        $error = "Login error. Please try again.";
        error_log("Login error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Evolentra</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">
        <div class="glass-card auth-box">
            <div class="logo">Evolentra</div>
            <h2 style="text-align: center; margin-bottom: 2rem;">Welcome Back</h2>

            <?php if(isset($error)): ?>
                <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.5); color: #fca5a5; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Email Address or Username</label>
                    <input type="text" name="email" required placeholder="Enter username or email">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Enter your password">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Sign In</button>
            </form>

            <p style="text-align: center; margin-top: 1.5rem; font-size: 0.875rem; color: #94a3b8;">
                Don't have an account? <a href="register.php" style="color: var(--primary-color); text-decoration: none;">Register</a>
            </p>
            <p style="text-align: center; margin-top: 0.5rem; font-size: 0.875rem;">
                <a href="index.php" style="color: #64748b; text-decoration: none;">&larr; Back to Home</a>
            </p>
        </div>
    </div>
</body>
</html>
