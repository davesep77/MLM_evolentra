<?php
require 'config_db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch user's current rank info
$user_query = $conn->query("SELECT current_rank, highest_rank, rank_achieved_at FROM mlm_users WHERE id = $user_id");
$user_data = $user_query->fetch_assoc();
$current_rank = $user_data['current_rank'] ?? 'Associate';
$highest_rank = $user_data['highest_rank'] ?? 'Associate';
$rank_achieved_at = $user_data['rank_achieved_at'];

// Get user's current stats for progress calculation
$wallet_query = $conn->query("SELECT left_vol, right_vol FROM mlm_wallets WHERE user_id = $user_id");
$wallet = $wallet_query->fetch_assoc();
$team_volume = ($wallet['left_vol'] ?? 0) + ($wallet['right_vol'] ?? 0);

$direct_refs_query = $conn->query("SELECT COUNT(*) as count FROM mlm_users WHERE sponsor_id = $user_id");
$direct_referrals = $direct_refs_query->fetch_assoc()['count'];

// Define rank requirements
$RANKS = [
    'Associate' => ['team_volume' => 0, 'direct_referrals' => 0, 'qualifying_legs' => 0, 'leg_rank' => null, 'color' => '#94a3b8', 'icon' => 'fa-user'],
    'Bronze' => ['team_volume' => 500, 'direct_referrals' => 2, 'qualifying_legs' => 0, 'leg_rank' => null, 'color' => '#cd7f32', 'icon' => 'fa-medal'],
    'Silver' => ['team_volume' => 2000, 'direct_referrals' => 5, 'qualifying_legs' => 0, 'leg_rank' => null, 'color' => '#c0c0c0', 'icon' => 'fa-award'],
    'Gold' => ['team_volume' => 5000, 'direct_referrals' => 10, 'qualifying_legs' => 0, 'leg_rank' => null, 'color' => '#ffd700', 'icon' => 'fa-trophy'],
    'Platinum' => ['team_volume' => 15000, 'direct_referrals' => 20, 'qualifying_legs' => 2, 'leg_rank' => 'Gold', 'color' => '#e5e4e2', 'icon' => 'fa-crown'],
    'Ruby' => ['team_volume' => 50000, 'direct_referrals' => 50, 'qualifying_legs' => 3, 'leg_rank' => 'Platinum', 'color' => '#e0115f', 'icon' => 'fa-gem'],
    'Emerald' => ['team_volume' => 150000, 'direct_referrals' => 100, 'qualifying_legs' => 5, 'leg_rank' => 'Ruby', 'color' => '#50c878', 'icon' => 'fa-gem'],
    'Diamond' => ['team_volume' => 500000, 'direct_referrals' => 200, 'qualifying_legs' => 3, 'leg_rank' => 'Emerald', 'color' => '#b9f2ff', 'icon' => 'fa-diamond'],
    'Crown Diamond' => ['team_volume' => 1500000, 'direct_referrals' => 500, 'qualifying_legs' => 5, 'leg_rank' => 'Diamond', 'color' => '#ffd700', 'icon' => 'fa-chess-king']
];

// Get next rank info
$rank_keys = array_keys($RANKS);
$current_rank_index = array_search($current_rank, $rank_keys);
$next_rank = null;
$next_rank_requirements = null;
if ($current_rank_index !== false && $current_rank_index < count($rank_keys) - 1) {
    $next_rank = $rank_keys[$current_rank_index + 1];
    $next_rank_requirements = $RANKS[$next_rank];
}

// Calculate progress to next rank
$team_volume_progress = 0;
$direct_refs_progress = 0;
if ($next_rank_requirements) {
    $team_volume_progress = $next_rank_requirements['team_volume'] > 0 
        ? min(100, ($team_volume / $next_rank_requirements['team_volume']) * 100) 
        : 100;
    $direct_refs_progress = $next_rank_requirements['direct_referrals'] > 0 
        ? min(100, ($direct_referrals / $next_rank_requirements['direct_referrals']) * 100) 
        : 100;
}

// Fetch rank history
$history_query = $conn->query("SELECT * FROM mlm_rank_history WHERE user_id = $user_id ORDER BY achieved_at DESC");

