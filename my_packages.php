<?php
require 'config_db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch User Deposits / Packages
// Assuming 'DEPOSIT' type in mlm_transactions represents a package purchase
$query = "SELECT * FROM mlm_transactions WHERE user_id = $user_id AND type = 'DEPOSIT' ORDER BY created_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Packages - Evolentra</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .page-header {
            margin-bottom: 2rem;
        }

        .packages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
        }

        .package-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.7) 0%, rgba(15, 23, 42, 0.8) 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1.5rem;
            padding: 2rem;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .package-card:hover {
            transform: translateY(-5px);
            border-color: rgba(139, 92, 246, 0.3);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .pkg-badge {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-active { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.2); }
        .status-expired { background: rgba(148, 163, 184, 0.15); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.2); }

        .pkg-name {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: linear-gradient(90deg, #fff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .pkg-amount {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
        }

        .pkg-amount span {
            font-size: 1rem;
            color: #94a3b8;
            font-weight: 500;
        }

        .pkg-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .pkg-detail-item {
            display: flex;
            flex-direction: column;
        }

        .pkg-label {
            font-size: 0.8rem;
            color: #94a3b8;
            margin-bottom: 0.25rem;
        }

        .pkg-value {
            font-size: 0.95rem;
            color: #e2e8f0;
            font-weight: 600;
        }

        .pkg-icon {
            width: 50px;
            height: 50px;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .icon-root { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; }
        .icon-rise { background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%); color: white; }
        .icon-terra { background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%); color: white; }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar_nav.php'; ?>
        
        <div class="main-content">
            <div class="container">
                <div class="page-header">
                    <h2 style="font-size: 2rem; font-weight: 800; margin-bottom: 0.5rem;">My Packages</h2>
                    <p style="color: #94a3b8;">Manage and track your active investment plans</p>
                </div>

                <?php if ($result->num_rows > 0): ?>
                    <div class="packages-grid">
                        <?php 
                        while ($pkg = $result->fetch_assoc()): 
                            $amount = $pkg['amount'];
                            
                            // Determine Plan (Logic from invest.php)
                            // ROOT: $50 - $5,000 (1.2%)
                            // RISE: $5,001 - $25,000 (1.3%)
                            // TERRA: $25,000+ (1.5%)
                            $plan_name = 'ROOT';
                            $roi_rate = '1.2%';
                            $icon_class = 'icon-root';
                            
                            if ($amount > 5000 && $amount <= 25000) {
                                $plan_name = 'RISE';
                                $roi_rate = '1.3%';
                                $icon_class = 'icon-rise';
                            } elseif ($amount > 25000) {
                                $plan_name = 'TERRA';
                                $roi_rate = '1.5%';
                                $icon_class = 'icon-terra';
                            }
                            
                            // Calculate simple estimated ROI per day
                            $roi_daily = $amount * ((float)$roi_rate / 100);

                            // Fetch global ROI progress for this user
                            // Note: Since the system aggregates investment, we use the user's global counter
                            $roi_days_paid = 0;
                            $user_progress = $conn->query("SELECT roi_days_paid FROM mlm_users WHERE id=$user_id")->fetch_assoc();
                            if($user_progress) {
                                $roi_days_paid = $user_progress['roi_days_paid'];
                            }
                            $remaining = max(0, 250 - $roi_days_paid);
                        ?>
                        <div class="package-card">
                            <div class="pkg-badge status-active">Active</div>
                            
                            <div class="pkg-icon <?= $icon_class ?>">
                                <i class="fas fa-cube"></i>
                            </div>

                            <div class="pkg-name"><?= $plan_name ?> Plan</div>
                            <div class="pkg-amount">$<?= number_format($amount, 2) ?> <span>USD</span></div>
                            
                            <div style="font-size: 0.9rem; color: #cbd5e1; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-chart-line" style="color: #10b981;"></i> 
                                Earns <strong><?= $roi_rate ?></strong> Daily ($<?= number_format($roi_daily, 2) ?>)
                            </div>

                            <div class="pkg-details">
                                <div class="pkg-detail-item">
                                    <span class="pkg-label">Purchase Date</span>
                                    <span class="pkg-value"><?= date('M d, Y', strtotime($pkg['created_at'])) ?></span>
                                </div>
                                <div class="pkg-detail-item">
                                    <span class="pkg-label">Duration Limit</span>
                                    <span class="pkg-value">250 Days</span>
                                </div>
                                <div class="pkg-detail-item">
                                    <span class="pkg-label">Progress</span>
                                    <span class="pkg-value" style="color: #34d399;"><?= $roi_days_paid ?> / 250 Days Paid</span>
                                </div>
                                <div class="pkg-detail-item">
                                    <span class="pkg-label">Ending In</span>
                                    <span class="pkg-value"><?= $remaining ?> Days</span>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 4rem; background: rgba(255,255,255,0.02); border-radius: 1.5rem; border: 1px dashed rgba(255,255,255,0.1);">
                        <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.05); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                            <i class="fas fa-box-open" style="font-size: 2rem; color: #64748b;"></i>
                        </div>
                        <h3 style="margin-bottom: 0.5rem; font-size: 1.25rem;">No Active Packages</h3>
                        <p style="color: #94a3b8; margin-bottom: 2rem;">You haven't purchased any investment packages yet.</p>
                        <a href="package.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle" style="margin-right: 0.5rem;"></i> Buy New Package
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
