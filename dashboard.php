<?php
require 'config_db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// --- Data Fetching ---

// 1. User Info & Investment
$user_query = $conn->query("SELECT * FROM mlm_users WHERE id = $user_id");
$user_data = $user_query->fetch_assoc();
$investment = $user_data['investment'] ?? 0;
$rank = $user_data['current_rank'] ?? 'Associate';
$rank_achieved_at = $user_data['rank_achieved_at'] ?? null;
 

// Fetch Recent Transactions for Live Tracker
$trx_query = $conn->query("SELECT id, type, amount, status, created_at FROM mlm_transactions WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 20");


// 2. Wallets
$w_query = $conn->query("SELECT * FROM mlm_wallets WHERE user_id=$user_id");
if ($w_query && $w_query->num_rows > 0) {
    $w = $w_query->fetch_assoc();
} else {
    $w = ['roi_wallet' => 0.00, 'referral_wallet' => 0.00, 'binary_wallet' => 0.00, 'left_vol' => 0, 'right_vol' => 0];
}

// 3. Totals Calculation
// Total Earnings = Sum of all wallets (Simplification) or sum of all transactions
$total_earning = $w['roi_wallet'] + $w['referral_wallet'] + $w['binary_wallet'];

// Total Withdrawals
$withdraw_query = $conn->query("SELECT SUM(amount) as total FROM mlm_transactions WHERE user_id=$user_id AND type='withdrawal'");
$withdraw_data = $withdraw_query->fetch_assoc();
$total_withdraw = $withdraw_data['total'] ?? 0.00;

// Today's Earning
$today = date('Y-m-d');
$today_earning_query = $conn->query("SELECT SUM(amount) as total FROM mlm_transactions WHERE user_id=$user_id AND DATE(created_at) = '$today' AND type IN ('roi', 'referral', 'binary')");
$today_earning_data = $today_earning_query->fetch_assoc();
$today_earning = $today_earning_data['total'] ?? 0.00;

// Today's Sales (Assuming this means new investments in downline or direct sales - using direct referrals investment for simplicity)
// OR it could be personal sales. Let's assume personal sales (investments made by user today) for now, or maybe referral volume.
// Let's stick to simple "Direct Sales Today"
$today_sales_query = $conn->query("SELECT SUM(investment) as total FROM mlm_users WHERE sponsor_id = '$username' AND DATE(created_at) = '$today'");
$today_sales_data = $today_sales_query->fetch_assoc();
$today_sales = $today_sales_data['total'] ?? 0.00;


// 4. Referral Links (using referral code)
require_once 'lib/ReferralEngine.php';
$refEngine = new ReferralEngine($conn);

// Ensure user has referral code
if (empty($user_data['referral_code'])) {
    $refCode = $refEngine->generateReferralCode($user_id, $username);
    $conn->query("UPDATE mlm_users SET referral_code='$refCode' WHERE id=$user_id");
    // Create referral links
    $refEngine->createReferralLinks($user_id, $refCode);
} else {
    $refCode = $user_data['referral_code'];
}

$path = dirname($_SERVER['PHP_SELF']);
if ($path == '/' || $path == '\\') { $path = ''; }
$base_url = "http://" . $_SERVER['HTTP_HOST'] . str_replace('\\', '/', $path) . "/register.php";
$link_general = $base_url . "?ref=" . $refCode;
$link_left = $base_url . "?ref=" . $refCode . "&position=left";
$link_right = $base_url . "?ref=" . $refCode . "&position=right";

