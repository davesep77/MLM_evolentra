<?php
require 'config_db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Get user's current investment
$user_query = $conn->query("SELECT username, investment FROM mlm_users WHERE id = $user_id");
$user = $user_query->fetch_assoc();
$current_investment = $user['investment'];

// Get wallet balance
$wallet_query = $conn->query("SELECT roi_wallet FROM mlm_wallets WHERE user_id = $user_id");
$wallet = $wallet_query->fetch_assoc();
$wallet_balance = $wallet['roi_wallet'];

// Handle investment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['invest'])) {
    $package = $_POST['package'];
    $amount = floatval($_POST['amount']);
    
    // Validate package and amount
    $valid = false;
    if ($package == 'ROOT' && $amount >= 50 && $amount <= 5000) {
        $valid = true;
    } elseif ($package == 'RISE' && $amount >= 5001 && $amount <= 25000) {
        $valid = true;
    } elseif ($package == 'TERRA' && $amount >= 25001) {
        $valid = true;
    }
    
    if ($valid) {
        // Update user investment
        $conn->query("UPDATE mlm_users SET investment = investment + $amount WHERE id = $user_id");
        
        // Log transaction
        $conn->query("INSERT INTO mlm_transactions (user_id, type, amount, description) VALUES ($user_id, 'DEPOSIT', $amount, 'Investment - $package Plan')");
        
        // Trigger Referral Commission
        require_once 'lib/Compensation.php';
        $comp = new Compensation($conn);
        
        $sponsor_query = $conn->query("SELECT sponsor_id FROM mlm_users WHERE id=$user_id");
        $sponsor_data = $sponsor_query->fetch_assoc();
        if ($sponsor_data && $sponsor_data['sponsor_id']) {
            $comp->processReferral($amount, $sponsor_data['sponsor_id']);
        }
        
        $message = "Successfully invested $$amount in $package plan!";
        $current_investment += $amount;
    } else {
        $error = "Invalid package or amount. Please check the package requirements.";
    }
}

