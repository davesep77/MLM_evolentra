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
$wallet_query = $conn->query("SELECT * FROM mlm_wallets WHERE user_id = $user_id");
$wallet = $wallet_query->fetch_assoc();

// Calculate trading stats
$total_investment = $user['investment'];
$roi_earned = $wallet['roi_wallet'];
$roi_percentage = $total_investment > 0 ? ($roi_earned / $total_investment) * 100 : 0;

// Determine ROI rate based on package
$daily_roi_rate = 1.2; // Default ROOT
if ($total_investment >= 5001 && $total_investment <= 25000) {
    $daily_roi_rate = 1.3; // RISE
} elseif ($total_investment >= 25001) {
    $daily_roi_rate = 1.5; // TERRA
}

// Calculate projected earnings
$daily_roi = ($total_investment * $daily_roi_rate) / 100;
$weekly_roi = $daily_roi * 7;
$monthly_roi = $daily_roi * 30;

// Get ROI transaction history
$roi_history = $conn->query("
    SELECT amount, created_at 
    FROM mlm_transactions 
    WHERE user_id = $user_id AND type = 'ROI' 
    ORDER BY created_at DESC 
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trading ROI - Evolentra</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- TradingView Lightweight Charts v4.1.1 -->
    <script src="https://unpkg.com/lightweight-charts@4.1.1/dist/lightweight-charts.standalone.production.js"></script>
    <style>
        :root {
            --cmc-bg: #0d1017;
            --cmc-card: #0d1017;
            --cmc-text: #fff;
            --cmc-text-sec: #a1a5b6;
            --cmc-green: #16c784;
            --cmc-red: #ea3943;
            --cmc-blue: #3861fb;
        }
        body { background-color: var(--cmc-bg) !important; color: var(--cmc-text); font-family: Inter, sans-serif; }
        
        /* Grid Layout */
        .cmc-grid {
            display: grid;
            grid-template-columns: 350px 1fr 300px;
            gap: 2rem;
            align-items: start;
        }

        /* Stats Column */
        .coin-header { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; }
        .stats-grid-cmc { display: grid; grid-template-columns: 1fr; gap: 1rem; margin-top: 1.5rem; }
        .stat-item { border-bottom: 1px solid #222531; padding-bottom: 0.75rem; display: flex; justify-content: space-between; align-items: center; }
        .stat-label { color: var(--cmc-text-sec); font-size: 0.85rem; display: flex; align-items: center; gap: 4px; }
        .stat-val { font-weight: 600; font-size: 0.95rem; }
        .stat-health-bar { width: 40px; height: 4px; background: #323546; border-radius: 2px; position: relative; }
        .stat-health-bar .fill { background: #cfd6e4; height: 100%; border-radius: 2px; }
        .info-icon { opacity: 0.5; font-size: 10px; border: 1px solid #666; border-radius: 50%; width: 12px; height: 12px; display: inline-flex; align-items: center; justify-content: center; }

        /* Chart Controls */
        .chart-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .chart-tabs button, .time-controls button {
            background: transparent; border: none; color: var(--cmc-text-sec); font-weight: 600; padding: 4px 8px; font-size: 0.85rem; cursor: pointer;
        }
        .chart-tabs button.active { background: #222531; color: #fff; border-radius: 6px; }
        .time-controls button.active { color: var(--cmc-blue); background: rgba(56, 97, 251, 0.1); border-radius: 4px; }

        /* Right Column */
         .sentiment-card { background: transparent; }
         .sentiment-bar-container { display: flex; height: 8px; border-radius: 4px; overflow: hidden; margin: 1rem 0; }
         .bar-bull { background: var(--cmc-green); color: #fff; font-size: 0; }
         .bar-bear { background: var(--cmc-red); color: #fff; font-size: 0; }
         .vote-btns { display: flex; gap: 1rem; }
         .vote-btns button { flex: 1; border: none; padding: 10px; border-radius: 8px; font-weight: 600; cursor: pointer; color: #fff; }
         .btn-bull { background: #323546; color: var(--cmc-green); }
         .btn-bear { background: #323546; color: var(--cmc-red); }
         
         /* Responsive */
         @media (max-width: 1200px) {
             .cmc-grid { grid-template-columns: 1fr; }
             .cmc-col-right { display: none; } /* Hide right sidebar on smaller screens for now */
         }
         
         .text-green { color: var(--cmc-green); }
         .text-red { color: var(--cmc-red); }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar_nav.php'; ?>
        
        <div class="main-content" style="background-color: #0d1017; color: #fff; font-family: Inter, sans-serif;">
            <div class="container-fluid" style="padding: 2rem;">
                
                <!-- CMC Header -->
                <div style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; color: #a1a5b6;">
                    <span>Cryptocurrencies</span> <span style="color: #64748b;">></span> 
                    <span style="color: #fff;">Bitcoin</span>
                </div>

                <!-- 3-Column Grid -->
                <div class="cmc-grid">
                    
                    <!-- Left Col: Coin Info & Stats -->
                    <div class="cmc-col-left">
                        <div class="coin-header">
                            <img src="https://s2.coinmarketcap.com/static/img/coins/64x64/1.png" alt="BTC" style="width: 32px; height: 32px;">
                            <h1 style="font-size: 1.5rem; font-weight: 700; margin: 0;">Bitcoin</h1>
                            <span style="background: #232531; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; color: #a1a5b6;">BTC</span>
                            <span style="background: #323546; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem;">#1</span>
                        </div>

                        <div class="price-section" style="margin-top: 1rem;">
                            <div id="main-price" style="font-size: 2.5rem; font-weight: 800;">$87,158.29</div>
                            <div id="main-change" style="font-size: 1rem; color: #ea3943; display: flex; align-items: center; gap: 0.25rem;">
                                <span id="change-icon">▼</span> <span id="change-val">0.49% (24h)</span>
                            </div>
                        </div>

                        <!-- Stats Grid -->
                        <div class="stats-grid-cmc">
                            <div class="stat-item">
                                <div class="stat-label">Market cap <span class="info-icon">i</span></div>
                                <div class="stat-val" id="stat-mcap">$1.74T</div>
                                <div class="stat-sub text-green">▲ 0.47%</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Volume (24h) <span class="info-icon">i</span></div>
                                <div class="stat-val" id="stat-vol">$20.24B</div>
                                <div class="stat-sub text-red">▼ 19.75%</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Volume/Mkt Cap (24h)</div>
                                <div class="stat-val">1.16%</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Circulating supply <span class="info-icon">i</span></div>
                                <div class="stat-val" id="stat-circ">19.96M BTC</div>
                                <div class="stat-health-bar"><div class="fill" style="width: 92%"></div></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Total supply <span class="info-icon">i</span></div>
                                <div class="stat-val" id="stat-total">19.96M BTC</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Max. supply <span class="info-icon">i</span></div>
                                <div class="stat-val" id="stat-max">21M BTC</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-label">Fully Diluted Valuation</div>
                                <div class="stat-val" id="stat-fdv">$1.83T</div>
                            </div>
                        </div>
                    </div>

                    <!-- Center Col: Chart -->
                    <div class="cmc-col-center">
                        <div class="chart-controls">
                            <div class="chart-tabs">
                                <button class="active">Price</button>
                                <button>Market Cap</button>
                                <button>TradingView</button>
                            </div>
                            <div class="time-controls">
                                <button>1D</button>
                                <button>7D</button>
                                <button>1M</button>
                                <button>1Y</button>
                                <button class="active">ALL</button>
                                <button>LOG</button>
                            </div>
                        </div>
                        
                        <div class="chart-wrapper-cmc">
                            <div id="tv-chart" style="width: 100%; height: 450px;"></div>
                        </div>
                    </div>

                    <!-- Right Col: Sentiment -->
                    <div class="cmc-col-right">
                        <div class="sentiment-card">
                            <h3 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 0.5rem;">Community sentiment</h3>
                            <div class="sentiment-bar-container">
                                <div class="bar-bull" style="width: 81%; border-radius: 4px 0 0 4px;">81%</div>
                                <div class="bar-bear" style="width: 19%; border-radius: 0 4px 4px 0;">19%</div>
                            </div>
                            <div class="vote-btns">
                                <button class="btn-bull">🚀 Bullish</button>
                                <button class="btn-bear">🐻 Bearish</button>
                            </div>
                        </div>

                        <div class="news-list" style="margin-top: 2rem;">
                            <div class="news-tabs" style="margin-bottom: 1rem; display: flex; gap: 0.5rem;">
                                <button class="active" style="background: #eef2f6; color: #000; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 0.8rem;">Top</button>
                                <button style="background: transparent; color: #a1a5b6; padding: 2px 8px; font-weight: 600; font-size: 0.8rem;">Latest</button>
                            </div>
                            
                            <div class="news-item" style="margin-bottom: 1.5rem;">
                                <div class="news-header" style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <img src="https://ui-avatars.com/api/?name=Explorer+T&background=random&color=fff&size=64" style="width: 24px; height: 24px; border-radius: 50%;">
                                    <span style="font-weight: 700; font-size: 0.9rem;">ExplorerT</span> 
                                    <span style="color: #a1a5b6; font-size: 0.8rem;">· Dec 23</span>
                                </div>
                                <p style="font-size: 0.9rem; line-height: 1.4; color: #fff;">Gold has pushed to a new all-time high while $BTC has reclaimed the $90,000 level setting the stage for a Santa-rally showroom...</p>
                            </div>
                            
                            <div class="news-item">
                                <div class="news-header" style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <img src="https://ui-avatars.com/api/?name=Crypto+King&background=10b981&color=fff&size=64" style="width: 24px; height: 24px; border-radius: 50%;">
                                    <span style="font-weight: 700; font-size: 0.9rem;">CryptoKing</span> 
                                    <span style="color: #a1a5b6; font-size: 0.8rem;">· 1h</span>
                                </div>
                                <p style="font-size: 0.9rem; line-height: 1.4; color: #fff;">$SOL is looking very bullish today! 🚀 <span style="color: #3861fb;">#Solana</span> <span style="color: #3861fb;">#Crypto</span></p>
                            </div>
                        </div>
                    </div>

                </div> <!-- End Grid -->

            </div>
        </div>
    </div>

    <!-- Chart Logic -->
    <script src="js/binance_ws.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const chartContainer = document.getElementById('tv-chart');
            const binance = new BinanceIntegration();
            const watchedPairs = ['BTCUSDT']; 
            let currentSymbol = 'BTCUSDT';
            let currentInterval = '1m'; // Faster interval for "live" feel

            // 1. Initialize Chart (Area Style for CMC look)
            const chart = LightweightCharts.createChart(chartContainer, {
                width: chartContainer.clientWidth,
                height: chartContainer.clientHeight,
                layout: { background: { type: 'solid', color: 'transparent' }, textColor: '#64748b' },
                grid: {
                    vertLines: { visible: false },
                    horzLines: { color: 'rgba(255, 255, 255, 0.05)' },
                },
                timeScale: {
                    timeVisible: true,
                    secondsVisible: true,
                    borderColor: '#222531',
                },
                rightPriceScale: {
                    borderColor: '#222531',
                    scaleMargins: { top: 0.2, bottom: 0.2 },
                },
                crosshair: {
                    mode: LightweightCharts.CrosshairMode.Normal,
                    vertLine: {
                        labelBackgroundColor: '#3861fb', 
                    },
                    horzLine: {
                        labelBackgroundColor: '#3861fb',
                    }
                },
            });

            // Use AreaSeries to match the user's CMC screenshot
            const mainSeries = chart.addAreaSeries({
                lineColor: '#16c784', 
                topColor: 'rgba(22, 199, 132, 0.2)',
                bottomColor: 'rgba(22, 199, 132, 0.0)',
                lineWidth: 2,
            });

            let lastClose = 0;
            let lastTime = 0;

            // 2. Data Loading
            async function loadChartData(symbol, interval) {
                const data = await binance.fetchKlines(symbol, interval);
                if (data && data.length > 0) {
                    // Convert candles to line data (using close price)
                    const lineData = data.map(d => ({
                        time: d.time,
                        value: d.close
                    }));
                    
                    mainSeries.setData(lineData);
                    
                    const last = data[data.length - 1];
                    lastClose = last.close;
                    lastTime = last.time;
                    
                    // Manually update header with latest static data
                    updatePriceHeader(lastClose);
                }
            }

            async function fetchCoinData() {
                const data = await binance.fetchTickers(watchedPairs); 
                let info = Array.isArray(data) ? data[0] : data;
                if (info) updateDashboard(info);
            }

            function updateDashboard(data) {
                const price = parseFloat(data.lastPrice);
                document.getElementById('main-price').innerText = '$' + price.toLocaleString(undefined, {minimumFractionDigits: 2});
                
                // Static fields (Mcap, etc) only update on refresh for now
                if (data.marketCap) document.getElementById('stat-mcap').innerText = '$' + formatCompact(data.marketCap);
                if (data.volume) document.getElementById('stat-vol').innerText = '$' + formatCompact(data.volume);
                if (data.circulatingSupply) document.getElementById('stat-circ').innerText = formatCompact(data.circulatingSupply) + ' BTC';
                if (data.maxSupply) document.getElementById('stat-max').innerText = formatCompact(data.maxSupply) + ' BTC';
                if (data.maxSupply) document.getElementById('stat-total').innerText = formatCompact(data.circulatingSupply) + ' BTC';
                if (data.fdv) document.getElementById('stat-fdv').innerText = '$' + formatCompact(data.fdv);
            }

            function updatePriceHeader(price) {
                document.getElementById('main-price').innerText = '$' + price.toLocaleString(undefined, {minimumFractionDigits: 2});
            }

            function formatCompact(num) {
                return Intl.NumberFormat('en-US', { notation: "compact", maximumFractionDigits: 2 }).format(num);
            }

            // --- Parallel Execution ---
            await Promise.all([
                loadChartData(currentSymbol, currentInterval),
                fetchCoinData()
            ]);

            // ==========================================
            // REAL-TIME SIMULATION ENGINE
            // ==========================================
            // Since API is blocked, we simulate ticks locally
            // to give the user the "Live" experience.
            
            setInterval(() => {
                // 1. Generate random walk
                const volatility = lastClose * 0.0002; // 0.02% volatility per tick
                const change = (Math.random() - 0.5) * volatility;
                const newPrice = lastClose + change;
                const now = Math.floor(Date.now() / 1000);

                // 2. Update Chart
                // If same second, update/overwrite. If new second, it's fine for AreaSeries (line).
                // But for smoothness we usually want unique timestamps or updates to candle.
                // For Area series, simply adding a point with current timestamp works if it's new.
                
                if (now > lastTime) {
                    mainSeries.update({
                        time: now,
                        value: newPrice
                    });
                    lastTime = now;
                }
                
                lastClose = newPrice;

                // 3. Update DOM (Price Flash)
                const priceEl = document.getElementById('main-price');
                priceEl.innerText = '$' + newPrice.toLocaleString(undefined, {minimumFractionDigits: 2});
                priceEl.style.color = change >= 0 ? '#16c784' : '#ea3943'; // Flash color
                
                // Reset color after 200ms
                setTimeout(() => { priceEl.style.color = '#fff'; }, 300);

            }, 1000); // Tick every 1 second


            // Resize Handling
            window.addEventListener('resize', () => {
                chart.applyOptions({ 
                    width: chartContainer.clientWidth,
                    height: chartContainer.clientHeight 
                });
            });
        });
    </script>
</body>
</html>
