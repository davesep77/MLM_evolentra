<?php
session_start();
require 'config_db.php';

$message = "";
$ref_code = isset($_GET['ref']) ? $_GET['ref'] : '';
$url_sponsor = '';
$url_position = isset($_GET['position']) ? strtoupper($_GET['position']) : '';

if ($ref_code) {
    try {
        $stmt = $conn->prepare("SELECT username FROM mlm_users WHERE referral_code = :ref_code");
        $stmt->execute(['ref_code' => $ref_code]);
        if ($sponsor = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $url_sponsor = $sponsor['username'];
        }
    } catch (PDOException $e) {
        error_log("Referral lookup error: " . $e->getMessage());
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $sponsor = isset($_POST['sponsor']) ? trim($_POST['sponsor']) : null;
    $position = isset($_POST['position']) ? strtolower($_POST['position']) : null;

    try {
        $stmt = $conn->prepare("SELECT id FROM mlm_users WHERE email = :email OR username = :username");
        $stmt->execute(['email' => $email, 'username' => $username]);

        if ($stmt->fetch()) {
            $message = "Error: Username or Email already exists.";
        } else {
            $sponsor_id = null;
            if ($sponsor) {
                $stmt = $conn->prepare("SELECT id FROM mlm_users WHERE username = :sponsor");
                $stmt->execute(['sponsor' => $sponsor]);
                if ($s = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $sponsor_id = $s['id'];
                }
            }

            $binary_position = null;
            if ($sponsor_id && $position) {
                $binary_position = $position;
            }

            $refCode = strtoupper(substr($username, 0, 3) . rand(1000, 9999));

            $stmt = $conn->prepare("
                INSERT INTO mlm_users (username, email, password, sponsor_id, binary_position, referral_code, created_at)
                VALUES (:username, :email, :password, :sponsor_id, :position, :ref_code, NOW())
            ");

            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'sponsor_id' => $sponsor_id,
                'position' => $binary_position,
                'ref_code' => $refCode
            ]);

            $user_id = $conn->lastInsertId();

            $stmt = $conn->prepare("INSERT INTO mlm_wallets (user_id) VALUES (:user_id)");
            $stmt->execute(['user_id' => $user_id]);

            $stmt = $conn->prepare("
                INSERT INTO mlm_referral_links (user_id, referral_code, link_type)
                VALUES (:user_id, :ref_code, 'general')
            ");
            $stmt->execute(['user_id' => $user_id, 'ref_code' => $refCode]);

            $message = "Registration successful! <a href='login.php' style='color: var(--primary-color);'>Login here</a>";
        }
    } catch (PDOException $e) {
        $message = "Error: Registration failed. Please try again.";
        error_log("Registration error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Evolentra</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">
        <div class="glass-card auth-box">
            <div class="logo">Evolentra</div>
            <h2 style="text-align: center; margin-bottom: 2rem;">Create Account</h2>

            <?php if($message): ?>
                <div style="background: rgba(99, 102, 241, 0.2); border: 1px solid rgba(99, 102, 241, 0.5); color: #e0e7ff; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem;">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required placeholder="Choose a username" minlength="3">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="name@example.com">
                </div>
                <div class="form-group">
                    <label>Sponsor <?= $url_sponsor ? '(From Referral Link)' : '(Optional)' ?></label>
                    <input type="text" name="sponsor" value="<?= htmlspecialchars($url_sponsor) ?>" placeholder="Sponsor username" <?= $url_sponsor ? 'readonly style="opacity: 0.7; cursor: not-allowed;"' : '' ?>>
                    <?php if ($url_position): ?>
                        <input type="hidden" name="position" value="<?= htmlspecialchars($url_position) ?>">
                        <p style="font-size: 0.8rem; color: #10b981; margin-top: 0.5rem;">
                            <i class="fas fa-check-circle"></i> Position: <?= $url_position === 'LEFT' ? 'Left Team' : 'Right Team' ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Create a password" minlength="6">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Create Account</button>
            </form>

            <p style="text-align: center; margin-top: 1.5rem; font-size: 0.875rem; color: #94a3b8;">
                Already have an account? <a href="login.php" style="color: var(--primary-color); text-decoration: none;">Login</a>
            </p>
            <p style="text-align: center; margin-top: 0.5rem; font-size: 0.875rem;">
                <a href="index.php" style="color: #64748b; text-decoration: none;">&larr; Back to Home</a>
            </p>
        </div>
    </div>
</body>
</html>
