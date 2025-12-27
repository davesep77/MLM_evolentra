<?php
require 'config_db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch Withdrawal History
$query = "SELECT * FROM mlm_withdrawal_requests WHERE user_id = $user_id ORDER BY requested_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal Log - Evolentra</title>
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
            display: inline-block;
        }

        .status-page-pending { background: rgba(234, 179, 8, 0.15); color: #facc15; }
        .status-page-approved { background: rgba(16, 185, 129, 0.15); color: #34d399; }
        .status-page-rejected { background: rgba(239, 68, 68, 0.15); color: #f87171; }

        .wallet-addr {
            font-family: monospace;
            color: #94a3b8;
            font-size: 0.85rem;
            background: rgba(0,0,0,0.2);
            padding: 0.2rem 0.5rem;
            border-radius: 0.25rem;
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
                        <h2 style="font-size: 2rem; font-weight: 800; margin-bottom: 0.5rem;">Withdrawal Log</h2>
                        <p style="color: #94a3b8;">Track your payout requests and status</p>
                    </div>
                    <a href="withdraw.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle" style="margin-right:0.5rem"></i> Request Withdrawal
                    </a>
                </div>

                <div class="glass-table-container table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Requested Date</th>
                                <th>Amount</th>
                                <th>Source Wallet</th>
                                <th>Payout Details</th>
                                <th>Status</th>
                                <th>Processed Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while($row = $result->fetch_assoc()): 
                                    $status_class = 'status-page-' . strtolower($row['status']);
                                ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 500;"><?= date('M d, Y', strtotime($row['requested_at'])) ?></div>
                                        <div style="font-size: 0.8rem; color: #64748b;"><?= date('H:i A', strtotime($row['requested_at'])) ?></div>
                                    </td>
                                    <td style="font-weight: 700; color: #fff; font-size: 1.1rem;">
                                        $<?= number_format($row['amount'], 2) ?>
                                    </td>
                                    <td style="text-transform: capitalize; color: #cbd5e1;">
                                        <?= str_replace('_', ' ', $row['wallet_type']) ?>
                                    </td>
                                    <td>
                                        <div style="margin-bottom: 0.25rem;">
                                            <span style="font-size: 0.8rem; color: #94a3b8; text-transform: uppercase;"><?= $row['payment_method'] ?></span>
                                        </div>
                                        <div class="wallet-addr" title="<?= $row['payment_address'] ?>">
                                            <?= substr($row['payment_address'], 0, 20) . (strlen($row['payment_address']) > 20 ? '...' : '') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $status_class ?>">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <td style="color: #94a3b8; font-size: 0.9rem;">
                                        <?= $row['processed_at'] ? date('M d, Y H:i', strtotime($row['processed_at'])) : '-' ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 4rem;">
                                        <div style="color: #64748b; margin-bottom: 1rem; font-size: 3rem;"><i class="fas fa-history"></i></div>
                                        <h3 style="color: #cbd5e1; font-size: 1.25rem;">No withdrawals yet</h3>
                                        <p style="color: #64748b; margin-top: 0.5rem;">Create a request to withdraw your earnings.</p>
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
