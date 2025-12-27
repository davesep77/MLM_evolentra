<?php
require 'config_db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_query = $conn->query("SELECT * FROM mlm_users WHERE id = $user_id");
$user = $user_query->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BSC Staking - Evolentra</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/web3@4.3.0/dist/web3.min.js"></script>
    <style>
        :root {
            --primary: #f0b90b;
            --secondary: #1e2329;
            --success: #0ecb81;
            --danger: #f6465d;
        }
        
        body { background: #0d1017; color: #fff; font-family: Inter, sans-serif; }
        
        .staking-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }
        
        .staking-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .staking-card {
            background: #1e2329;
            border-radius: 12px;
            padding: 2rem;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .staking-card h3 {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            color: var(--primary);
        }
        
        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        .stat-label {
            color: #848e9c;
            font-size: 0.9rem;
        }
        
        .stat-value {
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .input-group {
            margin: 1.5rem 0;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #848e9c;
            font-size: 0.9rem;
        }
        
        .input-group input {
            width: 100%;
            padding: 12px;
            background: #0d1017;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
        }
        
        .btn-stake {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: #000;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-stake:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(240, 185, 11, 0.3);
        }
        
        .btn-stake:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .btn-secondary {
            background: #2b3139;
            color: #fff;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        
        .alert-info {
            background: rgba(14, 203, 129, 0.1);
            border: 1px solid rgba(14, 203, 129, 0.3);
            color: var(--success);
        }
        
        .alert-warning {
            background: rgba(240, 185, 11, 0.1);
            border: 1px solid rgba(240, 185, 11, 0.3);
            color: var(--primary);
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar_nav.php'; ?>
        
        <div class="main-content">
            <div class="staking-container">
                <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">BSC Staking Dashboard</h1>
                <p style="color: #848e9c; margin-bottom: 2rem;">Stake EVOL tokens to earn 1.2% daily rewards</p>
                
                <div id="connection-status" class="alert alert-warning">
                    <i class="fas fa-wallet"></i> Please connect your wallet to continue
                </div>
                
                <div class="staking-grid">
                    <!-- Balance Card -->
                    <div class="staking-card">
                        <h3><i class="fas fa-coins"></i> Your Balance</h3>
                        <div class="stat-row">
                            <span class="stat-label">EVOL Balance</span>
                            <span class="stat-value" id="token-balance">0.00</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">BNB Balance</span>
                            <span class="stat-value" id="bnb-balance">0.00</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Wallet Address</span>
                            <span class="stat-value" id="wallet-address" style="font-size: 0.85rem;">Not connected</span>
                        </div>
                    </div>
                    
                    <!-- Staking Info Card -->
                    <div class="staking-card">
                        <h3><i class="fas fa-chart-line"></i> Staking Info</h3>
                        <div class="stat-row">
                            <span class="stat-label">Staked Amount</span>
                            <span class="stat-value" id="staked-amount">0.00 EVOL</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Pending Rewards</span>
                            <span class="stat-value" id="pending-rewards" style="color: var(--success);">0.00 EVOL</span>
                        </div>
                        <div class="stat-row">
                            <span class="stat-label">Staking Since</span>
                            <span class="stat-value" id="staking-time">-</span>
                        </div>
                        <button class="btn-stake btn-secondary" id="claim-btn" onclick="claimRewards()" disabled>
                            <i class="fas fa-gift"></i> Claim Rewards
                        </button>
                    </div>
                    
                    <!-- Stake Card -->
                    <div class="staking-card">
                        <h3><i class="fas fa-lock"></i> Stake Tokens</h3>
                        <div class="input-group">
                            <label>Amount to Stake (Min: 100 EVOL)</label>
                            <input type="number" id="stake-amount" placeholder="0.00" min="100" step="0.01">
                        </div>
                        <button class="btn-stake" id="stake-btn" onclick="stakeTokens()" disabled>
                            <i class="fas fa-arrow-up"></i> Stake
                        </button>
                    </div>
                    
                    <!-- Unstake Card -->
                    <div class="staking-card">
                        <h3><i class="fas fa-unlock"></i> Unstake Tokens</h3>
                        <div class="input-group">
                            <label>Amount to Unstake</label>
                            <input type="number" id="unstake-amount" placeholder="0.00" min="0" step="0.01">
                        </div>
                        <button class="btn-stake btn-secondary" id="unstake-btn" onclick="unstakeTokens()" disabled>
                            <i class="fas fa-arrow-down"></i> Unstake
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/web3_integration.js"></script>
    <script>
        let isInitialized = false;
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', async () => {
            // Check if wallet is already connected
            if (typeof window.ethereum !== 'undefined') {
                const accounts = await window.ethereum.request({ method: 'eth_accounts' });
                if (accounts.length > 0) {
                    await initializeWeb3();
                }
            }
        });
        
        async function initializeWeb3() {
            try {
                showLoading('Connecting to BSC...');
                
                // Initialize Web3Manager
                await web3Manager.init(true); // true = use testnet
                
                // Set contract address (update this after deployment)
                const contractAddress = '0x...'; // TODO: Update with deployed contract address
                web3Manager.initContract(contractAddress);
                
                isInitialized = true;
                
                // Update UI
                await updateDashboard();
                
                // Enable buttons
                document.getElementById('stake-btn').disabled = false;
                document.getElementById('unstake-btn').disabled = false;
                document.getElementById('claim-btn').disabled = false;
                
                showSuccess('Connected to BSC successfully!');
                
                // Auto-refresh every 30 seconds
                setInterval(updateDashboard, 30000);
                
            } catch (error) {
                console.error('Initialization error:', error);
                showError('Failed to connect: ' + error.message);
            }
        }
        
        async function updateDashboard() {
            if (!isInitialized) return;
            
            try {
                // Get balances
                const tokenBalance = await web3Manager.getBalance();
                const bnbBalance = await web3Manager.getBNBBalance();
                
                // Get staking info
                const stakeInfo = await web3Manager.getStakeInfo();
                
                // Update UI
                document.getElementById('token-balance').textContent = parseFloat(tokenBalance).toFixed(2) + ' EVOL';
                document.getElementById('bnb-balance').textContent = parseFloat(bnbBalance).toFixed(4) + ' BNB';
                document.getElementById('wallet-address').textContent = web3Manager.formatAddress(web3Manager.account);
                
                document.getElementById('staked-amount').textContent = parseFloat(stakeInfo.stakedAmount).toFixed(2) + ' EVOL';
                document.getElementById('pending-rewards').textContent = parseFloat(stakeInfo.pendingReward).toFixed(4) + ' EVOL';
                document.getElementById('staking-time').textContent = stakeInfo.stakingTime.toLocaleDateString();
                
            } catch (error) {
                console.error('Dashboard update error:', error);
            }
        }
        
        async function stakeTokens() {
            const amount = document.getElementById('stake-amount').value;
            
            if (!amount || parseFloat(amount) < 100) {
                showError('Minimum stake amount is 100 EVOL');
                return;
            }
            
            try {
                showLoading('Staking tokens...');
                const tx = await web3Manager.stake(amount);
                showSuccess('Staking successful! TX: ' + tx.transactionHash.slice(0, 10) + '...');
                await updateDashboard();
                document.getElementById('stake-amount').value = '';
            } catch (error) {
                showError('Staking failed: ' + error.message);
            }
        }
        
        async function unstakeTokens() {
            const amount = document.getElementById('unstake-amount').value;
            
            if (!amount || parseFloat(amount) <= 0) {
                showError('Please enter a valid amount');
                return;
            }
            
            try {
                showLoading('Unstaking tokens...');
                const tx = await web3Manager.unstake(amount);
                showSuccess('Unstaking successful! TX: ' + tx.transactionHash.slice(0, 10) + '...');
                await updateDashboard();
                document.getElementById('unstake-amount').value = '';
            } catch (error) {
                showError('Unstaking failed: ' + error.message);
            }
        }
        
        async function claimRewards() {
            try {
                showLoading('Claiming rewards...');
                const tx = await web3Manager.claimRewards();
                showSuccess('Rewards claimed! TX: ' + tx.transactionHash.slice(0, 10) + '...');
                await updateDashboard();
            } catch (error) {
                showError('Claim failed: ' + error.message);
            }
        }
        
        function showLoading(message) {
            const status = document.getElementById('connection-status');
            status.className = 'alert alert-info';
            status.innerHTML = '<span class="loading"></span> ' + message;
        }
        
        function showSuccess(message) {
            const status = document.getElementById('connection-status');
            status.className = 'alert alert-info';
            status.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
        }
        
        function showError(message) {
            const status = document.getElementById('connection-status');
            status.className = 'alert alert-warning';
            status.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + message;
        }
        
        // Connect wallet button in sidebar
        window.connectWallet = async function() {
            if (!isInitialized) {
                await initializeWeb3();
            }
        };
    </script>
</body>
</html>
