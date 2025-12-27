<?php
require 'config_db.php';

// Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle Mark All Read
if (isset($_POST['mark_all_read'])) {
    $conn->query("UPDATE mlm_notifications SET is_read=1 WHERE user_id=$user_id");
}

// Fetch Notifications
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$sql = "SELECT * FROM mlm_notifications WHERE user_id=$user_id";
if ($filter === 'unread') {
    $sql .= " AND is_read=0";
}
$sql .= " ORDER BY created_at DESC LIMIT 50";
$notifications = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications - Evolentra</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: #f8fafc; }
        .glass-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; padding: 1.5rem; }
        .notif-success { border-left: 4px solid #10b981; background: rgba(16, 185, 129, 0.1); }
        .notif-wrapper { border-left: 4px solid #3b82f6; background: rgba(59, 130, 246, 0.1); }
        .notif-warning { border-left: 4px solid #f59e0b; background: rgba(245, 158, 11, 0.1); }
        .notif-error { border-left: 4px solid #ef4444; background: rgba(239, 68, 68, 0.1); }
    </style>
</head>
<body class="flex">

    <?php include 'sidebar_nav.php'; ?>

    <div class="flex-1 ml-[280px] p-8">
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-white">Notifications</h1>
            <form method="POST">
                <button type="submit" name="mark_all_read" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded text-sm transition-colors">Mark All Read</button>
            </form>
        </header>

        <div class="flex gap-4 mb-6">
            <a href="?filter=all" class="px-4 py-2 rounded-lg <?= $filter == 'all' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700' ?>">All</a>
            <a href="?filter=unread" class="px-4 py-2 rounded-lg <?= $filter == 'unread' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700' ?>">Unread</a>
        </div>

        <div class="space-y-4 max-w-3xl">
            <?php if ($notifications->num_rows > 0): ?>
                <?php while ($n = $notifications->fetch_assoc()): 
                    $borderClass = 'notif-wrapper'; // default info
                    if ($n['type'] == 'success') $borderClass = 'notif-success';
                    if ($n['type'] == 'warning') $borderClass = 'notif-warning';
                    if ($n['type'] == 'error') $borderClass = 'notif-error';
                    
                    $opacity = $n['is_read'] ? 'opacity-60' : 'opacity-100 font-medium';
                ?>
                <div class="<?= $borderClass ?> p-4 rounded mb-2 transition-all <?= $opacity ?>">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="text-white text-md mb-1 <?= $n['is_read'] ? '' : 'font-bold' ?>"><?= htmlspecialchars($n['title']) ?></h4>
                            <p class="text-slate-300 text-sm"><?= htmlspecialchars($n['message']) ?></p>
                        </div>
                        <span class="text-xs text-slate-500 whitespace-nowrap ml-4"><?= date('M d, H:i', strtotime($n['created_at'])) ?></span>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-12 text-slate-500 glass-card">No notifications found.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
