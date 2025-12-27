<?php
require 'config_db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$user_query = $conn->query("SELECT * FROM mlm_users WHERE id = $user_id");
$user = $user_query->fetch_assoc();

// Fetch wallet data
$wallet_query = $conn->query("SELECT * FROM mlm_wallets WHERE id = $user_id");
$wallet = $wallet_query->fetch_assoc();

// Count team members
$team_count_query = $conn->query("SELECT COUNT(*) as count FROM mlm_users WHERE sponsor_id = $user_id");
$team_count = $team_count_query->fetch_assoc()['count'];

// Calculate total team volume
$total_volume = ($wallet['left_vol'] ?? 0) + ($wallet['right_vol'] ?? 0);

// Determine current rank
$current_rank = 'Member';
$next_rank = 'Bronze';
$progress = 0;

if ($user['investment'] >= 1000 && $team_count >= 5) {
    $current_rank = 'Bronze';
    $next_rank = 'Silver';
    $progress = min(100, ($user['investment'] / 5000) * 100);
} 
if ($user['investment'] >= 5000 && $team_count >= 15) {
    $current_rank = 'Silver';
    $next_rank = 'Gold';
    $progress = min(100, ($user['investment'] / 15000) * 100);
}
if ($user['investment'] >= 15000 && $team_count >= 50) {
    $current_rank = 'Gold';
    $next_rank = 'Platinum';
    $progress = min(100, ($user['investment'] / 50000) * 100);
}
if ($user['investment'] >= 50000 && $team_count >= 100) {
    $current_rank = 'Platinum';
    $next_rank = 'Diamond';
    $progress = 100;
}

