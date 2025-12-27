<?php
require 'config_db.php';

// Security: Ideally check for admin role, but for local mvp we skip
if (!isset($_SESSION['user_id'])) { die("Access Denied"); }

$output = "";
if (isset($_POST['run_roi'])) {
    ob_start();
    include 'cron_roi.php';
    $output .= ob_get_clean();
}
if (isset($_POST['run_binary'])) {
    ob_start();
    include 'cron_binary.php';
    $output .= ob_get_clean();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Debug - Evolentra</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="glass-card" style="max-width: 600px; margin: 2rem auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2>Admin Debug</h2>
                <a href="dashboard.php" class="btn btn-outline">&larr; Dashboard</a>
            </div>

            <p style="color: #94a3b8; margin-bottom: 1.5rem;">Manually trigger cron jobs to simulate daily cycle passage.</p>

            <form method="POST" style="display: flex; gap: 1rem;">
                <button type="submit" name="run_roi" class="btn btn-primary">Run Daily ROI</button>
                <button type="submit" name="run_binary" class="btn btn-outline">Run Binary Match</button>
            </form>

            <?php if($output): ?>
                <div style="margin-top: 2rem; background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 0.5rem; font-family: monospace; color: #cbd5e1; white-space: pre-wrap;">
<?= htmlspecialchars($output) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
