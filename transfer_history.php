<?php
require 'config_db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Initial sort/filter params could go here

// Fetch Transfer History (Sent & Received)
$query = "SELECT tr.*, 
                 s.username as sender_name, 
                 r.username as receiver_name 
          FROM mlm_transfer_requests tr
          LEFT JOIN mlm_users s ON tr.sender_id = s.id
          LEFT JOIN mlm_users r ON tr.receiver_id = r.id
          WHERE tr.sender_id = $user_id OR tr.receiver_id = $user_id
          ORDER BY tr.created_at DESC";

$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer History - Evolentra</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .page-header {
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        /* Glass Table Styles (reusing similar styles if available, or defining specific here) */
        .glass-table-container {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
        }

        .custom-table th {
            background: rgba(0, 0, 0, 0.2);
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            color: #94a3b8;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .custom-table td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: #e2e8f0;
            font-size: 0.95rem;
        }

        .custom-table tr:last-child td {
            border-bottom: none;
        }

        .custom-table tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        /* Status Badges */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.pending { background: rgba(234, 179, 8, 0.15); color: #facc15; }
        .status-badge.approved { background: rgba(16, 185, 129, 0.15); color: #34d399; }
        .status-badge.rejected { background: rgba(239, 68, 68, 0.15); color: #f87171; }

        /* Amount Colors */
        .amount-sent { color: #f87171; }
        .amount-received { color: #34d399; }
        
        .user-avatar-sm {
            width: 24px;
            height: 24px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            margin-right: 0.5rem;
            color: #ccc;
        }

        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar_nav.php'; ?>
        
        <div class="main-content">
            <div class="container">
                <div class="page-header">
                    <div>
                        <h2 style="font-size: 2rem; font-weight: 800; margin-bottom: 0.5rem;">Transfer History</h2>
                        <p style="color: #94a3b8;">Review your sent and received fund transfers</p>
                    </div>
                    <a href="transfer.php" class="btn btn-primary">
                        <i class="fas fa-paper-plane" style="margin-right:0.5rem"></i> New Transfer
                    </a>
                </div>

                <div class="glass-table-container table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Transaction Type</th>
                                <th>Counterparty</th>
                                <th>Amount</th>
                                <th>Wallet</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): 
                                    $is_sender = ($row['sender_id'] == $user_id);
                                    $type_label = $is_sender ? 'Sent' : 'Received';
                                    $type_icon = $is_sender ? 'fa-arrow-up' : 'fa-arrow-down';
                                    $amount_class = $is_sender ? 'amount-sent' : 'amount-received';
                                    $counterparty_name = $is_sender ? $row['receiver_name'] : $row['sender_name'];
                                    $formatted_date = date('M d, Y H:i', strtotime($row['created_at']));
                                ?>
                                <tr>
                                    <td style="color: #94a3b8; font-family: monospace;"><?= $formatted_date ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <div style="width: 28px; height: 28px; border-radius: 50%; background: <?= $is_sender ? 'rgba(239, 68, 68, 0.15)' : 'rgba(16, 185, 129, 0.15)' ?>; display: flex; align-items: center; justify-content: center; color: <?= $is_sender ? '#f87171' : '#34d399' ?>;">
                                                <i class="fas <?= $type_icon ?>"></i>
                                            </div>
                                            <span style="font-weight: 500; color: <?= $is_sender ? '#f87171' : '#34d399' ?>;"><?= $type_label ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center;">
                                            <div class="user-avatar-sm"><i class="fas fa-user"></i></div>
                                            <span><?= htmlspecialchars($counterparty_name) ?></span>
                                        </div>
                                    </td>
                                    <td class="<?= $amount_class ?>" style="font-weight: 700; font-family: monospace; font-size: 1rem;">
                                        <?= $is_sender ? '-' : '+' ?>$<?= number_format($row['amount'], 2) ?>
                                    </td>
                                    <td style="text-transform: capitalize; color: #cbd5e1;"><?= str_replace('_', ' ', $row['wallet_type']) ?></td>
                                    <td>
                                        <span class="status-badge <?= strtolower($row['status']) ?>">
                                            <?= strtolower($row['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 3rem;">
                                        <div style="color: #64748b; margin-bottom: 0.5rem; font-size: 3rem;"><i class="fas fa-search-dollar"></i></div>
                                        <div style="color: #94a3b8;">No transfer history found.</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</body>
</html>
