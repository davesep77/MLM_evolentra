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

    $req = $conn->query("SELECT * FROM mlm_transfer_requests WHERE id=$id AND status='pending'")->fetch_assoc();
    
    if ($req) {
        $sender_id = $req['sender_id'];
        $receiver_id = $req['receiver_id'];
        $amount = $req['amount'];
        $wallet_type = $req['wallet_type'];

        if ($action === 'approve') {
            $conn->begin_transaction();
            try {
                // Credit Receiver (Add to ROI wallet as general balance)
                $conn->query("UPDATE mlm_wallets SET roi_wallet = roi_wallet + $amount WHERE user_id=$receiver_id");

                // Update Request Status
                $conn->query("UPDATE mlm_transfer_requests SET status='approved', processed_at=NOW() WHERE id=$id");

                // Log Transaction for Receiver
                $conn->query("INSERT INTO mlm_transactions (user_id, type, amount, description) VALUES ($receiver_id, 'transfer_in', $amount, 'Transfer Received from User #$sender_id')");

                // Update Sender Transaction Log (Optional: Change description or type?)
                // Just keeping the original 'transfer_request' log is fine, or adding a 'transfer_complete' log.
                $conn->query("INSERT INTO mlm_transactions (user_id, type, amount, description) VALUES ($sender_id, 'transfer_out', $amount, 'Transfer Approved to User #$receiver_id')");

                // Notification
                require_once '../api/notifications.php';
                createNotification($conn, $receiver_id, 'Funds Received', "You used received $$amount from User #$sender_id.", 'success');
                createNotification($conn, $sender_id, 'Transfer Approved', "Your transfer of $$amount to User #$receiver_id has been approved.", 'success');

                $conn->commit();
                $message = "Transfer #$id approved.";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Error approving: " . $e->getMessage();
            }
        } elseif ($action === 'reject') {
            $conn->begin_transaction();
            try {
                // Refund Sender
                $conn->query("UPDATE mlm_wallets SET $wallet_type = $wallet_type + $amount WHERE user_id=$sender_id");
                
                // Update Request Status
                $conn->query("UPDATE mlm_transfer_requests SET status='rejected', processed_at=NOW() WHERE id=$id");

                // Log Refund
                $conn->query("INSERT INTO mlm_transactions (user_id, type, amount, description) VALUES ($sender_id, 'transfer_refund', $amount, 'Transfer #$id Rejected')");

                // Notification
                require_once '../api/notifications.php';
                createNotification($conn, $sender_id, 'Transfer Rejected', "Your transfer request of $$amount was rejected and refunded.", 'error');

                $conn->commit();
                $message = "Transfer #$id rejected and refunded.";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Error rejecting: " . $e->getMessage();
            }
        }
    }
}

// Fetch Requests
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';
$sql = "SELECT tr.*, s.username as sender_name, r.username as receiver_name 
        FROM mlm_transfer_requests tr
        JOIN mlm_users s ON tr.sender_id = s.id
        JOIN mlm_users r ON tr.receiver_id = r.id
        WHERE tr.status='$status_filter' 
        ORDER BY tr.created_at DESC";
$requests = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transfer Requests - Admin Panel</title>
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
            <h1 class="text-3xl font-bold text-white">Transfer Requests</h1>
        </header>

        <?php if($message): ?>
            <div class="bg-indigo-500/20 text-indigo-200 p-4 rounded mb-6 border border-indigo-500/50">
                <?= $message ?>
            </div>
        <?php endif; ?>

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
                        <th class="px-6 py-3">Sender</th>
                        <th class="px-6 py-3">Receiver</th>
                        <th class="px-6 py-3">Amount</th>
                        <th class="px-6 py-3">From Wallet</th>
                        <th class="px-6 py-3">Date</th>
                        <?php if($status_filter == 'pending'): ?><th class="px-6 py-3">Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($requests->num_rows > 0): ?>
                        <?php while ($r = $requests->fetch_assoc()): ?>
                        <tr class="border-b border-slate-700/50 hover:bg-slate-700/30">
                            <td class="px-6 py-4">#<?= $r['id'] ?></td>
                            <td class="px-6 py-4 text-white"><?= htmlspecialchars($r['sender_name']) ?></td>
                            <td class="px-6 py-4 text-indigo-400">➜ <?= htmlspecialchars($r['receiver_name']) ?></td>
                            <td class="px-6 py-4 text-emerald-400 font-bold">$<?= number_format($r['amount'], 2) ?></td>
                            <td class="px-6 py-4 capitalize"><?= str_replace('_', ' ', $r['wallet_type']) ?></td>
                            <td class="px-6 py-4 text-slate-400"><?= date('M d, H:i', strtotime($r['created_at'])) ?></td>
                            <?php if($status_filter == 'pending'): ?>
                            <td class="px-6 py-4 flex gap-2">
                                <form method="POST" onsubmit="return confirm('Approve this transfer?');">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/40 px-3 py-1 rounded">Approve</button>
                                </form>
                                <form method="POST" onsubmit="return confirm('Reject and refund sender?');">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="bg-red-500/20 text-red-400 hover:bg-red-500/40 px-3 py-1 rounded">Reject</button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-8 text-slate-500">No requests found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
