<?php
require 'config_db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = floatval($_POST['amount']);
    $user_id = $_SESSION['user_id'];
    $txid = $conn->real_escape_string($_POST['txid'] ?? '');
    $network = $conn->real_escape_string($_POST['network'] ?? 'BEP20');

    if ($amount >= 50 && !empty($txid)) {
        try {
            $sql = "INSERT INTO mlm_deposits (user_id, amount, txid, network, status) VALUES ($user_id, $amount, '$txid', '$network', 'pending')";
            if ($conn->query($sql)) {
                $conn->query("INSERT INTO mlm_transactions (user_id, type, amount, description) VALUES ($user_id, 'PENDING_DEPOSIT', $amount, 'Binance Deposit ($network) - TxID: $txid')");
                $conn->query("INSERT INTO mlm_notifications (user_id, title, message, type) VALUES ($user_id, 'Deposit Submitted', 'Your deposit of $amount USDT is being verified via TxID: $txid. Funds will appear once confirmed.', 'info')");
                $message = "Your deposit has been submitted! Our team is verifying TxID: <b>$txid</b>. This usually takes 5-15 mins.";
            }
        } catch (mysqli_sql_exception $e) {
            $message = ($e->getCode() == 1062) ? "Error: This TxID has already been submitted." : "Error: " . $e->getMessage();
        }
    } elseif (empty($txid)) {
        $message = "Please provide the Binance Transaction ID (Hash) for verification.";
    } else {
        $message = "Minimum investment is $50 (ROOT Plan).";
    }
}