// Define packages
$packages = [
    'ROOT' => [
        'name' => 'ROOT',
        'min' => 50,
        'max' => 5000,
        'roi' => '1.2%',
        'color' => 'linear-gradient(135deg, #10b981 0%, #34d399 100%)',
        'icon' => '🌱',
        'features' => [
            'Daily ROI: 1.2%',
            'Referral Bonus: 8%',
            'Binary Matching: 10%',
            'Daily Cap: $2,000',
            'Minimum: $50',
            'Maximum: $5,000',
            'Ideal for Beginners',
            'Duration: 250 Days'
        ]
    ],
    'RISE' => [
        'name' => 'RISE',
        'min' => 5001,
        'max' => 25000,
        'roi' => '1.3%',
        'color' => 'linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%)',
        'icon' => '🚀',
        'features' => [
            'Daily ROI: 1.3%',
            'Referral Bonus: 8%',
            'Binary Matching: 10%',
            'Daily Cap: $2,500',
            'Minimum: $5,001',
            'Maximum: $25,000',
            'For Growing Investors',
            'Duration: 250 Days'
        ]
    ],
    'TERRA' => [
        'name' => 'TERRA',
        'min' => 25001,
        'max' => 999999,
        'roi' => '1.5%',
        'color' => 'linear-gradient(135deg, #a78bfa 0%, #ec4899 100%)',
        'icon' => '👑',
        'features' => [
            'Daily ROI: 1.5%',
            'Referral Bonus: 9%',
            'Binary Matching: 10%',
            'Daily Cap: $5,000',
            'Minimum: $25,001',
            'No Maximum Limit',
            'Premium Elite Plan',
            'Duration: 250 Days'
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investment Packages - Evolentra</title>
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

        .current-investment {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 3rem;
            backdrop-filter: blur(20px);
        }

        .current-investment-label {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 0.5rem;
        }

        .current-investment-value {
            font-size: 2rem;
            font-weight: 800;
            color: white;
        }

        .packages-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .package-card {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem;
            padding: 2rem;
            backdrop-filter: blur(20px);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .package-card:hover {
            transform: translateY(-10px);
            border-color: rgba(167, 139, 250, 0.5);
            box-shadow: 0 20px 60px rgba(167, 139, 250, 0.3);
        }

        .package-card.selected {
            border-color: #a78bfa;
            box-shadow: 0 20px 60px rgba(167, 139, 250, 0.4);
        }

        .package-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .package-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .package-name {
            font-size: 1.75rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .package-roi {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #a78bfa 0%, #ec4899 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .package-roi-label {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .package-range {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.75rem;
            border-radius: 0.75rem;
            text-align: center;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .package-features {
            list-style: none;
            margin-bottom: 2rem;
        }

        .package-features li {
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .package-features li:last-child {
            border-bottom: none;
        }

        .package-features i {
            color: #10b981;
            font-size: 1rem;
        }

        .select-package-btn {
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
            letter-spacing: 0.5px;
        }

        .select-package-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(167, 139, 250, 0.5);
        }

        .investment-form {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem;
            padding: 2rem;
            backdrop-filter: blur(20px);
            max-width: 600px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .form-input {
            width: 100%;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 0.75rem;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.12);
            border-color: #a78bfa;
        }

        .btn-invest {
            width: 100%;
            padding: 1.25rem;
            background: linear-gradient(135deg, #a78bfa 0%, #ec4899 100%);
            border: none;
            border-radius: 0.75rem;
            color: white;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-invest:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 40px rgba(167, 139, 250, 0.5);
        }

        .alert {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #86efac;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        @media (max-width: 1024px) {
            .packages-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .main-wrapper {
                margin-left: 0;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar_nav.php'; ?>
    
    <div class="main-wrapper">
        <div class="page-header">
            <h1 class="page-title">Investment Packages</h1>
            <p class="page-subtitle">Choose the plan that fits your investment goals</p>
        </div>
        
        <!-- Crypto Ticker Bar -->
        <div class="crypto-ticker-bar" style="display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 1rem; margin-bottom: 2rem; scrollbar-width: none;">
            <!-- Populated by JS -->
        </div>

        <?php if($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= $message ?></span>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= $error ?></span>
            </div>
        <?php endif; ?>

        <div class="current-investment">
            <div class="current-investment-label">Your Current Total Investment</div>
            <div class="current-investment-value">$<?= number_format($current_investment, 2) ?></div>
        </div>

        <div class="packages-grid">
            <?php foreach ($packages as $key => $pkg): ?>
            <div class="package-card" onclick="selectPackage('<?= $key ?>', <?= $pkg['min'] ?>, <?= $pkg['max'] ?>)">
                <div class="package-header">
                    <div class="package-icon"><?= $pkg['icon'] ?></div>
                    <div class="package-name"><?= $pkg['name'] ?></div>
                    <div class="package-roi"><?= $pkg['roi'] ?></div>
                    <div class="package-roi-label">Daily ROI</div>
                </div>

                <div class="package-range">
                    $<?= number_format($pkg['min']) ?> - $<?= number_format($pkg['max']) ?>
                </div>

                <ul class="package-features">
                    <?php foreach ($pkg['features'] as $feature): ?>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span><?= $feature ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <button type="button" class="select-package-btn" onclick="selectPackage('<?= $key ?>', <?= $pkg['min'] ?>, <?= $pkg['max'] ?>)">
                    Select <?= $pkg['name'] ?>
                </button>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="investment-form" id="investmentForm" style="display: none;">
            <h3 style="text-align: center; margin-bottom: 1.5rem; font-size: 1.5rem;">
                Complete Your Investment
            </h3>

            <form method="POST" action="">
                <input type="hidden" name="package" id="selectedPackage">
                
                <div class="form-group">
                    <label>Selected Package</label>
                    <input type="text" class="form-input" id="packageDisplay" readonly>
                </div>

                <div class="form-group">
                    <label>Investment Amount (USD)</label>
                    <input type="number" name="amount" id="amountInput" class="form-input" 
                           placeholder="Enter amount" required min="50" step="0.01">
                    <small id="rangeHint" style="color: rgba(255,255,255,0.6); font-size: 0.875rem;"></small>
                </div>

                <div class="form-group">
                    <label>Payment Method</label>
                    <select class="form-input" required>
                        <option value="">Choose Payment Method</option>
                        <option value="BTC">Bitcoin (BTC)</option>
                        <option value="ETH">Ethereum (ETH)</option>
                        <option value="USDT">Tether (USDT)</option>
                        <option value="TRX">Tron (TRX)</option>
                        <option value="XRP">Ripple (XRP)</option>
                    </select>
                </div>

                <button type="submit" name="invest" class="btn-invest">
                    <i class="fas fa-rocket"></i> Invest Now
                </button>
            </form>
        </div>
    </div>

    <script>
        function selectPackage(packageName, min, max) {
            document.getElementById('selectedPackage').value = packageName;
            document.getElementById('packageDisplay').value = packageName + ' Plan';
            document.getElementById('amountInput').min = min;
            document.getElementById('amountInput').max = max;
            document.getElementById('rangeHint').textContent = 'Range: $' + min.toLocaleString() + ' - $' + max.toLocaleString();
            
            document.getElementById('investmentForm').style.display = 'block';
            document.getElementById('investmentForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Highlight selected card
            document.querySelectorAll('.package-card').forEach(card => {
                card.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
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
                el.style.background = 'rgba(255, 255, 255, 0.05)';
                el.style.border = '1px solid rgba(255, 255, 255, 0.1)';
                el.style.borderRadius = '0.75rem';
                el.style.minWidth = '140px';
                el.style.padding = '0.75rem 1rem';
                el.style.backdropFilter = 'blur(10px)';
                el.id = `ticker-${sym}`;
                el.innerHTML = `
                    <div style="font-size: 0.75rem; color: #94a3b8; font-weight:700; margin-bottom: 0.25rem;">${sym.replace('USDT','')}</div>
                    <div class="price" style="font-size: 1.1rem; font-weight: 700;">Loading...</div>
                    <div class="change" style="font-size: 0.75rem;">--</div>
                `;
                tickerContainer.appendChild(el);
            });

            // Initial REST Fetch
            tickerSyms.forEach(async (sym) => {
                const data = await binance.fetchTicker(sym);
                if (data && data.lastPrice) {
                    const el = document.getElementById(`ticker-${sym}`);
                    if(el) {
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
                }
            });

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
