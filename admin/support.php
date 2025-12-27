<?php
require '../config_db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = "";

// Handle Reply/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $response = $conn->real_escape_string($_POST['response']);
    $status = $_POST['status']; // open, in_progress, closed

    $sql = "UPDATE mlm_support_tickets SET admin_response='$response', status='$status', updated_at=NOW() WHERE id=$id";
    if ($conn->query($sql) === TRUE) {
        $message = "Ticket #$id updated successfully.";
        
        // Notify User
        $t_user = $conn->query("SELECT user_id FROM mlm_support_tickets WHERE id=$id")->fetch_assoc();
        if ($t_user) {
            require_once '../api/notifications.php';
            createNotification($conn, $t_user['user_id'], 'Ticket Updated', "Admin has replied to your ticket #$id.", 'info');
        }
    } else {
        $message = "Error: " . $conn->error;
    }
}

// Filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'open';
$tickets = $conn->query("SELECT t.*, u.username FROM mlm_support_tickets t JOIN mlm_users u ON t.user_id = u.id WHERE t.status='$status_filter' ORDER BY t.created_at ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Support Tickets - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: #f8fafc; }
        .glass-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; padding: 1.5rem; }
    </style>
</head>
<body class="flex">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 ml-[280px] p-8">
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-white">Support Tickets</h1>
        </header>

        <?php if($message): ?>
            <div class="bg-indigo-500/20 text-indigo-200 p-4 rounded mb-6 border border-indigo-500/50">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="flex gap-4 mb-6">
            <a href="?status=open" class="px-4 py-2 rounded-lg <?= $status_filter == 'open' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700' ?>">Open</a>
            <a href="?status=in_progress" class="px-4 py-2 rounded-lg <?= $status_filter == 'in_progress' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700' ?>">In Progress</a>
            <a href="?status=closed" class="px-4 py-2 rounded-lg <?= $status_filter == 'closed' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700' ?>">Closed</a>
        </div>

        <div class="space-y-4">
            <?php if ($tickets->num_rows > 0): ?>
                <?php while ($t = $tickets->fetch_assoc()): ?>
                <div class="glass-card">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="font-bold text-lg text-white mb-1">#<?= $t['id'] ?> <?= htmlspecialchars($t['subject']) ?></h3>
                            <p class="text-sm text-slate-400">From: <span class="text-indigo-400"><?= htmlspecialchars($t['username']) ?></span> &bull; <?= date('M d, H:i', strtotime($t['created_at'])) ?></p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-500/20 text-blue-300"><?= strtoupper($t['priority']) ?></span>
                    </div>
                    
                    <div class="bg-slate-800/50 p-4 rounded mb-4 text-slate-300 text-sm">
                        <?= nl2br(htmlspecialchars($t['message'])) ?>
                    </div>

                    <form method="POST" class="mt-4 border-t border-slate-700/50 pt-4">
                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                        <div class="mb-4">
                            <label class="block text-xs uppercase text-slate-500 font-bold mb-2">Admin Response</label>
                            <textarea name="response" rows="3" class="w-full bg-slate-900 border border-slate-700 rounded p-3 text-sm focus:outline-none focus:border-indigo-500" placeholder="Type your reply..."><?= htmlspecialchars($t['admin_response'] ?? '') ?></textarea>
                        </div>
                        <div class="flex items-center gap-4">
                            <select name="status" class="bg-slate-800 border border-slate-700 rounded px-3 py-2 text-sm focus:outline-none">
                                <option value="open" <?= $t['status'] == 'open' ? 'selected' : '' ?>>Open</option>
                                <option value="in_progress" <?= $t['status'] == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="closed" <?= $t['status'] == 'closed' ? 'selected' : '' ?>>Closed</option>
                            </select>
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded text-sm font-bold">Update Ticket</button>
                        </div>
                    </form>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-12 text-slate-500 glass-card">No tickets found in this category.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
