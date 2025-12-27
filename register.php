<?php
require 'config_db.php';

$message = "";

// Get sponsor and position from URL
$url_sponsor = isset($_GET['sponsor']) ? $_GET['sponsor'] : '';
$url_position = isset($_GET['position']) ? strtoupper($_GET['position']) : '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $sponsor = isset($_POST['sponsor']) ? $_POST['sponsor'] : null;
    $position = isset($_POST['position']) ? strtoupper($_POST['position']) : null;

    // Basic validation
    $check = $conn->query("SELECT id FROM mlm_users WHERE email='$email' OR username='$username'");
    if ($check->num_rows > 0) {
        $message = "Error: Username or Email already exists.";
    } else {
        // find sponsor ID
        $sponsor_id = null;
        if ($sponsor) {
            $s = $conn->query("SELECT id FROM mlm_users WHERE username='$sponsor'");
            if ($s->num_rows > 0) {
                $sponsor_id = $s->fetch_assoc()['id'];
            }
        }

        // AUTOMATIC BINARY PLACEMENT - Place on weaker leg
        $binary_position = null;
        if ($sponsor_id) {
            // If position is specified from referral link, use it
            if ($position === 'LEFT') {
                $binary_position = 'L';
            } elseif ($position === 'RIGHT') {
                $binary_position = 'R';
            } else {
                // AUTO-BALANCE: Calculate weaker leg
                $binary_position = getWeakerLeg($sponsor_id, $conn);
            }
        }

        // create user with binary position
        $sql = "INSERT INTO mlm_users (username,email,password,sponsor_id,binary_position) VALUES ('$username','$email','$password', " . ($sponsor_id ? "$sponsor_id" : "NULL") . ", " . ($binary_position ? "'$binary_position'" : "NULL") . ")";
        
        if ($conn->query($sql) === TRUE) {
            $user_id = $conn->insert_id;
            // create wallet
            $conn->query("INSERT INTO mlm_wallets (user_id) VALUES ($user_id)");
            $message = "Registration successful! <a href='login.php' style='color: var(--secondary-color);'>Login here</a>";
        } else {
            $message = "Error: " . $conn->error;
        }
    }
}

/**
 * Get the weaker leg (left or right) for automatic binary placement
 * Returns 'L' or 'R' based on which side has fewer members
 */
function getWeakerLeg($sponsor_id, $conn) {
    // Count members in left leg
    $leftCount = countTeamMembers($sponsor_id, 'L', $conn);
    
    // Count members in right leg
    $rightCount = countTeamMembers($sponsor_id, 'R', $conn);
    
    // Return the weaker leg (fewer members)
    // If equal, default to left
    return ($rightCount < $leftCount) ? 'R' : 'L';
}

/**
 * Recursively count all team members in a specific leg
 */
function countTeamMembers($user_id, $position, $conn) {
    // Get direct children in this position
    $query = "SELECT id FROM mlm_users WHERE sponsor_id = $user_id AND binary_position = '$position'";
    $result = $conn->query($query);
    
    if ($result->num_rows == 0) {
        return 0;
    }
    
    $count = $result->num_rows;
    
    // Recursively count children's teams
    while ($row = $result->fetch_assoc()) {
        $count += countTeamMembers($row['id'], 'L', $conn);
        $count += countTeamMembers($row['id'], 'R', $conn);
    }
    
    return $count;
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
                    <input type="text" name="username" required placeholder="Choose a username">
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
                    <input type="password" name="password" required placeholder="Create a password">
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