// Franchise tiers
$franchises = [
    'bronze' => [
        'name' => 'Bronze Franchise',
        'icon' => '🥉',
        'color' => 'linear-gradient(135deg, #cd7f32 0%, #e8a87c 100%)',
        'investment' => 1000,
        'requirements' => [
            'Minimum Investment: $1,000',
            'Direct Referrals: 5+',
            'Monthly Volume: $2,000',
            'Active Status Required'
        ],
        'benefits' => [
            'Extra 2% ROI Bonus',
            'Priority Support',
            'Bronze Badge',
            'Marketing Materials',
            'Training Access'
        ]
    ],
    'silver' => [
        'name' => 'Silver Franchise',
        'icon' => '🥈',
        'color' => 'linear-gradient(135deg, #c0c0c0 0%, #e8e8e8 100%)',
        'investment' => 5000,
        'requirements' => [
            'Minimum Investment: $5,000',
            'Direct Referrals: 15+',
            'Monthly Volume: $10,000',
            'Bronze Rank Achieved'
        ],
        'benefits' => [
            'Extra 3% ROI Bonus',
            'VIP Support',
            'Silver Badge',
            'Advanced Training',
            'Team Building Tools',
            'Monthly Webinars'
        ]
    ],
    'gold' => [
        'name' => 'Gold Franchise',
        'icon' => '🥇',
        'color' => 'linear-gradient(135deg, #ffd700 0%, #ffed4e 100%)',
        'investment' => 15000,
        'requirements' => [
            'Minimum Investment: $15,000',
            'Direct Referrals: 50+',
            'Monthly Volume: $50,000',
            'Silver Rank Achieved'
        ],
        'benefits' => [
            'Extra 5% ROI Bonus',
            'Dedicated Account Manager',
            'Gold Badge',
            'Leadership Training',
            'Conference Invitations',
            'Profit Sharing Program'
        ]
    ],
    'platinum' => [
        'name' => 'Platinum Franchise',
        'icon' => '💎',
        'color' => 'linear-gradient(135deg, #e5e4e2 0%, #b9f2ff 100%)',
        'investment' => 50000,
        'requirements' => [
            'Minimum Investment: $50,000',
            'Direct Referrals: 100+',
            'Monthly Volume: $200,000',
            'Gold Rank Achieved'
        ],
        'benefits' => [
            'Extra 7% ROI Bonus',
            'Executive Support Team',
            'Platinum Badge',
            'Global Events Access',
            'Revenue Share',
            'Company Equity Options',
            'Luxury Rewards Program'
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Franchise Opportunities - Evolentra</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #4c1d95 100%);
            min-height: 100vh;
            color: white;
            margin: 0;
            padding: 0;
        }

        .main-wrapper {
            margin-left: 280px;
            padding: 2rem;
            min-height: 100vh;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #a78bfa 0%, #ec4899 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .current-status {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem;
            padding: 2rem;
            margin-bottom: 3rem;
            backdrop-filter: blur(20px);
        }

        .status-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .status-item {
            text-align: center;
        }

        .status-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
        }

        .status-label {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .rank-progress {
            margin-top: 1.5rem;
        }

        .rank-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .progress-bar {
            height: 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #a78bfa 0%, #ec4899 100%);
            transition: width 0.3s ease;
        }

        .franchise-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .franchise-card {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem;
            padding: 2rem;
            backdrop-filter: blur(20px);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .franchise-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 50%, rgba(167, 139, 250, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .franchise-card:hover {
            transform: translateY(-10px);
            border-color: rgba(167, 139, 250, 0.5);
            box-shadow: 0 20px 60px rgba(167, 139, 250, 0.3);
        }

        .franchise-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .franchise-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .franchise-name {
            font-size: 1.75rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .franchise-investment {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #a78bfa 0%, #ec4899 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .feature-list {
            list-style: none;
            margin-bottom: 1.5rem;
        }

        .feature-list li {
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .feature-list li:last-child {
            border-bottom: none;
        }

        .feature-list i {
            color: #10b981;
            font-size: 1rem;
        }

        .apply-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #a78bfa 0%, #ec4899 100%);
            border: none;
            border-radius: 0.75rem;
            color: white;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
        }

        .apply-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(167, 139, 250, 0.5);
        }

        .apply-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        @media (max-width: 1024px) {
            .franchise-grid {
                grid-template-columns: 1fr;
            }
            .status-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .main-wrapper {
                margin-left: 0;
                padding: 1rem;
            }
            .status-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar_nav.php'; ?>
    
    <div class="main-wrapper">
        <div class="page-header">
            <h1 class="page-title">Franchise Opportunities</h1>
            <p class="page-subtitle">Unlock exclusive benefits and grow your empire</p>
        </div>

        <div class="current-status">
            <h3 style="margin-bottom: 1.5rem; font-size: 1.25rem;">Your Current Status</h3>
            
            <div class="status-grid">
                <div class="status-item">
                    <div class="status-value"><?= $current_rank ?></div>
                    <div class="status-label">Current Rank</div>
                </div>
                <div class="status-item">
                    <div class="status-value">$<?= number_format($user['investment'], 0) ?></div>
                    <div class="status-label">Total Investment</div>
                </div>
                <div class="status-item">
                    <div class="status-value"><?= $team_count ?></div>
                    <div class="status-label">Team Members</div>
                </div>
                <div class="status-item">
                    <div class="status-value">$<?= number_format($total_volume, 0) ?></div>
                    <div class="status-label">Team Volume</div>
                </div>
            </div>

            <div class="rank-progress">
                <div class="rank-info">
                    <span style="font-weight: 600;">Progress to <?= $next_rank ?></span>
                    <span style="color: #a78bfa;"><?= round($progress) ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $progress ?>%;"></div>
                </div>
            </div>
        </div>

        <div class="franchise-grid">
            <?php foreach ($franchises as $key => $franchise): ?>
            <div class="franchise-card">
                <div class="franchise-header">
                    <div class="franchise-icon"><?= $franchise['icon'] ?></div>
                    <div class="franchise-name"><?= $franchise['name'] ?></div>
                    <div class="franchise-investment">$<?= number_format($franchise['investment']) ?>+</div>
                </div>

                <div class="section-title">Requirements</div>
                <ul class="feature-list">
                    <?php foreach ($franchise['requirements'] as $req): ?>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span><?= $req ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <div class="section-title">Benefits</div>
                <ul class="feature-list">
                    <?php foreach ($franchise['benefits'] as $benefit): ?>
                    <li>
                        <i class="fas fa-star"></i>
                        <span><?= $benefit ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <button class="apply-btn" 
                        <?= $user['investment'] < $franchise['investment'] ? 'disabled' : '' ?>
                        onclick="applyFranchise('<?= $franchise['name'] ?>')">
                    <?= $user['investment'] >= $franchise['investment'] ? 'Apply Now' : 'Locked' ?>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function applyFranchise(name) {
            alert('Franchise application for ' + name + ' will be processed by our team. You will be contacted within 24 hours.');
        }
    </script>
</body>
</html>
