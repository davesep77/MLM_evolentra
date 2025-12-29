<?php
session_start();
require 'config_db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

try {
    $stmt = $conn->prepare("SELECT * FROM mlm_users WHERE id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

    $investment = $user_data['investment'] ?? 0;
    $rank = $user_data['current_rank'] ?? 'Associate';
    $refCode = $user_data['referral_code'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM mlm_wallets WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wallet) {
        $wallet = [
            'roi_wallet' => 0.00,
            'referral_wallet' => 0.00,
            'binary_wallet' => 0.00,
            'left_vol' => 0,
            'right_vol' => 0
        ];
    }

    $total_earning = $wallet['roi_wallet'] + $wallet['referral_wallet'] + $wallet['binary_wallet'];

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM mlm_users WHERE sponsor_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $directCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
    $link_general = $base_url . "/register.php?ref=" . $refCode;

} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $error = "Unable to load dashboard data.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Evolentra</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #f1f5f9;
            min-height: 100vh;
        }

        .dashboard-container {
            display: flex;
        }

        .sidebar {
            width: 260px;
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            padding: 2rem 1rem;
            height: 100vh;
            position: sticky;
            top: 0;
        }

        .logo {
            font-size: 1.75rem;
            font-weight: 800;
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 2rem;
            text-align: center;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-menu li {
            margin-bottom: 0.5rem;
        }

        .nav-menu a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }

        .nav-menu a:hover, .nav-menu a.active {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
        }

        .hero-section {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem;
            padding: 2.5rem;
            margin-bottom: 2rem;
        }

        .hero-section h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .hero-section .rank {
            color: #fbbf24;
            font-weight: 600;
            font-size: 1.125rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 1.5rem;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(16, 185, 129, 0.3);
        }

        .stat-label {
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: #ffffff;
        }

        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.2;
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
        }

        .referral-section {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .referral-section h3 {
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }

        .referral-link-box {
            display: flex;
            gap: 1rem;
            align-items: center;
            background: rgba(0, 0, 0, 0.3);
            padding: 1rem;
            border-radius: 0.5rem;
        }

        .referral-link-box input {
            flex: 1;
            background: transparent;
            border: none;
            color: #10b981;
            font-family: monospace;
            font-size: 0.875rem;
            outline: none;
        }

        .copy-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .copy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 40px rgba(16, 185, 129, 0.4);
        }

        .logout-btn {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: all 0.2s;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.2);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo">Evolentra</div>
            <ul class="nav-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="team.php"><i class="fas fa-users"></i> My Team</a></li>
                <li><a href="genealogy.php"><i class="fas fa-sitemap"></i> Binary Tree</a></li>
                <li><a href="income_report.php"><i class="fas fa-chart-line"></i> Income Report</a></li>
                <li><a href="withdraw.php"><i class="fas fa-money-bill-wave"></i> Withdraw</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
            </ul>
            <div style="position: absolute; bottom: 2rem; left: 1rem; right: 1rem;">
                <a href="logout.php" class="logout-btn" style="width: 100%; text-align: center; display: block;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </aside>

        <main class="main-content">
            <div class="hero-section">
                <h1>Welcome, <?= htmlspecialchars($username) ?></h1>
                <p class="rank">Current Rank: <?= htmlspecialchars($rank) ?></p>
            </div>

            <div class="stats-grid">
                <div class="stat-card" style="position: relative;">
                    <div class="stat-label">ROI Wallet</div>
                    <div class="stat-value">$<?= number_format($wallet['roi_wallet'], 2) ?></div>
                    <i class="fas fa-seedling stat-icon" style="color: #10b981;"></i>
                </div>

                <div class="stat-card" style="position: relative;">
                    <div class="stat-label">Referral Wallet</div>
                    <div class="stat-value">$<?= number_format($wallet['referral_wallet'], 2) ?></div>
                    <i class="fas fa-users stat-icon" style="color: #3b82f6;"></i>
                </div>

                <div class="stat-card" style="position: relative;">
                    <div class="stat-label">Binary Wallet</div>
                    <div class="stat-value">$<?= number_format($wallet['binary_wallet'], 2) ?></div>
                    <i class="fas fa-project-diagram stat-icon" style="color: #a78bfa;"></i>
                </div>

                <div class="stat-card" style="position: relative;">
                    <div class="stat-label">Total Earnings</div>
                    <div class="stat-value">$<?= number_format($total_earning, 2) ?></div>
                    <i class="fas fa-wallet stat-icon" style="color: #fbbf24;"></i>
                </div>

                <div class="stat-card" style="position: relative;">
                    <div class="stat-label">Active Capital</div>
                    <div class="stat-value">$<?= number_format($investment, 2) ?></div>
                    <i class="fas fa-shield-alt stat-icon" style="color: #f43f5e;"></i>
                </div>

                <div class="stat-card" style="position: relative;">
                    <div class="stat-label">Direct Referrals</div>
                    <div class="stat-value"><?= $directCount ?></div>
                    <i class="fas fa-user-friends stat-icon" style="color: #34d399;"></i>
                </div>
            </div>

            <div class="referral-section">
                <h3>Your Referral Link</h3>
                <div class="referral-link-box">
                    <input type="text" id="refLink" value="<?= htmlspecialchars($link_general) ?>" readonly>
                    <button class="copy-btn" onclick="copyLink()">
                        <i class="fas fa-copy"></i> Copy Link
                    </button>
                </div>
                <p style="margin-top: 1rem; color: #64748b; font-size: 0.875rem;">
                    Share this link to invite new members and earn 9% referral commission on their investments.
                </p>
            </div>

            <div class="referral-section">
                <h3>Network Overview</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <div style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.5rem;">Left Leg Volume</div>
                        <div style="font-size: 1.5rem; font-weight: 700;">$<?= number_format($wallet['left_vol'], 2) ?></div>
                        <div style="width: 100%; height: 8px; background: rgba(0,0,0,0.2); border-radius: 1rem; margin-top: 0.5rem; overflow: hidden;">
                            <div style="height: 100%; width: 80%; background: #3b82f6; border-radius: 1rem;"></div>
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 0.875rem; color: #64748b; margin-bottom: 0.5rem;">Right Leg Volume</div>
                        <div style="font-size: 1.5rem; font-weight: 700;">$<?= number_format($wallet['right_vol'], 2) ?></div>
                        <div style="width: 100%; height: 8px; background: rgba(0,0,0,0.2); border-radius: 1rem; margin-top: 0.5rem; overflow: hidden;">
                            <div style="height: 100%; width: 45%; background: #10b981; border-radius: 1rem;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function copyLink() {
            const input = document.getElementById('refLink');
            input.select();
            input.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(input.value).then(() => {
                alert('Referral link copied to clipboard!');
            });
        }
    </script>
</body>
</html>