// Fetch leaderboard
$leaderboard_query = $conn->query("SELECT u.id, u.username, u.current_rank, u.rank_achieved_at, w.left_vol, w.right_vol,
                                   (w.left_vol + w.right_vol) as total_volume
                                   FROM mlm_users u
                                   LEFT JOIN mlm_wallets w ON u.id = w.user_id
                                   ORDER BY FIELD(u.current_rank, 'Crown Diamond', 'Diamond', 'Emerald', 'Ruby', 'Platinum', 'Gold', 'Silver', 'Bronze', 'Associate'), 
                                   total_volume DESC
                                   LIMIT 20");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rank System - Evolentra</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Modern Rank Page Overrides */
        .rank-hero {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.9) 100%);
            border-radius: 1.5rem;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            margin-bottom: 2rem;
        }

        .rank-hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at top right, rgba(16, 185, 129, 0.1), transparent 50%);
            z-index: 0;
        }

        .rank-badge-lg {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            color: white;
            box-shadow: 0 0 40px rgba(0,0,0,0.3);
            position: relative;
            z-index: 1;
            border: 3px solid rgba(255,255,255,0.2);
        }

        .rank-hero-content {
            flex: 1;
            z-index: 1;
        }

        .rank-hero-title {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
            margin-bottom: 0.5rem;
        }

        .rank-hero-name {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
            background: linear-gradient(90deg, #fff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .rank-hero-date {
            font-size: 0.95rem;
            color: #10b981;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(16, 185, 129, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 2rem;
        }

        .progress-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1.5rem;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .progress-bar-bg {
            height: 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            margin: 1rem 0 0.5rem;
            overflow: hidden;
            position: relative;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #8b5cf6, #d946ef);
            border-radius: 6px;
            transition: width 1s ease-in-out;
            box-shadow: 0 0 10px rgba(139, 92, 246, 0.5);
        }

        .rank-table-wrapper {
            background: rgba(15, 23, 42, 0.6);
            border-radius: 1.5rem;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .custom-table th {
            background: rgba(0, 0, 0, 0.2);
            padding: 1.2rem;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.05em;
        }

        .custom-table td {
            padding: 1.2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: #e2e8f0;
        }

        .custom-table tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .current-rank-row td {
            background: rgba(16, 185, 129, 0.05) !important;
            border-color: rgba(16, 185, 129, 0.2);
        }

        .rank-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-weight: 600;
            font-size: 0.9rem;
            min-width: 140px;
        }
        
        @media (max-width: 768px) {
            .rank-hero { flex-direction: column; text-align: center; }
            .rank-hero-date { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- FIXED SIDEBAR INCLUDE -->
        <?php include 'sidebar_nav.php'; ?>
        
        <div class="main-content">
            <div class="container">
                <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <div>
                        <h2 style="font-size: 2rem; font-weight: 800; margin-bottom: 0.5rem;">Rank Achievements</h2>
                        <p style="color: #94a3b8;">Track your career progression and rewards</p>
                    </div>
                    <?php if ($next_rank): ?>
                    <div style="text-align: right;">
                        <span style="display: block; font-size: 0.85rem; color: #94a3b8;">Next Target</span>
                        <strong style="color: #a78bfa; font-size: 1.2rem;"><?= $next_rank ?></strong>
                    </div>
                    <?php endif; ?>
                </header>

                <!-- HERO SECTION -->
                <div class="rank-hero">
                    <div class="rank-badge-lg" style="background: linear-gradient(135deg, <?= $RANKS[$current_rank]['color'] ?> 0%, #1e293b 100%); border-color: <?= $RANKS[$current_rank]['color'] ?>;">
                        <i class="fas <?= $RANKS[$current_rank]['icon'] ?>"></i>
                    </div>
                    <div class="rank-hero-content">
                        <div class="rank-hero-title">Current Status</div>
                        <div class="rank-hero-name"><?= $current_rank ?></div>
                        <?php if ($rank_achieved_at): ?>
                            <div class="rank-hero-date">
                                <i class="fas fa-check-circle"></i> 
                                Achieved on <?= date('F d, Y', strtotime($rank_achieved_at)) ?>
                            </div>
                        <?php else: ?>
                            <div class="rank-hero-date" style="background: rgba(255,255,255,0.05); color: #94a3b8;">
                                No Rank Achieved Yet
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="rank-stats" style="display: flex; gap: 2rem; border-left: 1px solid rgba(255,255,255,0.1); padding-left: 2rem;">
                        <div>
                            <div style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 0.25rem;">Team Volume</div>
                            <div style="font-size: 1.5rem; font-weight: 700;">$<?= number_format($team_volume, 2) ?></div>
                        </div>
                        <div>
                            <div style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 0.25rem;">Direct Refs</div>
                            <div style="font-size: 1.5rem; font-weight: 700;"><?= $direct_referrals ?></div>
                        </div>
                    </div>
                </div>

                <!-- PROGRESS SECTION -->
                <?php if ($next_rank): ?>
                <div class="progress-card">
                    <h3 style="margin-bottom: 1.5rem; font-size: 1.25rem;"><i class="fas fa-chart-line" style="color: #d946ef; margin-right: 0.75rem;"></i> Progress to <?= $next_rank ?></h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                        <!-- Volume Progress -->
                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.9rem; font-weight: 500;">
                                <span>Team Volume Needed</span>
                                <span>$<?= number_format($team_volume) ?> / $<?= number_format($next_rank_requirements['team_volume']) ?></span>
                            </div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar-fill" style="width: <?= $team_volume_progress ?>%;"></div>
                            </div>
                            <div style="text-align: right; font-size: 0.85rem; color: <?= $team_volume_progress >= 100 ? '#10b981' : '#94a3b8' ?>;">
                                <?= round($team_volume_progress, 1) ?>% Completed
                            </div>
                        </div>

                        <!-- Referral Progress -->
                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 0.9rem; font-weight: 500;">
                                <span>Direct Referrals Needed</span>
                                <span><?= $direct_referrals ?> / <?= $next_rank_requirements['direct_referrals'] ?></span>
                            </div>
                            <div class="progress-bar-bg">
                                <div class="progress-bar-fill" style="width: <?= $direct_refs_progress ?>%; background: linear-gradient(90deg, #ec4899, #f43f5e);"></div>
                            </div>
                            <div style="text-align: right; font-size: 0.85rem; color: <?= $direct_refs_progress >= 100 ? '#10b981' : '#94a3b8' ?>;">
                                <?= round($direct_refs_progress, 1) ?>% Completed
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- RANKS TABLE -->
                <div style="margin-top: 3rem;">
                    <h3 style="margin-bottom: 1.5rem; font-size: 1.25rem;">Rank Requirements & Rewards</h3>
                    <div class="rank-table-wrapper">
                        <table class="custom-table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="text-align: left;">Rank Level</th>
                                    <th style="text-align: center;">Min. Volume</th>
                                    <th style="text-align: center;">Direct Refs</th>
                                    <th style="text-align: left;">Benefits & Bonus</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($RANKS as $rank_name => $requirements): ?>
                                <tr class="<?= $rank_name === $current_rank ? 'current-rank-row' : '' ?>">
                                    <td>
                                        <div class="rank-pill" style="background: rgba(255,255,255,0.05); color: <?= $requirements['color'] ?>; border: 1px solid <?= $requirements['color'] ?>44;">
                                            <i class="fas <?= $requirements['icon'] ?>"></i> <?= $rank_name ?>
                                        </div>
                                    </td>
                                    <td style="text-align: center; font-family: monospace; font-size: 1rem;">$<?= number_format($requirements['team_volume']) ?></td>
                                    <td style="text-align: center;"><?= $requirements['direct_referrals'] ?></td>
                                    <td style="color: #cbd5e1;">
                                        <?php 
                                        $benefits = [
                                            'Associate' => 'Standard Access',
                                            'Bronze' => '2% Binary Bonus',
                                            'Silver' => '3% Binary Bonus + Badge',
                                            'Gold' => '5% Binary Bonus + Certificate',
                                            'Platinum' => '7% Bonus + Car Fund Eligibility',
                                            'Ruby' => '10% Bonus + Leadership Retreat',
                                            'Emerald' => '12% Bonus + Luxury Watch',
                                            'Diamond' => '15% Bonus + International Trip',
                                            'Crown Diamond' => '20% Bonus + Global Revenue Share'
                                        ];
                                        echo $benefits[$rank_name] ?? '-';
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>
</html>
