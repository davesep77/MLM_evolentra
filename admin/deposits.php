<?php
require '../config_db.php';
require_once '../lib/Compensation.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = "";
$error = "";

// Handle Approval / Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $action = $_POST['action'];
    $note = $conn->real_escape_string($_POST['admin_note'] ?? '');

    $dep = $conn->query("SELECT d.*, u.sponsor_id FROM mlm_deposits d JOIN mlm_users u ON d.user_id = u.id WHERE d.id=$id AND d.status='pending'")->fetch_assoc();
    
    if ($dep) {
        $user_id = $dep['user_id'];
        $amount = $dep['amount'];

        if ($action === 'approve') {
            // 1. Update Deposit Status
            $conn->query("UPDATE mlm_deposits SET status='approved', processed_at=NOW(), admin_note='$note' WHERE id=$id");
            
            // 2. Add to User Investment Balance
            $conn->query("UPDATE mlm_users SET investment = investment + $amount WHERE id=$user_id");
            
            // 3. Log Final Transaction
            $conn->query("INSERT INTO mlm_transactions (user_id, type, amount, description) VALUES ($user_id, 'DEPOSIT_CONFIRMED', $amount, 'Deposit approved by Admin')");
            
            // 4. Trigger Referral Commission
            $comp = new Compensation($conn);
            if ($dep['sponsor_id']) {
                $comp->processReferral($amount, $dep['sponsor_id']);
            }

            // 5. Notify User
            $conn->query("INSERT INTO mlm_notifications (user_id, title, message, type) VALUES ($user_id, 'Deposit Approved', 'Your deposit of $amount USDT has been verified and added to your capital!', 'success')");

            $message = "Deposit #$id approved successfully.";
        } elseif ($action === 'reject') {
            $conn->query("UPDATE mlm_deposits SET status='rejected', processed_at=NOW(), admin_note='$note' WHERE id=$id");
            
            // Notify User
            $conn->query("INSERT INTO mlm_notifications (user_id, title, message, type) VALUES ($user_id, 'Deposit Rejected', 'Your deposit of $amount USDT was rejected. Note: $note', 'error')");
            
            $message = "Deposit #$id rejected.";
        }
    }
}

// Filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';
$deposits = $conn->query("SELECT d.*, u.username FROM mlm_deposits d JOIN mlm_users u ON d.user_id = u.id WHERE d.status='$status_filter' ORDER BY d.created_at DESC");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Deposits - Admin Panel</title>
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
            <h1 class="text-3xl font-bold text-white">Deposit Verification</h1>
        </header>

        <?php if($message): ?>
            <div class="bg-emerald-500/20 text-emerald-200 p-4 rounded mb-6 border border-emerald-500/50">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="flex gap-4 mb-6">
            <a href="?status=pending" class="px-4 py-2 rounded-lg <?= $status_filter == 'pending' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700' ?>">Pending</a>
            <a href="?status=approved" class="px-4 py-2 rounded-lg <?= $status_filter == 'approved' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700' ?>">Approved</a>
            <a href="?status=rejected" class="px-4 py-2 rounded-lg <?= $status_filter == 'rejected' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700' ?>">Rejected</a>
        </div>

        <div class="glass-card overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="text-xs uppercase bg-slate-800 text-slate-400">
                    <tr>
                        <th class="px-6 py-3">ID</th>
                        <th class="px-6 py-3">User</th>
                        <th class="px-6 py-3">Amount</th>
                        <th class="px-6 py-3">Network</th>
                        <th class="px-6 py-3">TxID (Hash)</th>
                        <th class="px-6 py-3">Date</th>
                        <?php if($status_filter == 'pending'): ?><th class="px-6 py-3">Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($deposits->num_rows > 0): ?>
                        <?php while ($d = $deposits->fetch_assoc()): ?>
                        <tr class="border-b border-slate-700/50 hover:bg-slate-700/30">
                            <td class="px-6 py-4">#<?= $d['id'] ?></td>
                            <td class="px-6 py-4 font-bold text-white"><?= htmlspecialchars($d['username']) ?></td>
                            <td class="px-6 py-4 text-emerald-400 font-bold"><?= number_format($d['amount'], 2) ?> USDT</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded text-xs <?= $d['network'] == 'BEP20' ? 'bg-yellow-500/20 text-yellow-500' : 'bg-emerald-500/20 text-emerald-500' ?>">
                                    <?= $d['network'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <a href="https://bscscan.com/tx/<?= $d['txid'] ?>" target="_blank" class="font-mono text-xs text-indigo-400 hover:underline">
                                    <?= substr($d['txid'], 0, 10) ?>...<?= substr($d['txid'], -10) ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 text-slate-400"><?= date('M d, H:i', strtotime($d['created_at'])) ?></td>
                            <?php if($status_filter == 'pending'): ?>
                            <td class="px-6 py-4">
                                <form method="POST" class="flex gap-2">
                                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                    <input type="text" name="admin_note" placeholder="Note (optional)" class="bg-slate-900 border border-slate-700 rounded px-2 py-1 text-xs w-24">
                                    <button type="submit" name="action" value="approve" class="bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/40 px-3 py-1 rounded text-xs transition">Approve</button>
                                    <button type="submit" name="action" value="reject" class="bg-red-500/20 text-red-400 hover:bg-red-500/40 px-3 py-1 rounded text-xs transition">Reject</button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-8 text-slate-500">No records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
