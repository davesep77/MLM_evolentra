<?php
require 'config_db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get user data
$user_query = $conn->query("SELECT * FROM mlm_users WHERE id=$user_id");
$user_data = $user_query->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crypto Wallet Payment - Evolentra</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar_nav.php'; ?>
        
        <div class="main-content">
            <div class="container">
                <h1 class="page-title"><i class="fas fa-wallet"></i> Crypto Wallet Payment</h1>
                <p class="page-subtitle">Connect your wallet and make instant crypto payments</p>
                
                <!-- Testnet Toggle -->
                <div class="testnet-toggle">
                    <label class="toggle-switch">
                        <input type="checkbox" id="testnet-toggle" onchange="toggleTestnet(this.checked)">
                        <span class="toggle-slider"></span>
                    </label>
                    <span class="toggle-label">Use Testnet (for testing)</span>
                </div>
                
                <!-- Wallet Selection -->
                <div id="wallet-selection" class="section-card">
                    <h2 class="section-title">Select Your Wallet</h2>
                    <p class="wallet-subtitle">Choose how you want to connect. WalletConnect works with any mobile wallet - no extension needed!</p>
                    
                    <div class="wallet-grid">
                        <!-- WalletConnect - NO EXTENSION NEEDED (Prioritized) -->
                        <div class="wallet-card recommended" onclick="connectWallet('walletconnect')">
                            <div class="recommended-badge">Recommended</div>
                            <div class="wallet-icon walletconnect-gradient">
                                <i class="fas fa-qrcode"></i>
                            </div>
                            <h3>WalletConnect</h3>
                            <p>Scan QR with mobile wallet</p>
                            <small class="no-extension">✓ No extension needed</small>
                            <button class="btn-connect">Connect via QR</button>
                        </div>
                        
                        <!-- MetaMask -->
                        <div class="wallet-card" onclick="connectWallet('metamask')">
                            <div class="wallet-icon metamask-gradient">
                                <i class="fab fa-ethereum"></i>
                            </div>
                            <h3>MetaMask</h3>
                            <p>Browser extension wallet</p>
                            <small class="extension-required" id="metamask-status">Checking...</small>
                            <button class="btn-connect">Connect</button>
                        </div>
                        
                        <!-- Trust Wallet -->
                        <div class="wallet-card" onclick="connectWallet('trust')">
                            <div class="wallet-icon trust-gradient">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h3>Trust Wallet</h3>
                            <p>Browser extension wallet</p>
                            <small class="extension-required" id="trust-status">Checking...</small>
                            <button class="btn-connect">Connect</button>
                        </div>
                        
                        <!-- Binance Wallet -->
                        <div class="wallet-card" onclick="connectWallet('binance')">
                            <div class="wallet-icon binance-gradient">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <h3>Binance Wallet</h3>
                            <p>Binance ecosystem wallet</p>
                            <small class="extension-required" id="binance-status">Checking...</small>
                            <button class="btn-connect">Connect</button>
                        </div>
                    </div>
                    
                    <!-- Installation Help -->
                    <div class="installation-help">
                        <h3><i class="fas fa-info-circle"></i> Don't have a wallet extension?</h3>
                        <p>Use <strong>WalletConnect</strong> (recommended) - works with Trust Wallet, MetaMask Mobile, Rainbow, and 100+ mobile wallets!</p>
                        <p>Or install a browser extension:</p>
                        <div class="extension-links">
                            <a href="https://metamask.io/download/" target="_blank" class="extension-link">
                                <i class="fab fa-ethereum"></i> Install MetaMask
                            </a>
                            <a href="https://trustwallet.com/browser-extension" target="_blank" class="extension-link">
                                <i class="fas fa-shield-alt"></i> Install Trust Wallet
                            </a>
                            <a href="https://www.binance.com/en/wallet" target="_blank" class="extension-link">
                                <i class="fas fa-wallet"></i> Install Binance Wallet
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Connected Wallet Info -->
                <div id="wallet-connected" class="section-card" style="display: none;">
                    <div class="connected-header">
                        <div class="connected-info">
                            <i class="fas fa-check-circle text-green"></i>
                            <div>
                                <h3>Wallet Connected</h3>
                                <p id="connected-address">0x...</p>
                            </div>
                        </div>
                        <button onclick="disconnectWallet()" class="btn-disconnect">Disconnect</button>
                    </div>
                </div>
                
                <!-- Payment Form -->
                <div id="payment-form" class="section-card" style="display: none;">
                    <h2 class="section-title">Make Payment</h2>
                    
                    <div class="payment-details">
                        <div class="form-group">
                            <label>Investment Amount (USD)</label>
                            <div class="input-with-icon">
                                <i class="fas fa-dollar-sign"></i>
                                <input type="number" id="payment-amount" min="50" step="0.01" placeholder="Enter amount (min $50)">
                            </div>
                            <small class="form-hint">Minimum investment: $50</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Payment Token</label>
                            <select id="payment-token" class="form-select">
                                <option value="USDT">USDT (Tether)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Network</label>
                            <select id="payment-network" class="form-select">
                                <option value="BSC">Binance Smart Chain (BSC)</option>
                            </select>
                        </div>
                        
                        <div class="payment-summary">
                            <div class="summary-row">
                                <span>Amount:</span>
                                <span id="summary-amount">$0.00</span>
                            </div>
                            <div class="summary-row">
                                <span>Network Fee:</span>
                                <span class="text-muted">~$0.20</span>
                            </div>
                            <div class="summary-row total">
                                <span>Total:</span>
                                <span id="summary-total">$0.00</span>
                            </div>
                        </div>
                        
                        <button onclick="processPayment()" class="btn-primary btn-large">
                            <i class="fas fa-paper-plane"></i> Send Payment
                        </button>
                    </div>
                </div>
                
                <!-- Transaction Status -->
                <div id="transaction-status" class="section-card" style="display: none;">
                    <div class="status-container">
                        <div class="status-icon">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                        <h3 id="status-title">Processing Transaction...</h3>
                        <p id="status-message">Please wait while we confirm your transaction</p>
                        <div class="tx-hash-display" id="tx-hash-container" style="display: none;">
                            <span>Transaction Hash:</span>
                            <a id="tx-hash-link" href="#" target="_blank"></a>
                        </div>
                    </div>
                </div>
                
                <!-- Payment History -->
                <div class="section-card mt-6">
                    <h2 class="section-title"><i class="fas fa-history"></i> Recent Payments</h2>
                    <div id="payment-history">
                        <p class="text-center text-muted">Loading payment history...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- WalletConnect Library -->
    <script src="https://unpkg.com/@walletconnect/web3-provider@1.8.0/dist/umd/index.min.js"></script>
    <script src="js/multi_wallet.js"></script>
    <script src="js/walletconnect_integration.js"></script>
    <script>
        let walletConnector = new MultiWalletConnector();
        
        // Check wallet availability on page load
        window.addEventListener('load', function() {
            setTimeout(checkWalletAvailability, 500);
        });
        
        function checkWalletAvailability() {
            const wallets = walletConnector.detectWallets();
            
            // Update MetaMask status
            const metamaskStatus = document.getElementById('metamask-status');
            if (wallets.metamask) {
                metamaskStatus.innerHTML = '✓ Extension detected';
                metamaskStatus.className = 'extension-detected';
            } else {
                metamaskStatus.innerHTML = '⚠ Extension not installed';
                metamaskStatus.className = 'extension-required';
            }
            
            // Update Trust Wallet status
            const trustStatus = document.getElementById('trust-status');
            if (wallets.trust) {
                trustStatus.innerHTML = '✓ Extension detected';
                trustStatus.className = 'extension-detected';
            } else {
                trustStatus.innerHTML = '⚠ Extension not installed';
                trustStatus.className = 'extension-required';
            }
            
            // Update Binance Wallet status
            const binanceStatus = document.getElementById('binance-status');
            if (wallets.binance) {
                binanceStatus.innerHTML = '✓ Extension detected';
                binanceStatus.className = 'extension-detected';
            } else {
                binanceStatus.innerHTML = '⚠ Extension not installed';
                binanceStatus.className = 'extension-required';
            }
        }
        
        function toggleTestnet(enabled) {
            walletConnector.toggleTestnet(enabled);
            const label = document.querySelector('.toggle-label');
            label.textContent = enabled ? 'Using Testnet (Safe for testing)' : 'Use Testnet (for testing)';
            label.style.color = enabled ? '#fbbf24' : '';
            
            if (enabled) {
                showToast('Switched to BSC Testnet', 'info');
            } else {
                showToast('Switched to BSC Mainnet', 'info');
            }
        }
        
        async function connectWallet(type) {
            // Check if wallet is available
            const wallets = walletConnector.detectWallets();
            
            // Special handling for extension-based wallets
            if (type === 'metamask' && !wallets.metamask) {
                showInstallPrompt('MetaMask', 'https://metamask.io/download/');
                return;
            }
            if (type === 'trust' && !wallets.trust) {
                showInstallPrompt('Trust Wallet', 'https://trustwallet.com/browser-extension');
                return;
            }
            if (type === 'binance' && !wallets.binance) {
                showInstallPrompt('Binance Wallet', 'https://www.binance.com/en/wallet');
                return;
            }
            
            showLoading('Connecting to ' + type + ' wallet...');
            
            let result;
            if (type === 'binance') {
                result = await walletConnector.connectBinanceWallet();
            } else if (type === 'trust') {
                result = await walletConnector.connectTrustWallet();
            } else if (type === 'metamask') {
                result = await walletConnector.connectMetaMask();
            } else if (type === 'walletconnect') {
                result = await walletConnector.connectWalletConnect();
            }
            
            hideLoading();
            
            if (result.success) {
                document.getElementById('wallet-selection').style.display = 'none';
                document.getElementById('wallet-connected').style.display = 'block';
                document.getElementById('payment-form').style.display = 'block';
                document.getElementById('connected-address').textContent = walletConnector.formatAddress(result.address);
                
                showToast('Wallet connected successfully!', 'success');
            } else {
                showToast(result.error || 'Failed to connect wallet', 'error');
            }
        }
        
        function disconnectWallet() {
            walletConnector.disconnect();
            document.getElementById('wallet-selection').style.display = 'block';
            document.getElementById('wallet-connected').style.display = 'none';
            document.getElementById('payment-form').style.display = 'none';
            showToast('Wallet disconnected', 'info');
        }
        
        async function processPayment() {
            const amount = parseFloat(document.getElementById('payment-amount').value);
            
            if (!amount || amount < 50) {
                showToast('Minimum investment is $50', 'error');
                return;
            }
            
            // Show transaction status
            document.getElementById('payment-form').style.display = 'none';
            document.getElementById('transaction-status').style.display = 'block';
            
            const result = await walletConnector.sendPayment(amount);
            
            if (result.success) {
                document.querySelector('#transaction-status .status-icon i').className = 'fas fa-check-circle text-green';
                document.getElementById('status-title').textContent = 'Payment Sent!';
                document.getElementById('status-message').textContent = 'Your transaction is being confirmed on the blockchain';
                
                // Show transaction hash
                document.getElementById('tx-hash-container').style.display = 'block';
                const txLink = document.getElementById('tx-hash-link');
                txLink.textContent = result.txHash.substring(0, 20) + '...';
                txLink.href = 'https://bscscan.com/tx/' + result.txHash;
                
                showToast('Payment sent successfully!', 'success');
                
                // Reload payment history
                setTimeout(() => loadPaymentHistory(), 3000);
            } else {
                document.querySelector('#transaction-status .status-icon i').className = 'fas fa-times-circle text-red';
                document.getElementById('status-title').textContent = 'Payment Failed';
                document.getElementById('status-message').textContent = result.error || 'Transaction failed';
                
                showToast(result.error || 'Payment failed', 'error');
            }
        }
        
        // Update payment summary
        document.getElementById('payment-amount').addEventListener('input', function() {
            const amount = parseFloat(this.value) || 0;
            document.getElementById('summary-amount').textContent = '$' + amount.toFixed(2);
            document.getElementById('summary-total').textContent = '$' + amount.toFixed(2);
        });
        
        // Load payment history
        async function loadPaymentHistory() {
            try {
                const response = await fetch('/api/wallet/payment.php');
                const data = await response.json();
                
                if (data.success && data.payments.length > 0) {
                    let html = '<div class="table-responsive"><table class="data-table"><thead><tr><th>Date</th><th>Amount</th><th>Token</th><th>Status</th><th>TX Hash</th></tr></thead><tbody>';
                    
                    data.payments.forEach(payment => {
                        const statusClass = payment.status === 'completed' ? 'badge-success' : payment.status === 'pending' ? 'badge-warning' : 'badge-info';
                        const txHashShort = payment.tx_hash ? payment.tx_hash.substring(0, 10) + '...' : 'Pending';
                        const txLink = payment.tx_hash ? `<a href="https://bscscan.com/tx/${payment.tx_hash}" target="_blank">${txHashShort}</a>` : txHashShort;
                        
                        html += `<tr>
                            <td>${new Date(payment.created_at).toLocaleDateString()}</td>
                            <td>$${parseFloat(payment.amount).toFixed(2)}</td>
                            <td>${payment.token}</td>
                            <td><span class="badge ${statusClass}">${payment.status}</span></td>
                            <td>${txLink}</td>
                        </tr>`;
                    });
                    
                    html += '</tbody></table></div>';
                    document.getElementById('payment-history').innerHTML = html;
                } else {
                    document.getElementById('payment-history').innerHTML = '<p class="text-center text-muted">No payment history yet</p>';
                }
            } catch (error) {
                console.error('Error loading payment history:', error);
            }
        }
        
        // Load on page load
        loadPaymentHistory();
        
        function showInstallPrompt(walletName, installUrl) {
            const modal = document.createElement('div');
            modal.className = 'install-modal';
            modal.innerHTML = `
                <div class="install-modal-content">
                    <div class="install-icon">⚠️</div>
                    <h2>${walletName} Not Installed</h2>
                    <p>To use ${walletName}, you need to install the browser extension first.</p>
                    <div class="install-options">
                        <a href="${installUrl}" target="_blank" class="btn-install">
                            <i class="fas fa-download"></i> Install ${walletName}
                        </a>
                        <button onclick="this.closest('.install-modal').remove()" class="btn-cancel">Cancel</button>
                    </div>
                    <div class="install-alternative">
                        <p><strong>Or use WalletConnect instead:</strong></p>
                        <p>Works with any mobile wallet - no extension needed!</p>
                        <button onclick="this.closest('.install-modal').remove(); connectWallet('walletconnect');" class="btn-walletconnect">
                            <i class="fas fa-qrcode"></i> Use WalletConnect
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
        
        function showLoading(message) {
            const loader = document.createElement('div');
            loader.id = 'loading-overlay';
            loader.innerHTML = `
                <div class="loading-content">
                    <div class="spinner"></div>
                    <p>${message}</p>
                </div>
            `;
            document.body.appendChild(loader);
        }
        
        function hideLoading() {
            const loader = document.getElementById('loading-overlay');
            if (loader) loader.remove();
        }
        
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = 'toast toast-' + type;
            toast.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : type === 'error' ? 'times-circle' : 'info-circle') + '"></i> ' + message;
            toast.style.cssText = 'position: fixed; bottom: 2rem; right: 2rem; background: linear-gradient(135deg, ' + (type === 'success' ? '#10b981, #059669' : type === 'error' ? '#ef4444, #dc2626' : '#3b82f6, #2563eb') + '); color: white; padding: 1rem 1.5rem; border-radius: 0.75rem; box-shadow: 0 10px 40px rgba(0,0,0,0.3); display: flex; align-items: center; gap: 0.75rem; z-index: 9999; font-weight: 600;';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }
    </script>
    
    <style>
        .wallet-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .wallet-subtitle {
            color: #94a3b8;
            margin-top: 0.5rem;
            font-size: 0.95rem;
        }
        
        .wallet-card {
            background: rgba(255, 255, 255, 0.03);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .wallet-card.recommended {
            border-color: rgba(16, 185, 129, 0.5);
            background: rgba(16, 185, 129, 0.05);
        }
        
        .recommended-badge {
            position: absolute;
            top: -10px;
            right: 10px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 700;
        }
        
        .wallet-card:hover {
            transform: translateY(-5px);
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 20px 60px rgba(99, 102, 241, 0.2);
        }
        
        .wallet-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
        }
        
        .binance-gradient { background: linear-gradient(135deg, #F3BA2F 0%, #F0B90B 100%); }
        .trust-gradient { background: linear-gradient(135deg, #3375BB 0%, #2A5A8E 100%); }
        .metamask-gradient { background: linear-gradient(135deg, #F6851B 0%, #E2761B 100%); }
        .walletconnect-gradient { background: linear-gradient(135deg, #3B99FC 0%, #2D7DD2 100%); }
        
        .testnet-toggle {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1rem 0 2rem;
            padding: 1rem;
            background: rgba(251, 191, 36, 0.1);
            border: 1px solid rgba(251, 191, 36, 0.3);
            border-radius: 0.75rem;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.2);
            transition: 0.4s;
            border-radius: 26px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: #fbbf24;
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(24px);
        }
        
        .toggle-label {
            color: #94a3b8;
            font-weight: 600;
        }
        
        .wallet-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.5rem;
        }
        
        .wallet-card p {
            color: #94a3b8;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        
        .no-extension {
            display: block;
            color: #10b981;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .extension-required {
            display: block;
            color: #fbbf24;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .extension-detected {
            display: block;
            color: #10b981;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .btn-connect {
            background: rgba(99, 102, 241, 0.2);
            border: 1px solid rgba(99, 102, 241, 0.5);
            color: #6366f1;
            padding: 0.75rem 2rem;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-connect:hover {
            background: rgba(99, 102, 241, 0.3);
        }
        
        .installation-help {
            margin-top: 3rem;
            padding: 2rem;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 1rem;
        }
        
        .installation-help h3 {
            color: #fff;
            font-size: 1.25rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .installation-help p {
            color: #94a3b8;
            margin-bottom: 1rem;
        }
        
        .extension-links {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1.5rem;
        }
        
        .extension-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .extension-link:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }
        
        .install-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }
        
        .install-modal-content {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 3rem;
            border-radius: 1.5rem;
            max-width: 500px;
            text-align: center;
            border: 2px solid rgba(255, 255, 255, 0.1);
        }
        
        .install-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .install-modal-content h2 {
            color: #fff;
            font-size: 1.75rem;
            margin-bottom: 1rem;
        }
        
        .install-modal-content p {
            color: #94a3b8;
            margin-bottom: 2rem;
        }
        
        .install-options {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .btn-install {
            flex: 1;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            padding: 1rem;
            border-radius: 0.75rem;
            text-decoration: none;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }
        
        .btn-install:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 40px rgba(99, 102, 241, 0.4);
        }
        
        .btn-cancel {
            flex: 1;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            padding: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.15);
        }
        
        .install-alternative {
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .btn-walletconnect {
            width: 100%;
            background: linear-gradient(135deg, #3B99FC 0%, #2D7DD2 100%);
            color: white;
            padding: 1rem;
            border: none;
            border-radius: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1rem;
            transition: all 0.3s;
        }
        
        .btn-walletconnect:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 40px rgba(59, 153, 252, 0.4);
        }
        
        #loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .loading-content {
            text-align: center;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-top-color: #6366f1;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .connected-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 1rem;
        }
        
        .connected-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .connected-info i {
            font-size: 2rem;
        }
        
        .connected-info h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff;
            margin: 0;
        }
        
        .connected-info p {
            color: #94a3b8;
            margin: 0;
            font-family: monospace;
        }
        
        .btn-disconnect {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.5);
            color: #ef4444;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
        }
        
        .payment-details {
            margin-top: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #fff;
            margin-bottom: 0.5rem;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        
        .input-with-icon input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            color: #fff;
            font-size: 1rem;
        }
        
        .form-select {
            width: 100%;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            color: #fff;
            font-size: 1rem;
        }
        
        .form-hint {
            display: block;
            margin-top: 0.5rem;
            color: #94a3b8;
            font-size: 0.875rem;
        }
        
        .payment-summary {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            padding: 1.5rem;
            margin: 2rem 0;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            color: #94a3b8;
        }
        
        .summary-row.total {
            border-top: 2px solid rgba(255, 255, 255, 0.1);
            margin-top: 0.5rem;
            padding-top: 1rem;
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff;
        }
        
        .btn-primary {
            width: 100%;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 0.75rem;
            font-size: 1.125rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 40px rgba(99, 102, 241, 0.4);
        }
        
        .status-container {
            text-align: center;
            padding: 3rem;
        }
        
        .status-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
        }
        
        .status-icon i {
            color: #6366f1;
        }
        
        .tx-hash-display {
            margin-top: 2rem;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 0.75rem;
        }
        
        .tx-hash-display a {
            color: #6366f1;
            text-decoration: none;
            font-family: monospace;
        }
        
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .badge-success { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .badge-warning { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
        .badge-info { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        
        @media (max-width: 1024px) {
            .wallet-grid { grid-template-columns: 1fr; }
        }
    </style>
</body>
</html>
