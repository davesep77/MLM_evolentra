<?php
require '../config_db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Fetch Stats
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) FROM mlm_users")->fetch_row()[0],
    'pending_withdrawals' => $conn->query("SELECT COUNT(*) FROM mlm_withdrawal_requests WHERE status='pending'")->fetch_row()[0],
    'open_tickets' => $conn->query("SELECT COUNT(*) FROM mlm_support_tickets WHERE status='open'")->fetch_row()[0],
    'total_invested' => $conn->query("SELECT COALESCE(SUM(amount), 0) FROM mlm_transactions WHERE type='investment'")->fetch_row()[0]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Evolentra</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: #f8fafc; }
        .glass-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 1rem; padding: 1.5rem; }
    </style>
</head>
<body class="flex">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 ml-[280px] p-8">
        <header class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-white">Admin Dashboard</h1>
                <p class="text-slate-400">System Overview & Monitoring</p>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-sm text-slate-300">Welcome, Admin</span>
                <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold">A</div>
            </div>
        </header>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="glass-card">
                <div class="text-slate-400 text-sm mb-1">Total Users</div>
                <div class="text-2xl font-bold text-white"><?= number_format($stats['total_users']) ?></div>
            </div>
            <div class="glass-card">
                <div class="text-slate-400 text-sm mb-1">Total Invested</div>
                <div class="text-2xl font-bold text-emerald-400">$<?= number_format($stats['total_invested'], 2) ?></div>
            </div>
            <div class="glass-card border-orange-500/30">
                <div class="text-slate-400 text-sm mb-1">Pending Withdrawals</div>
                <div class="text-2xl font-bold text-orange-400"><?= number_format($stats['pending_withdrawals']) ?></div>
            </div>
            <div class="glass-card border-blue-500/30">
                <div class="text-slate-400 text-sm mb-1">Open Support Tickets</div>
                <div class="text-2xl font-bold text-blue-400"><?= number_format($stats['open_tickets']) ?></div>
            </div>
        </div>

        <!-- Recent Activity Sections -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Latest Users -->
            <div class="glass-card">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold">New Users</h3>
                    <a href="users.php" class="text-indigo-400 text-sm hover:text-indigo-300">View All</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-300">
                        <thead class="text-xs uppercase bg-slate-800 text-slate-400">
                            <tr>
                                <th class="px-4 py-2">User</th>
                                <th class="px-4 py-2">Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $users = $conn->query("SELECT username, created_at FROM mlm_users ORDER BY id DESC LIMIT 5");
                            while ($u = $users->fetch_assoc()):
                            ?>
                            <tr class="border-b border-slate-700/50 hover:bg-slate-700/30">
                                <td class="px-4 py-3 font-medium text-white"><?= htmlspecialchars($u['username']) ?></td>
                                <td class="px-4 py-3 text-slate-400"><?= date('M d, H:i', strtotime($u['created_at'])) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Withdrawals -->
            <div class="glass-card">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold">Pending Withdrawals</h3>
                    <a href="withdrawals.php" class="text-indigo-400 text-sm hover:text-indigo-300">Manage</a>
                </div>
                <?php if ($stats['pending_withdrawals'] > 0): ?>
                    <table class="w-full text-left text-sm text-slate-300">
                        <thead class="text-xs uppercase bg-slate-800 text-slate-400">
                            <tr>
                                <th class="px-4 py-2">User</th>
                                <th class="px-4 py-2">Amount</th>
                                <th class="px-4 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $w_reqs = $conn->query("SELECT u.username, w.amount, w.status FROM mlm_withdrawal_requests w JOIN mlm_users u ON w.user_id = u.id WHERE w.status='pending' LIMIT 5");
                            while ($w = $w_reqs->fetch_assoc()):
                            ?>
                            <tr class="border-b border-slate-700/50">
                                <td class="px-4 py-3"><?= htmlspecialchars($w['username']) ?></td>
                                <td class="px-4 py-3 font-bold text-white">$<?= number_format($w['amount'], 2) ?></td>
                                <td class="px-4 py-3"><span class="bg-yellow-500/20 text-yellow-300 px-2 py-1 rounded text-xs">Pending</span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-slate-500 text-center py-8">No pending withdrawals.</p>
                <?php endif; ?>
            </div>

        </div>

    </div>
</body>
</html>
