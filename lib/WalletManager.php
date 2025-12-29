<?php
/**
 * WalletManager Class
 * Unified wallet management for Binance, Trust, MetaMask, and other Web3 wallets
 */

class WalletManager {
    private $conn;
    
    // Supported networks
    const NETWORK_BSC = 'BSC';
    const NETWORK_ETH = 'ETH';
    
    // Supported tokens
    const TOKEN_USDT = 'USDT';
    const TOKEN_BNB = 'BNB';
    
    // Contract addresses (BSC)
    const USDT_BSC_CONTRACT = '0x55d398326f99059fF775485246999027B3197955';
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }
    
    /**
     * Detect wallet type from user agent or provider
     */
    public function detectWallet($userAgent, $provider = null) {
        if ($provider) {
            if (strpos($provider, 'BinanceChain') !== false) return 'binance';
            if (strpos($provider, 'Trust') !== false) return 'trust';
            if (strpos($provider, 'MetaMask') !== false) return 'metamask';
        }
        
        if (strpos($userAgent, 'Binance') !== false) return 'binance';
        if (strpos($userAgent, 'Trust') !== false) return 'trust';
        
        return 'other';
    }
    
    /**
     * Validate wallet address format
     */
    public function validateWalletAddress($address, $network = self::NETWORK_BSC) {
        // Ethereum/BSC address validation (0x + 40 hex characters)
        if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $address)) {
            return false;
        }
        
        // Additional checksum validation can be added here
        return true;
    }
    
    /**
     * Connect wallet and verify signature
     */
    public function connectWallet($userId, $walletType, $walletAddress, $signature, $message, $network = self::NETWORK_BSC) {
        // Validate address
        if (!$this->validateWalletAddress($walletAddress, $network)) {
            return ['success' => false, 'error' => 'Invalid wallet address'];
        }
        
        // Verify signature
        if (!$this->verifySignature($walletAddress, $signature, $message)) {
            return ['success' => false, 'error' => 'Invalid signature'];
        }
        
        // Check if wallet already connected to another user
        $check = $this->conn->query("SELECT user_id FROM mlm_wallet_connections 
                                     WHERE wallet_address='$walletAddress' AND user_id != $userId");
        if ($check && $check->num_rows > 0) {
            return ['success' => false, 'error' => 'Wallet already connected to another account'];
        }
        
        // Store wallet connection
        $sql = "INSERT INTO mlm_wallet_connections 
                (user_id, wallet_type, wallet_address, network, is_verified, signature)
                VALUES ($userId, '$walletType', '$walletAddress', '$network', 1, '$signature')
                ON DUPLICATE KEY UPDATE 
                last_used_at = NOW(), is_verified = 1, signature = '$signature'";
        
        if ($this->conn->query($sql) === TRUE) {
            // Update user's primary wallet address
            $this->conn->query("UPDATE mlm_users SET wallet_address='$walletAddress' WHERE id=$userId");
            
            return ['success' => true, 'message' => 'Wallet connected successfully'];
        }
        
        return ['success' => false, 'error' => 'Database error: ' . $this->conn->error];
    }
    
    /**
     * Verify cryptographic signature
     */
    private function verifySignature($address, $signature, $message) {
        // This is a simplified version
        // In production, use proper ECDSA signature verification
        // Using libraries like kornrunner/keccak or web3.php
        
        // For now, we'll accept any signature if address is valid
        // TODO: Implement proper signature verification
        return strlen($signature) > 100; // Basic check
    }
    
    /**
     * Initiate payment request
     */
    public function initiatePayment($userId, $amount, $walletType, $walletAddress, $token = self::TOKEN_USDT, $network = self::NETWORK_BSC) {
        // Validate amount
        if ($amount < 50) {
            return ['success' => false, 'error' => 'Minimum investment is $50'];
        }
        
        // Create payment record
        $sql = "INSERT INTO mlm_crypto_payments 
                (user_id, wallet_type, wallet_address, amount, token, network, status)
                VALUES ($userId, '$walletType', '$walletAddress', $amount, '$token', '$network', 'pending')";
        
        if ($this->conn->query($sql) === TRUE) {
            $paymentId = $this->conn->insert_id;
            
            return [
                'success' => true,
                'payment_id' => $paymentId,
                'contract_address' => self::USDT_BSC_CONTRACT,
                'network' => $network,
                'token' => $token,
                'amount' => $amount
            ];
        }
        
        return ['success' => false, 'error' => 'Failed to create payment record'];
    }
    
    /**
     * Verify transaction on blockchain
     */
    public function verifyTransaction($txHash, $network = self::NETWORK_BSC) {
        // Validate tx hash format
        if (!preg_match('/^0x[a-fA-F0-9]{64}$/', $txHash)) {
            return ['success' => false, 'error' => 'Invalid transaction hash'];
        }
        
        // Check if transaction already processed
        $check = $this->conn->query("SELECT id, status FROM mlm_crypto_payments WHERE tx_hash='$txHash'");
        if ($check && $check->num_rows > 0) {
            $payment = $check->fetch_assoc();
            return [
                'success' => true,
                'status' => $payment['status'],
                'message' => 'Transaction already processed'
            ];
        }
        
        // In production, verify transaction on blockchain using RPC
        // For now, we'll mark it as confirming
        return [
            'success' => true,
            'status' => 'confirming',
            'confirmations' => 0
        ];
    }
    
    /**
     * Process payment callback (from blockchain listener or webhook)
     */
    public function processPaymentCallback($txHash, $fromAddress, $toAddress, $amount, $blockNumber, $confirmations = 12) {
        // Find payment by tx hash or create new one
        $payment = $this->conn->query("SELECT * FROM mlm_crypto_payments WHERE tx_hash='$txHash'")->fetch_assoc();
        
        if (!$payment) {
            // Try to find by wallet address and amount
            $payment = $this->conn->query("SELECT * FROM mlm_crypto_payments 
                                          WHERE wallet_address='$fromAddress' 
                                          AND amount=$amount 
                                          AND status='pending' 
                                          ORDER BY created_at DESC LIMIT 1")->fetch_assoc();
            
            if ($payment) {
                // Update with tx hash
                $this->conn->query("UPDATE mlm_crypto_payments 
                                   SET tx_hash='$txHash', block_number=$blockNumber, status='confirming'
                                   WHERE id={$payment['id']}");
            } else {
                return ['success' => false, 'error' => 'Payment not found'];
            }
        }
        
        // Update confirmations
        $status = $confirmations >= 12 ? 'completed' : 'confirming';
        $this->conn->query("UPDATE mlm_crypto_payments 
                           SET confirmations=$confirmations, status='$status', block_number=$blockNumber
                           WHERE tx_hash='$txHash'");
        
        // If completed, process the investment
        if ($status === 'completed' && $payment['status'] !== 'completed') {
            $this->processCompletedPayment($payment['id']);
        }
        
        return ['success' => true, 'status' => $status, 'confirmations' => $confirmations];
    }
    
    /**
     * Process completed payment
     */
    private function processCompletedPayment($paymentId) {
        $payment = $this->conn->query("SELECT * FROM mlm_crypto_payments WHERE id=$paymentId")->fetch_assoc();
        
        if (!$payment || $payment['status'] === 'completed') {
            return false;
        }
        
        $userId = $payment['user_id'];
        $amount = $payment['amount'];
        
        // Update user investment
        $this->conn->query("UPDATE mlm_users SET investment = investment + $amount WHERE id=$userId");
        
        // Add to main wallet
        $this->conn->query("UPDATE mlm_wallets SET main_wallet = main_wallet + $amount WHERE user_id=$userId");
        
        // Log transaction
        $this->conn->query("INSERT INTO mlm_transactions (user_id, type, amount, description) 
                           VALUES ($userId, 'deposit', $amount, 'Crypto payment via {$payment['wallet_type']} - TX: {$payment['tx_hash']}')");
        
        // Mark payment as completed
        $this->conn->query("UPDATE mlm_crypto_payments SET status='completed', confirmed_at=NOW() WHERE id=$paymentId");
        
        // Process referral commission if applicable
        $user = $this->conn->query("SELECT sponsor_id FROM mlm_users WHERE id=$userId")->fetch_assoc();
        if ($user && $user['sponsor_id']) {
            require_once 'Compensation.php';
            $comp = new Compensation($this->conn);
            $comp->processReferral($amount, $user['sponsor_id'], $userId);
        }
        
        return true;
    }
    
    /**
     * Get user's connected wallets
     */
    public function getUserWallets($userId) {
        $result = $this->conn->query("SELECT * FROM mlm_wallet_connections 
                                      WHERE user_id=$userId 
                                      ORDER BY last_used_at DESC");
        
        $wallets = [];
        while ($row = $result->fetch_assoc()) {
            $wallets[] = $row;
        }
        
        return $wallets;
    }
    
    /**
     * Get payment history
     */
    public function getPaymentHistory($userId, $limit = 20) {
        $result = $this->conn->query("SELECT * FROM mlm_crypto_payments 
                                      WHERE user_id=$userId 
                                      ORDER BY created_at DESC LIMIT $limit");
        
        $payments = [];
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
        
        return $payments;
    }
    
    /**
     * Get pending payments (for monitoring)
     */
    public function getPendingPayments() {
        $result = $this->conn->query("SELECT * FROM mlm_crypto_payments 
                                      WHERE status IN ('pending', 'confirming') 
                                      ORDER BY created_at ASC");
        
        $payments = [];
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
        
        return $payments;
    }
}
?>