// Get referral statistics
$refStats = $refEngine->getReferralStats($user_id);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Evolentra</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> 
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar_nav.php'; ?>
        
        <div class="main-content">
            <div class="container">
                <!-- IMMERSIVE HERO SECTION (Kinetic Typography Style) -->
                <div class="control-room-hero">
                    <div class="hero-content">
                        <div class="welcome-badge">
                            <span class="pulse-dot"></span> SYSTEM LIVE | 2025 PROTOCOL ACTIVE
                        </div>
                        <h1 class="kinetic-text">Welcome, <?= htmlspecialchars($username) ?></h1>
                        <p class="hero-subtext">Optimizing your global network from the <span class="highlight">Evolentra Control Room</span>.</p>
                        
                        <div class="hero-stats-row">
                            <div class="hero-stat-item">
                                <span class="stat-label">Network Status</span>
                                <span class="stat-status-active">SYNCHRONIZED</span>
                            </div>
                            <div class="hero-stat-item">
                                <span class="stat-label">Current Rank</span>
                                <span class="stat-rank"><?= $rank ?></span>
                            </div>
                            <div class="hero-stat-item">
                                <span class="stat-label">System Mode</span>
                                <span class="stat-mode">COMPOUND GROWTH</span>
                            </div>
                        </div>
                    </div>
                    <div class="hero-visual">
                        <div class="data-orb"></div>
                        <img src="https://cdn-icons-png.flaticon.com/512/4712/4712038.png" alt="AI Agent" class="hero-robot">
                    </div>
                </div>

                <!-- PREDICTIVE ANALYTICS & AI INSIGHTS -->
                <div class="grid-layout-3">
                    <!-- ROI FORECAST (Predictive Analytics) -->
                    <div class="glass-container predictive-card">
                        <div class="card-head">
                            <i class="fas fa-chart-line text-indigo-400"></i>
                            <span>30-Day ROI Forecast</span>
                        </div>
                        <div class="prediction-value">$<?= number_format($investment * 0.012 * 30, 2) ?></div>
                        <p class="prediction-note">Estimated based on your daily 1.2% ROOT logic.</p>
                        <div class="progress-container">
                            <div class="progress-bar" style="width: 65%;"></div>
                        </div>
                        <div class="flex-between text-xs mt-2">
                            <span>Current Plan: ROOT</span>
                            <span class="text-indigo-400">Upgrade for +0.3%</span>
                        </div>
                    </div>

                    <!-- AI STRATEGY (Optimization) -->
                    <div class="glass-container insight-card">
                        <div class="card-head">
                            <i class="fas fa-brain text-emerald-400"></i>
                            <span>AI Strategy Insight</span>
                        </div>
                        <div class="insight-msg">
                            <?php if($w['left_vol'] > $w['right_vol']): ?>
                                "Focus growth on the <span class='text-emerald-400'>Right Leg</span> to trigger a $<?= number_format(($w['left_vol'] - $w['right_vol']) * 0.1, 2) ?> binary match."
                            <?php else: ?>
                                "Focus growth on the <span class='text-emerald-400'>Left Leg</span> to trigger a $<?= number_format(($w['right_vol'] - $w['left_vol']) * 0.1, 2) ?> binary match."
                            <?php endif; ?>
                        </div>
                        <div class="insight-cta">
                            <a href="genealogy.php" class="text-link">View Weak Point &rarr;</a>
                        </div>
                    </div>

                    <!-- GLOBAL REVENUE SHARE -->
                    <div class="glass-container revenue-card">
                        <div class="card-head">
                            <i class="fas fa-globe text-pink-400"></i>
                            <span>Black Olive Star Pool</span>
                        </div>
                        <div class="pool-status">QUALIFICATION: 35%</div>
                        <div class="mini-progress">
                            <div class="mini-bar" style="width: 35%;"></div>
                        </div>
                        <p class="text-xs text-slate-400 mt-2">Earn a share of global company revenue at high ranks.</p>
                    </div>
                </div>

                <!-- CORE FINANCIALS -->
                <div class="grid-layout-2 mt-6">
                    <!-- WALLETS -->
                    <div class="glass-container">
                        <h3 class="container-title">Capital Overview</h3>
                        <div class="wallet-visual-grid">
                            <div class="wallet-box" style="--accent: #10b981;">
                                <div class="w-label">ROI Wallet</div>
                                <div class="w-val">$<?= number_format($w['roi_wallet'], 2) ?></div>
                                <div class="w-icon"><i class="fas fa-seedling"></i></div>
                            </div>
                            <div class="wallet-box" style="--accent: #3b82f6;">
                                <div class="w-label">Referral Bonus</div>
                                <div class="w-val">$<?= number_format($w['referral_wallet'], 2) ?></div>
                                <div class="w-icon"><i class="fas fa-users"></i></div>
                            </div>
                            <div class="wallet-box" style="--accent: #a78bfa;">
                                <div class="w-label">Binary Match</div>
                                <div class="w-val">$<?= number_format($w['binary_wallet'], 2) ?></div>
                                <div class="w-icon"><i class="fas fa-project-diagram"></i></div>
                            </div>
                            <div class="wallet-box" style="--accent: #f43f5e;">
                                <div class="w-label">Active Capital</div>
                                <div class="w-val">$<?= number_format($investment, 2) ?></div>
                                <div class="w-icon"><i class="fas fa-shield-alt"></i></div>
                            </div>
                        </div>
                    </div>

                    <!-- NETWORK RADAR -->
                    <div class="glass-container">
                        <h3 class="container-title">Network Synergy</h3>
                        <div class="radar-group">
                            <div class="radar-item">
                                <div class="flex-between mb-1">
                                    <span class="text-slate-400">Left Leg Volume</span>
                                    <span class="text-white font-bold">$<?= number_format($w['left_vol'], 2) ?></span>
                                </div>
                                <div class="radar-bar-bg"><div class="radar-bar" style="width: 80%; background: #3b82f6;"></div></div>
                            </div>
                            <div class="radar-item mt-4">
                                <div class="flex-between mb-1">
                                    <span class="text-slate-400">Right Leg Volume</span>
                                    <span class="text-white font-bold">$<?= number_format($w['right_vol'], 2) ?></span>
                                </div>
                                <div class="radar-bar-bg"><div class="radar-bar" style="width: 45%; background: #10b981;"></div></div>
                            </div>
                            <div class="radar-footer mt-6 flex-between">
                                <div class="text-xs">
                                    <div class="text-slate-500">Total Network PV</div>
                                    <div class="text-lg font-bold text-white"><?= number_format($w['left_vol'] + $w['right_vol']) ?></div>
                                </div>
                                <a href="genealogy.php" class="btn-sm-indigo">View Tree</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RECENT TRANSACTIONS (Live Commission Tracker) -->
                <div class="glass-container mt-6">
                    <div class="flex-between mb-4">
                        <h3 class="container-title m-0">Live Commission Tracker</h3>
                        <a href="income_report.php" class="text-xs text-indigo-400">Detailed Report &rarr;</a>
                    </div>
                    <div class="table-responsive">
                        <table class="control-table">
                            <thead>
                                <tr>
                                    <th>TIMESTAMP</th>
                                    <th>TYPE</th>
                                    <th>DESCRIPTION</th>
                                    <th>AMOUNT</th>
                                    <th>STATUS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($trx_query && $trx_query->num_rows > 0): $trx_query->data_seek(0); while($t = $trx_query->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= date('M d, H:i', strtotime($t['created_at'])) ?></td>
                                        <td><span class="type-badge badge-<?= strtolower($t['type']) ?>"><?= $t['type'] ?></span></td>
                                        <td class="text-slate-400"><?= $t['description'] ?? 'System Process' ?></td>
                                        <td class="font-bold text-emerald-400">+$<?= number_format($t['amount'], 2) ?></td>
                                        <td><span class="status-dot dot-success"></span> COMPLETED</td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="5" class="text-center py-6 text-slate-500">Awaiting network activity...</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- CONTROL ROOM OVERRIDES -->
        <style>
            :root {
                --glass-bg: rgba(30, 41, 59, 0.6);
                --glass-border: rgba(255, 255, 255, 0.08);
                --primary-glow: rgba(167, 139, 250, 0.4);
            }

            .main-content { background: #0f172a; min-height: 100vh; color: #f8fafc; padding-top: 1rem; }
            
            /* Control Room Hero */
            .control-room-hero {
                background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
                border: 1px solid var(--glass-border);
                border-radius: 1.5rem;
                padding: 2.5rem;
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 2rem;
                position: relative;
                overflow: hidden;
            }
            .welcome-badge {
                display: inline-flex;
                align-items: center;
                background: rgba(16, 185, 129, 0.1);
                border: 1px solid rgba(16, 185, 129, 0.2);
                color: #10b981;
                font-size: 0.7rem;
                font-weight: 700;
                padding: 0.4rem 0.8rem;
                border-radius: 2rem;
                margin-bottom: 1rem;
                letter-spacing: 0.1rem;
            }
            .kinetic-text { font-size: 3rem; font-weight: 800; margin-bottom: 0.5rem; }
            .hero-subtext { color: #94a3b8; font-size: 1.1rem; }
            .highlight { color: #a78bfa; font-weight: 700; }
            .hero-stats-row { display: flex; gap: 2.5rem; margin-top: 2rem; }
            .hero-stat-item { display: flex; flex-direction: column; }
            .stat-label { font-size: 0.7rem; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 0.3rem; }
            .stat-status-active { color: #10b981; font-weight: 800; font-size: 1rem; }
            .stat-rank { color: #fbbf24; font-weight: 800; font-size: 1rem; }
            .stat-mode { color: #3b82f6; font-weight: 800; font-size: 1rem; }
            
            .hero-robot { height: 180px; filter: drop-shadow(0 0 30px var(--primary-glow)); }

            /* Grid Layouts */
            .grid-layout-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
            .grid-layout-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; }

            /* Container */
            .glass-container {
                background: var(--glass-bg);
                backdrop-filter: blur(20px);
                border: 1px solid var(--glass-border);
                border-radius: 1.25rem;
                padding: 1.5rem;
                transition: all 0.3s ease;
            }
            .glass-container:hover { border-color: rgba(167, 139, 250, 0.3); transform: translateY(-3px); }
            .container-title { font-size: 1.1rem; color: #fff; font-weight: 700; margin-bottom: 1.25rem; }

            /* Specific Cards */
            .card-head { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: #94a3b8; }
            .prediction-value { font-size: 2rem; font-weight: 800; color: #fff; margin-bottom: 0.5rem; }
            .prediction-note { font-size: 0.75rem; color: #64748b; margin-bottom: 1rem; }
            .progress-container { width: 100%; height: 6px; background: rgba(255,255,255,0.05); border-radius: 1rem; }
            .progress-bar { height: 100%; background: linear-gradient(90deg, #6366f1, #a78bfa); border-radius: 1rem; box-shadow: 0 0 10px var(--primary-glow); }
            
            .insight-msg { background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 0.75rem; font-size: 0.95rem; line-height: 1.5; color: #cbd5e1; border-left: 3px solid #10b981; margin-bottom: 1rem; }
            .text-link { color: #a78bfa; font-size: 0.8rem; text-decoration: none; font-weight: 600; }
            
            .pool-status { font-size: 1.25rem; font-weight: 800; color: #f43f5e; margin-bottom: 0.5rem; }
            .mini-bar { height: 6px; background: #f43f5e; border-radius: 1rem; }

            /* Wallet Visual Grid */
            .wallet-visual-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
            .wallet-box {
                background: rgba(0,0,0,0.2);
                border: 1px solid rgba(255,255,255,0.05);
                border-radius: 1rem;
                padding: 1.25rem;
                position: relative;
                overflow: hidden;
            }
            .wallet-box:hover { border-color: var(--accent); }
            .w-label { font-size: 0.7rem; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 0.25rem; }
            .w-val { font-size: 1.25rem; font-weight: 800; color: #fff; }
            .w-icon { position: absolute; right: -5px; bottom: -5px; font-size: 2.5rem; opacity: 0.1; color: var(--accent); transform: rotate(-15deg); }

            /* Radar Bars */
            .radar-bar-bg { width: 100%; height: 8px; background: rgba(0,0,0,0.2); border-radius: 1rem; overflow: hidden; }
            .radar-bar { height: 100%; border-radius: 1rem; }
            .btn-sm-indigo { background: #6366f1; color: #fff; font-size: 0.7rem; font-weight: 700; padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none; }

            /* Table Styles */
            .control-table { width: 100%; text-align: left; font-size: 0.85rem; border-collapse: separate; border-spacing: 0 0.5rem; }
            .control-table th { padding: 1rem; color: #64748b; font-weight: 700; font-size: 0.7rem; text-transform: uppercase; border-bottom: 1px solid var(--glass-border); }
            .control-table td { padding: 1rem; background: rgba(0,0,0,0.1); }
            .control-table tr:hover td { background: rgba(167, 139, 250, 0.05); }
            .control-table tr td:first-child { border-radius: 0.75rem 0 0 0.75rem; }
            .control-table tr td:last-child { border-radius: 0 0.75rem 0.75rem 0; }
            
            .type-badge { font-size: 0.65rem; font-weight: 700; padding: 0.25rem 0.5rem; border-radius: 0.4rem; text-transform: uppercase; }
            .badge-roi { background: rgba(16, 185, 129, 0.1); color: #10b981; }
            .badge-referral { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
            .badge-binary { background: rgba(167, 139, 250, 0.1); color: #a78bfa; }
            .status-dot { height: 8px; width: 8px; border-radius: 50%; display: inline-block; margin-right: 0.5rem; }
            .dot-success { background: #10b981; box-shadow: 0 0 10px #10b981; }
            
            @media (max-width: 1024px) {
                .grid-layout-3, .grid-layout-2 { grid-template-columns: 1fr; }
                .kinetic-text { font-size: 2rem; }
            }
        </style>

            </div>
        </div>
    </div>

    <style>
        .toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 40px rgba(16, 185, 129, 0.4);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            opacity: 0;
            transform: translateY(100px);
            transition: all 0.3s ease;
            z-index: 9999;
            font-weight: 600;
        }
        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }
    </style>

    <script>
        function copyToClipboard(elementId) {
            var copyText = document.getElementById(elementId);
            copyText.select();
            copyText.setSelectionRange(0, 99999); 
            navigator.clipboard.writeText(copyText.value).then(function() {
                showToast('Referral link copied to clipboard!');
            });
        }

        function showToast(message) {
            // Remove existing toast if any
            const existingToast = document.querySelector('.toast');
            if (existingToast) {
                existingToast.remove();
            }

            // Create new toast
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
            document.body.appendChild(toast);

            // Show toast
            setTimeout(() => toast.classList.add('show'), 100);

            // Hide and remove toast after 3 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>
    <script src="js/binance_ws.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const binance = new BinanceIntegration();
            const tickerSyms = ['BTCUSDT', 'ETHUSDT', 'BNBUSDT', 'SOLUSDT', 'XRPUSDT'];
            const tickerStreams = tickerSyms.map(s => `${s.toLowerCase()}@ticker`);

            const tickerContainer = document.querySelector('.crypto-ticker-bar');
            
            // Initialize elements
            tickerSyms.forEach(sym => {
                const el = document.createElement('div');
                el.className = 'dashboard-card';
                el.style.minWidth = '140px';
                el.style.padding = '0.75rem 1rem';
                el.style.marginBottom = '0'; // Override default
                el.id = `ticker-${sym}`;
                el.innerHTML = `
                    <div style="font-size: 0.75rem; color: #94a3b8; font-weight:700; margin-bottom: 0.25rem;">${sym.replace('USDT','')}</div>
                    <div class="price" style="font-size: 1.1rem; font-weight: 700;">Loading...</div>
                    <div class="change" style="font-size: 0.75rem;">--</div>
                `;
                tickerContainer.appendChild(el);
            });

            // Initial REST Fetch - Batched for performance
            (async () => {
                const results = await binance.fetchTickers(tickerSyms);
                if (Array.isArray(results)) {
                    results.forEach(data => {
                        const sym = data.symbol;
                        const el = document.getElementById(`ticker-${sym}`);
                        if (el && data.lastPrice) {
                            const price = parseFloat(data.lastPrice);
                            const change = parseFloat(data.priceChangePercent);
                            
                            if (!isNaN(price)) {
                                let priceStr = price < 1 ? price.toFixed(4) : price.toLocaleString(undefined, {maximumFractionDigits: 2});
                                el.querySelector('.price').innerText = '$' + priceStr;
                                
                                if (!isNaN(change)) {
                                    const chgEl = el.querySelector('.change');
                                    chgEl.innerText = (change > 0 ? '+' : '') + change.toFixed(2) + '%';
                                    chgEl.style.color = change >= 0 ? '#34d399' : '#f87171';
                                }
                            }
                        }
                    });
                }
            })();

            // Subscribe
            binance.subscribe(tickerStreams, (data) => {
                if (data.e === '24hrTicker') {
                    const sym = data.s;
                    const el = document.getElementById(`ticker-${sym}`);
                    if(el && data.c) {
                        const price = parseFloat(data.c);
                        const change = parseFloat(data.P);
                        
                        if (!isNaN(price)) {
                            let priceStr = price < 1 ? price.toFixed(4) : price.toLocaleString(undefined, {maximumFractionDigits: 2});
                            el.querySelector('.price').innerText = '$' + priceStr;
                            
                            if (!isNaN(change)) {
                                const chgEl = el.querySelector('.change');
                                chgEl.innerText = (change > 0 ? '+' : '') + change.toFixed(2) + '%';
                                chgEl.style.color = change >= 0 ? '#34d399' : '#f87171';
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