// Fetch Master Binance for display
$master_addr_res = $conn->query("SELECT setting_value FROM mlm_system_settings WHERE setting_key='master_binance_address'");
$master_binance = $master_addr_res->fetch_assoc()['setting_value'] ?? 'Contact Support';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invest - Evolentra</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="glass-card" style="max-width: 600px; margin: 2rem auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2>Deposit Capital</h2>
                <a href="dashboard.php" class="btn btn-outline">&larr; Back</a>
            </div>

            <!-- Crypto Ticker Bar -->
            <div class="crypto-ticker-bar" style="display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 1rem; margin-bottom: 2rem; scrollbar-width: none;">
                <!-- Populated by JS -->
            </div>

            <?php if($message): ?>
                <div style="background: rgba(16, 185, 129, 0.2); color: #86efac; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <!-- Payment Method Selection -->
            <div class="payment-cards-grid">
                <!-- Binance Wallet Card -->
                <div class="payment-card active" onclick="switchMethod('binance_pay')" id="card-binance_pay">
                    <div class="card-icon" style="color: #F3BA2F;"><i class="fab fa-binance"></i></div>
                    <div class="card-text">
                        <h3>Binance Wallet</h3>
                        <p>A wallet within the Binance app, which offers users a secure and streamlined method to manage their assets.</p>
                        <div class="platform-icons">
                            <i class="fab fa-apple"></i>
                            <i class="fab fa-android"></i>
                        </div>
                    </div>
                </div>

                <!-- Trust Wallet Card -->
                <div class="payment-card" onclick="switchMethod('manual')" id="card-manual">
                    <div class="card-icon" style="color: #3375BB;"><i class="fas fa-shield-alt"></i></div>
                    <div class="card-text">
                        <h3>Trust Wallet</h3>
                        <p>Helping over 25 million crypto users buy, store and sell cryptocurrencies and NFTs with Trust Wallet.</p>
                        <div class="platform-icons">
                            <i class="fas fa-desktop"></i>
                            <i class="fab fa-apple"></i>
                            <i class="fab fa-android"></i>
                        </div>
                    </div>
                </div>
            </div>

            <style>
                .payment-cards-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 1rem;
                    margin-bottom: 2rem;
                }
                
                .payment-card {
                    background: rgba(255, 255, 255, 0.05);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    border-radius: 1rem;
                    padding: 1.5rem;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    display: flex;
                    flex-direction: column;
                    gap: 1rem;
                    position: relative;
                    overflow: hidden;
                }
                
                .payment-card:hover {
                    transform: translateY(-2px);
                    background: rgba(255, 255, 255, 0.08);
                    border-color: rgba(255, 255, 255, 0.2);
                }
                
                .payment-card.active {
                    background: rgba(16, 185, 129, 0.1); 
                    border-color: var(--primary-accent);
                    box-shadow: 0 0 15px rgba(16, 185, 129, 0.2);
                }
                
                /* Specific Active States */
                #card-binance_pay.active {
                    background: rgba(243, 186, 47, 0.1);
                    border-color: #F3BA2F;
                    box-shadow: 0 0 15px rgba(243, 186, 47, 0.2);
                }
                
                #card-manual.active {
                    background: rgba(51, 117, 187, 0.1);
                    border-color: #3375BB;
                    box-shadow: 0 0 15px rgba(51, 117, 187, 0.2);
                }

                .card-icon {
                    font-size: 2.5rem;
                    margin-bottom: 0.5rem;
                }
                
                .card-text h3 {
                    margin-bottom: 0.5rem;
                    font-size: 1.1rem;
                    color: #fff;
                }
                
                .card-text p {
                    font-size: 0.8rem;
                    color: #94a3b8;
                    line-height: 1.4;
                    margin-bottom: 1rem;
                    height: 60px;
                    overflow: hidden;
                }
                
                .platform-icons {
                    display: flex;
                    gap: 0.5rem;
                    color: #64748b;
                    font-size: 0.9rem;
                }
                
                .method-content { display: none; margin-top: 1rem; animation: fadeIn 0.3s ease; }
                .method-content.active { display: block; }
                
                @media (max-width: 600px) {
                    .payment-cards-grid {
                        grid-template-columns: 1fr;
                    }
                    .card-text p {
                        height: auto;
                    }
                }
            </style>

            <!-- Instant Binance Pay Content -->
            <div id="method-binance_pay" class="method-content active">
                <form action="process_binance_pay.php" method="POST">
                    <div class="form-group">
                        <label>Enter Investment Amount (USD)</label>
                        <input type="number" name="amount" min="50" placeholder="Minimum $50" required style="font-size: 1.25rem; padding: 1.25rem; background: rgba(243, 186, 47, 0.05); border-color: rgba(243, 186, 47, 0.3);">
                        <p style="font-size: 0.8rem; color: #94a3b8; margin-top: 0.5rem;">
                            <i class="fas fa-info-circle"></i> You will be redirected to the secure Binance Pay checkout.
                        </p>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1.25rem; font-size: 1.1rem; margin-top: 1rem; background: #f3ba2f; color: #000; font-weight: 800; border: none; box-shadow: 0 10px 20px rgba(243, 186, 47, 0.2);">
                        <i class="fab fa-binance" style="margin-right: 0.5rem;"></i> Pay with Binance Pay
                    </button>
                </form>
            </div>

            <!-- Manual Transfer Content -->
            <div id="method-manual" class="method-content">
                <script src="https://cdnjs.cloudflare.com/ajax/libs/ethers/5.7.2/ethers.umd.min.js"></script>
                <script src="js/web3_payment.js"></script>

                <div class="web3-controls" style="margin-bottom: 2rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 2rem;">
                    <h4 style="color: #fff; margin-bottom: 1rem;">Option A: Connect Web3 Wallet</h4>
                    <p style="font-size: 0.9rem; color: #94a3b8; margin-bottom: 1rem;">If you have Trust Wallet, MetaMask, or Binance Extension installed.</p>
                    
                    <button type="button" id="btn-connect-wallet" onclick="window.web3Pay.connect()" class="btn btn-outline" style="width: 100%; margin-bottom: 1rem;">
                        <i class="fas fa-wallet"></i> Connect Wallet
                    </button>

                    <div id="web3-payment-section" style="display: none;">
                        <div class="form-group">
                            <label>Amount to Pay (USDT)</label>
                            <div style="display: flex; gap: 0.5rem;">
                                <input type="number" id="web3-amount" placeholder="50" style="flex: 1; padding: 0.75rem; border-radius: 0.5rem; border: none;">
                                <button type="button" onclick="window.web3Pay.pay(document.getElementById('web3-amount').value)" class="btn btn-primary">
                                    Pay Now
                                </button>
                            </div>
                            <div id="payment-status" style="margin-top: 1rem; font-size: 0.9rem;"></div>
                        </div>
                    </div>
                </div>

                <form method="POST" id="manual-payment-form">
                    <h4 style="color: #fff; margin-bottom: 1rem;">Option B: Manual Transfer</h4>
                    
                    <div class="form-group">
                        <label>Target Wallet Address (USDT BEP20)</label>
                        <div style="background: rgba(243, 186, 47, 0.1); border: 1px solid #f3ba2f; border-radius: 1rem; padding: 1.5rem; text-align: center; margin-top: 1rem;">
                            <div style="font-family: monospace; font-size: 1rem; color: #fff; word-break: break-all; margin-bottom: 1rem;">
                                <?= htmlspecialchars($master_binance) ?>
                            </div>
                            <button type="button" onclick="navigator.clipboard.writeText('<?= $master_binance ?>'); alert('Copied!')" class="btn btn-outline" style="font-size: 0.8rem; padding: 0.5rem 1rem;">
                                <i class="fas fa-copy"></i> Copy Address
                            </button>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 1rem;">
                        <label>Investment Amount (USD)</label>
                        <input type="number" name="amount" min="50" placeholder="Minimum $50" required style="font-size: 1.2rem; padding: 1rem;">
                    </div>

                    <div class="form-group" style="margin-top: 1rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 0.5rem;">
                            <label class="network-choice">
                                <input type="radio" name="network" value="BEP20" checked hidden>
                                <span style="display:block; padding:0.5rem; background:rgba(255,255,255,0.1); border-radius:0.5rem; text-align:center; cursor:pointer;">USDT (BEP20)</span>
                            </label>
                            <label class="network-choice">
                                <input type="radio" name="network" value="TRC20" hidden>
                                <span style="display:block; padding:0.5rem; background:rgba(255,255,255,0.05); border-radius:0.5rem; text-align:center; cursor:pointer; color:#777;">USDT (TRC20)</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 1.5rem;" id="manual-tx-input-section">
                        <label>Transaction Hash (TxID)</label>
                        <input type="text" name="txid" placeholder="Paste your TxID/Hash here" required style="font-size: 1rem; padding: 1rem; font-family: monospace;">
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem; margin-top: 1rem; background: #64748b; color: #fff; font-weight: 800; border: none;">
                        Submit for Verification
                    </button>
                </form>

                <script>
                    let web3Pay;
                    document.addEventListener('DOMContentLoaded', () => {
                         web3Pay = new Web3Payment({
                             masterAddress: '<?= $master_binance ?>',
                             // Optional: usdtContract: '...' 
                         });
                    });
                </script>
            </div>

            <script>
                function switchMethod(method) {
                    // Hide all content
                    document.querySelectorAll('.method-content').forEach(el => el.classList.remove('active'));
                    
                    // Reset all cards
                    document.querySelectorAll('.payment-card').forEach(el => el.classList.remove('active'));
                    
                    // Activate selected content
                    document.getElementById('method-' + method).classList.add('active');
                    
                    // Activate selected card
                    if(method === 'binance_pay') {
                        document.getElementById('card-binance_pay').classList.add('active');
                    } else {
                        document.getElementById('card-manual').classList.add('active');
                    }
                }
            </script>
            
            <div style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                <h4 style="margin-bottom: 1rem; color: #fff;">Investment Plans & Rates</h4>
                <div style="display: grid; gap: 1rem; grid-template-columns: repeat(3, 1fr); text-align: center;">
                    <div style="background: rgba(255,255,255,0.03); padding: 0.5rem; border-radius: 0.5rem;">
                        <div style="color: var(--secondary-color); font-weight: bold;">ROOT</div>
                        <div style="font-size: 0.8rem; color: #94a3b8;">$50 - $5,000</div>
                        <div style="font-size: 0.9rem; color: #fff;">1.2% Daily</div>
                        <div style="font-size: 0.75rem; color: #f59e0b;">Cap $2k/Day</div>
                        <div style="font-size: 0.75rem; color: #64748b; margin-top:0.25rem;">Duration: 250 Days</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.03); padding: 0.5rem; border-radius: 0.5rem;">
                        <div style="color: var(--secondary-color); font-weight: bold;">RISE</div>
                        <div style="font-size: 0.8rem; color: #94a3b8;">$5,001 - $25k</div>
                        <div style="font-size: 0.9rem; color: #fff;">1.3% Daily</div>
                         <div style="font-size: 0.75rem; color: #f59e0b;">Cap $2.5k/Day</div>
                         <div style="font-size: 0.75rem; color: #64748b; margin-top:0.25rem;">Duration: 250 Days</div>
                    </div>
                    <div style="background: rgba(255,255,255,0.03); padding: 0.5rem; border-radius: 0.5rem;">
                        <div style="color: var(--secondary-color); font-weight: bold;">TERRA</div>
                        <div style="font-size: 0.8rem; color: #94a3b8;">$25k+</div>
                        <div style="font-size: 0.9rem; color: #fff;">1.5% Daily</div>
                         <div style="font-size: 0.75rem; color: #f59e0b;">Cap $5k/Day</div>
                         <div style="font-size: 0.75rem; color: #64748b; margin-top:0.25rem;">Duration: 250 Days</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
                el.style.color = '#fff'; // Ensure text is visible on light bg if any
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
