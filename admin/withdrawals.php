<?php
require '../config_db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = "";

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $action = $_POST['action'];

    $req = $conn->query("SELECT * FROM mlm_withdrawal_requests WHERE id=$id AND status='pending'")->fetch_assoc();
    
    if ($req) {
        if ($action === 'approve' || $action === 'instant_process') {
            $admin_txid = $conn->real_escape_string($_POST['admin_txid'] ?? '');
            $conn->query("UPDATE mlm_withdrawal_requests SET status='completed', processed_at=NOW(), admin_txid='$admin_txid' WHERE id=$id");
            $method = strtoupper($req['payment_method']);
            $message = "Withdrawal #$id marked as COMPLETED via $method." . ($admin_txid ? " TxID Logged: $admin_txid" : "");
        } elseif ($action === 'reject') {
            // Refund logic
            $amount = $req['amount'];
            $user_id = $req['user_id'];
            $wallet_col = $req['wallet_type']; // ROI_wallet, referral_wallet, binary_wallet

            // Sanity check column name to prevent SQL injection or errors
            $allowed_wallets = ['roi_wallet', 'referral_wallet', 'binary_wallet'];
            if (in_array($wallet_col, $allowed_wallets)) {
                $conn->query("UPDATE mlm_wallets SET $wallet_col = $wallet_col + $amount WHERE user_id=$user_id");
                $conn->query("UPDATE mlm_withdrawal_requests SET status='rejected', processed_at=NOW() WHERE id=$id");
                $message = "Withdrawal #$id rejected and refunded.";
            } else {
                $message = "Error: Invalid wallet type.";
            }
        }
    }
}

// Filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';
$requests = $conn->query("SELECT w.*, u.username FROM mlm_withdrawal_requests w JOIN mlm_users u ON w.user_id = u.id WHERE w.status='$status_filter' ORDER BY w.requested_at DESC");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Withdrawals - Admin Panel</title>
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
            <h1 class="text-3xl font-bold text-white">Withdrawal Requests</h1>
        </header>

        <?php if($message): ?>
            <div class="bg-indigo-500/20 text-indigo-200 p-4 rounded mb-6 border border-indigo-500/50">
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
                        <th class="px-6 py-3">Wallet</th>
                        <th class="px-6 py-3">Method</th>
                        <th class="px-6 py-3">Address</th>
                        <th class="px-6 py-3">Date</th>
                        <?php if($status_filter == 'pending'): ?><th class="px-6 py-3">Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($requests->num_rows > 0): ?>
                        <?php while ($r = $requests->fetch_assoc()): ?>
                        <tr class="border-b border-slate-700/50 hover:bg-slate-700/30">
                            <td class="px-6 py-4">#<?= $r['id'] ?></td>
                            <td class="px-6 py-4 font-bold text-white"><?= htmlspecialchars($r['username']) ?></td>
                            <td class="px-6 py-4 text-emerald-400 font-bold">$<?= number_format($r['amount'], 2) ?></td>
                            <td class="px-6 py-4 capitalize"><?= str_replace('_', ' ', $r['wallet_type']) ?></td>
                            <td class="px-6 py-4"><?= htmlspecialchars($r['payment_method']) ?></td>
                            <td class="px-6 py-4 font-mono text-xs"><?= htmlspecialchars($r['payment_address']) ?></td>
                            <td class="px-6 py-4 text-slate-400"><?= date('M d, H:i', strtotime($r['requested_at'])) ?></td>
                            <?php if($status_filter == 'pending'): ?>
                                <form method="POST" onsubmit="return confirm('Approve this withdrawal?');" class="flex flex-col gap-2">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="text" name="admin_txid" placeholder="Payout TxID (Optional)" class="bg-slate-900 border border-slate-700 rounded px-2 py-1 text-xs">
                                    <button type="submit" class="bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/40 px-3 py-1 rounded">Approve & Log TxID</button>
                                </form>
                                
                                <!-- AUTO PAYOUT BUTTON -->
                                <form action="process_auto_payout.php" method="POST" target="_blank" onsubmit="return confirm('WARNING: This will automatically send real crypto funds to the user address. Proceed?');" class="mt-2 text-center">
                                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white px-3 py-1 rounded text-xs font-bold flex items-center justify-center gap-1">
                                        <i class="fas fa-bolt"></i> Auto-Pay via Binance
                                    </button>
                                </form>

                                <!-- C2C PAYOUT FORM -->
                                <form action="process_payout_c2c.php" method="POST" target="_blank" onsubmit="return confirm('Send funds to Binance ID/Email?');" class="mt-2 text-center p-2 bg-slate-800 rounded border border-slate-700">
                                    <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                    <div class="flex gap-1 mb-1">
                                        <select name="transfer_type" class="bg-slate-900 text-xs border border-slate-700 rounded px-1 py-1 text-slate-300 w-1/3">
                                            <option value="BINANCE_ID">UID</option>
                                            <option value="EMAIL">Email</option>
                                        </select>
                                        <input type="text" name="receiver" placeholder="Binance ID / Email" class="bg-slate-900 border border-slate-700 rounded px-2 py-1 text-xs w-2/3 text-white" required>
                                    </div>
                                    <button type="submit" class="w-full bg-yellow-600 hover:bg-yellow-500 text-white px-3 py-1 rounded text-xs font-bold flex items-center justify-center gap-1">
                                        <i class="fas fa-paper-plane"></i> Pay to Binance ID
                                    </button>
                                </form>

                                <form method="POST" onsubmit="return confirm('Reject and refund to wallet?');" class="mt-2">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="w-full bg-red-500/20 text-red-400 hover:bg-red-500/40 px-3 py-1 rounded">Reject</button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center py-8 text-slate-500">No requests found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
