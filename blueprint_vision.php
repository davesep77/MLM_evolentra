<?php
require 'config_db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evolentra Strategy Blueprint - Visual Mapping</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-deep: #0f172a;
            --glass: rgba(30, 41, 59, 0.7);
            --accent: #10b981;
            --accent-purple: #a78bfa;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-deep);
            color: #f8fafc;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        .blueprint-wrapper {
            margin-left: 280px;
            padding: 3rem;
            max-width: 1200px;
        }

        .header-section {
            text-align: center;
            margin-bottom: 4rem;
        }

        .badge-strategy {
            display: inline-block;
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent);
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #fff 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-subtext {
            font-size: 1.2rem;
            color: #94a3b8;
            max-width: 700px;
            margin: 0 auto;
        }

        /* Roadmap Grid */
        .vision-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        .vision-block {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 2rem;
            padding: 2.5rem;
            display: flex;
            gap: 2.5rem;
            align-items: center;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s;
        }
        .vision-block:hover { transform: scale(1.01); border-color: rgba(167, 139, 250, 0.3); }

        .block-number {
            font-size: 5rem;
            font-weight: 900;
            opacity: 0.05;
            position: absolute;
            left: 2rem;
            top: 2rem;
        }

        .icon-box {
            width: 80px;
            height: 80px;
            border-radius: 1.5rem;
            background: linear-gradient(135deg, var(--accent-purple) 0%, #ec4899 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            box-shadow: 0 10px 40px rgba(167, 139, 250, 0.3);
            flex-shrink: 0;
        }

        .block-content h2 {
            font-size: 1.75rem;
            margin-bottom: 1rem;
        }

        .block-content p {
            color: #94a3b8;
            line-height: 1.7;
            margin-bottom: 1.5rem;
        }

        .map-tag {
            background: rgba(255, 255, 255, 0.05);
            padding: 0.4rem 0.8rem;
            border-radius: 0.5rem;
            font-size: 0.8rem;
            color: #cbd5e1;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
        }
        .map-tag b { color: var(--accent-purple); }

        .feature-check {
            list-style: none;
            padding: 0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }
        .feature-check li {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #cbd5e1;
        }
        .feature-check i { color: var(--accent); }

        .live-btn {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent);
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            margin-top: 1.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgba(16, 185, 129, 0.2);
            transition: all 0.3s;
        }
        .live-btn:hover { background: var(--accent); color: white; }

        @media (max-width: 1024px) {
            .blueprint-wrapper { margin-left: 0; padding: 1.5rem; }
            .vision-block { flex-direction: column; text-align: center; }
            .icon-box { margin: 0 auto; }
            .feature-check { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'sidebar_nav.php'; ?>

    <div class="blueprint-wrapper">
        <div class="header-section">
            <div class="badge-strategy">Strategic Alignment 2025</div>
            <h1>The Master Implementation</h1>
            <p class="header-subtext">Mapping the Evolentra Architectural Blueprint to the live production environment.</p>
        </div>

        <div class="vision-grid">
            <!-- 1. VISUAL IDENTITY -->
            <div class="vision-block">
                <span class="block-number">01</span>
                <div class="icon-box"><i class="fas fa-palette"></i></div>
                <div class="block-content">
                    <div class="map-tag"><i class="fas fa-file-alt"></i> Section 1.1: <b>Digital Comfort Paradigm</b></div>
                    <h2>Visual & UI Infrastructure</h2>
                    <p>We've deployed the "Digital Comfort" aesthetic across all pages. This includes high-contrast dark mode, kinetic gradients, and crystal-reflective gemstone rank assets.</p>
                    <ul class="feature-check">
                        <li><i class="fas fa-check-circle"></i> Dark Mode Standard</li>
                        <li><i class="fas fa-check-circle"></i> Kinetic Typography</li>
                        <li><i class="fas fa-check-circle"></i> Crystal Rank SVGs</li>
                        <li><i class="fas fa-check-circle"></i> Organic Smooth Grids</li>
                    </ul>
                    <a href="about_ranks.php" class="live-btn">View Experience &rarr;</a>
                </div>
            </div>

            <!-- 2. COMPENSATION ENGINE -->
            <div class="vision-block">
                <span class="block-number">02</span>
                <div class="icon-box"><i class="fas fa-cogs"></i></div>
                <div class="block-content">
                    <div class="map-tag"><i class="fas fa-file-alt"></i> Section 2.1: <b>Algorithmic Engine</b></div>
                    <h2>The Compensation Engine</h2>
                    <p>The logic from the blueprint is now live in `lib/Compensation.php`. Every investment triggers multi-tier payouts synchronized with the network hierarchy.</p>
                    <ul class="feature-check">
                        <li><i class="fas fa-check-circle"></i> 10% Binary Matching</li>
                        <li><i class="fas fa-check-circle"></i> 8-9% Referral Bonus</li>
                        <li><i class="fas fa-check-circle"></i> 1.2%-1.5% Daily ROI</li>
                        <li><i class="fas fa-check-circle"></i> 9-Rank Gemstone Logic</li>
                    </ul>
                    <a href="package.php" class="live-btn">View Packages &rarr;</a>
                </div>
            </div>

            <!-- 3. CONTROL ROOM -->
            <div class="vision-block">
                <span class="block-number">03</span>
                <div class="icon-box"><i class="fas fa-microchip"></i></div>
                <div class="block-content">
                    <div class="map-tag"><i class="fas fa-file-alt"></i> Section 6.1: <b>Dashboard Control Room</b></div>
                    <h2>Business Intelligence Center</h2>
                    <p>Your dashboard is no longer just reporting—it's the "Control Room". With AI-driven strategy insights and predictive ROI forecasting for the next 30 days.</p>
                    <ul class="feature-check">
                        <li><i class="fas fa-check-circle"></i> Predictive ROI Analysis</li>
                        <li><i class="fas fa-check-circle"></i> AI Strategy Advisor</li>
                        <li><i class="fas fa-check-circle"></i> Network Health Radar</li>
                        <li><i class="fas fa-check-circle"></i> Live Commission Tracker</li>
                    </ul>
                    <a href="dashboard.php" class="live-btn">Enter Control Room &rarr;</a>
                </div>
            </div>

            <!-- 4. FINANCIAL INFRASTRUCTURE -->
            <div class="vision-block">
                <span class="block-number">04</span>
                <div class="icon-box"><i class="fas fa-vault"></i></div>
                <div class="block-content">
                    <h2 class="vision-title">Binance Global Liquidity</h2>
                    <p class="vision-text">Direct integration with the Binance ecosystem for borderless capital flow.</p>
                    <ul class="feature-check">
                        <li><i class="fas fa-check-circle"></i> USDT-BEP20 (Smart Chain) Support</li>
                        <li><i class="fas fa-check-circle"></i> USDT-TRC20 (TRON) Integration</li>
                        <li><i class="fas fa-check-circle"></i> Direct Master Wallet Sweeping</li>
                        <li><i class="fas fa-check-circle"></i> Automated Withdrawal Routing</li>
                    </ul>
                    <div class="live-tag">
                        <a href="withdraw.php" class="live-btn">Live Gateway <i class="fas fa-external-link-alt"></i></a>
                    </div>
                </div>
            </div>

            <!-- 5. GAMIFICATION -->
            <div class="vision-block">
                <span class="block-number">05</span>
                <div class="icon-box"><i class="fas fa-gamepad"></i></div>
                <div class="block-content">
                    <div class="map-tag"><i class="fas fa-file-alt"></i> Section 6.2: <b>Gamification Strategy</b></div>
                    <h2>Behavioral Incentives</h2>
                    <p>Gamifying achievement with the gem-based rank system. Progress bars glow and ranks unlock visually rewarding milestones for the distributors.</p>
                    <ul class="feature-check">
                        <li><i class="fas fa-check-circle"></i> Gemstone Milestone System</li>
                        <li><i class="fas fa-check-circle"></i> Rank Achievements Badge</li>
                        <li><i class="fas fa-check-circle"></i> Career Progress Tracker</li>
                        <li><i class="fas fa-check-circle"></i> AI Personalization</li>
                    </ul>
                    <a href="genealogy.php" class="live-btn">Explore Network &rarr;</a>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 5rem; padding-bottom: 5rem;">
            <p class="text-slate-500 text-sm">PRODUCED BY ANTIGRAVITY FOR EVOLENTRA ECOSYSTEM | 2025 COMPLIANT</p>
        </div>
    </div>
</body>
</html>
