<?php
require '../config_db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = "";
$error = "";

// Handle Approval / Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];
    $note = $conn->real_escape_string($_POST['binance_note'] ?? '');

    if ($action === 'verify') {
        $conn->query("UPDATE mlm_user_settings SET binance_link_status='verified', binance_note='$note' WHERE user_id=$user_id");
        $conn->query("INSERT INTO mlm_notifications (user_id, title, message, type) VALUES ($user_id, 'Binance Account Linked', 'Your Binance addresses have been verified and locked. You can now withdraw funds.', 'success')");
        $message = "User #$user_id Binance account verified.";
    } elseif ($action === 'reject') {
        $conn->query("UPDATE mlm_user_settings SET binance_link_status='rejected', binance_note='$note' WHERE user_id=$user_id");
        $conn->query("INSERT INTO mlm_notifications (user_id, title, message, type) VALUES ($user_id, 'Binance Link Rejected', 'Your Binance connection request was rejected. Reason: $note', 'error')");
        $message = "User #$user_id Binance account rejected.";
    }
}

// Filter
$status_filter = $_GET['status'] ?? 'pending';
$accounts = $conn->query("
    SELECT s.*, u.username, u.email 
    FROM mlm_user_settings s 
    JOIN mlm_users u ON s.user_id = u.id 
    WHERE s.binance_link_status='$status_filter' 
    ORDER BY s.updated_at DESC
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Binance Connections - Admin</title>
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
            <h1 class="text-3xl font-bold text-white">Binance Account Connections</h1>
        </header>

        <?php if($message): ?>
            <div class="bg-emerald-500/20 text-emerald-200 p-4 rounded mb-6 border border-emerald-500/50">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="flex gap-4 mb-6">
            <a href="?status=pending" class="px-4 py-2 rounded-lg <?= $status_filter == 'pending' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700' ?>">Pending Verification</a>
            <a href="?status=verified" class="px-4 py-2 rounded-lg <?= $status_filter == 'verified' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700' ?>">Verified</a>
            <a href="?status=rejected" class="px-4 py-2 rounded-lg <?= $status_filter == 'rejected' ? 'bg-red-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700' ?>">Rejected</a>
            <a href="?status=unlinked" class="px-4 py-2 rounded-lg <?= $status_filter == 'unlinked' ? 'bg-slate-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700' ?>">Unlinked</a>
        </div>

        <div class="glass-card overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="text-xs uppercase bg-slate-800 text-slate-400">
                    <tr>
                        <th class="px-6 py-3">User</th>
                        <th class="px-6 py-3">BEP-20 (BSC)</th>
                        <th class="px-6 py-3">TRC-20 (TRON)</th>
                        <th class="px-6 py-3">Last Update</th>
                        <th class="px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($accounts->num_rows > 0): ?>
                        <?php while ($a = $accounts->fetch_assoc()): ?>
                        <tr class="border-b border-slate-700/50 hover:bg-slate-700/30">
                            <td class="px-6 py-4">
                                <div class="font-bold text-white"><?= htmlspecialchars($a['username']) ?></div>
                                <div class="text-xs text-slate-500"><?= htmlspecialchars($a['email']) ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-mono text-xs text-indigo-400"><?= $a['usdt_address_bep20'] ?: 'Not Set' ?></span>
                                <div class="text-[0.6rem] text-slate-500 mt-1">Smart Chain Network</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-mono text-xs text-emerald-400"><?= $a['usdt_address_trc20'] ?: 'Not Set' ?></span>
                                <div class="text-[0.6rem] text-slate-500 mt-1">TRON Network</div>
                            </td>
                            <td class="px-6 py-4 text-slate-400 text-xs"><?= date('M d, H:i', strtotime($a['updated_at'])) ?></td>
                            <td class="px-6 py-4">
                                <form method="POST" class="flex flex-col gap-2">
                                    <input type="hidden" name="user_id" value="<?= $a['user_id'] ?>">
                                    <input type="text" name="binance_note" placeholder="Note (e.g. Invalid Address)" class="bg-slate-900 border border-slate-700 rounded px-2 py-1 text-xs w-full">
                                    <div class="flex gap-2">
                                        <button type="submit" name="action" value="verify" class="flex-1 bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/40 px-3 py-1 rounded text-xs transition font-bold">Verify</button>
                                        <button type="submit" name="action" value="reject" class="flex-1 bg-red-500/20 text-red-400 hover:bg-red-500/40 px-3 py-1 rounded text-xs transition font-bold">Reject</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-8 text-slate-500">No connection requests found for this status.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
