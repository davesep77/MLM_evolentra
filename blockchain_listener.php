<?php
/**
 * Blockchain Event Listener
 * Listens for smart contract events and updates database
 */

require_once __DIR__ . '/config_db.php';
require_once __DIR__ . '/vendor/autoload.php';

use Web3\Web3;
use Web3\Contract;

class BlockchainEventListener {
    private $web3;
    private $contract;
    private $conn;
    private $contractAddress;
    private $lastProcessedBlock;
    
    // Contract ABI for events
    private $contractABI = '[
        {
            "anonymous": false,
            "inputs": [
                {"indexed": true, "name": "user", "type": "address"},
                {"indexed": false, "name": "amount", "type": "uint256"},
                {"indexed": true, "name": "referrer", "type": "address"}
            ],
            "name": "Staked",
            "type": "event"
        },
        {
            "anonymous": false,
            "inputs": [
                {"indexed": true, "name": "user", "type": "address"},
                {"indexed": false, "name": "amount", "type": "uint256"}
            ],
            "name": "Unstaked",
            "type": "event"
        },
        {
            "anonymous": false,
            "inputs": [
                {"indexed": true, "name": "user", "type": "address"},
                {"indexed": false, "name": "reward", "type": "uint256"}
            ],
            "name": "RewardClaimed",
            "type": "event"
        },
        {
            "anonymous": false,
            "inputs": [
                {"indexed": true, "name": "referrer", "type": "address"},
                {"indexed": true, "name": "referee", "type": "address"},
                {"indexed": false, "name": "amount", "type": "uint256"},
                {"indexed": false, "name": "level", "type": "uint8"}
            ],
            "name": "ReferralRewarded",
            "type": "event"
        }
    ]';
    
    public function __construct($rpcUrl, $contractAddress, $conn) {
        $this->web3 = new Web3($rpcUrl);
        $this->contractAddress = $contractAddress;
        $this->conn = $conn;
        $this->contract = new Contract($this->web3->provider, $this->contractABI);
        
        // Get last processed block from database
        $result = $conn->query("SELECT value FROM mlm_system_settings WHERE setting_key = 'last_processed_block'");
        if ($result && $row = $result->fetch_assoc()) {
            $this->lastProcessedBlock = (int)$row['value'];
        } else {
            $this->lastProcessedBlock = 0;
        }
    }
    
    /**
     * Start listening for events
     */
    public function listen() {
        echo "Starting blockchain event listener...\n";
        echo "Contract: {$this->contractAddress}\n";
        echo "Last processed block: {$this->lastProcessedBlock}\n\n";
        
        while (true) {
            try {
                $this->processEvents();
                sleep(15); // Check every 15 seconds
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage() . "\n";
                sleep(30); // Wait longer on error
            }
        }
    }
    
    /**
     * Process blockchain events
     */
    private function processEvents() {
        // Get current block number
        $this->web3->eth->blockNumber(function ($err, $blockNumber) {
            if ($err !== null) {
                throw new Exception("Failed to get block number: " . $err->getMessage());
            }
            
            $currentBlock = hexdec($blockNumber->toString());
            
            if ($currentBlock <= $this->lastProcessedBlock) {
                return; // No new blocks
            }
            
            echo "Processing blocks " . ($this->lastProcessedBlock + 1) . " to $currentBlock\n";
            
            // Get events from last processed block to current
            $this->getEvents($this->lastProcessedBlock + 1, $currentBlock);
            
            // Update last processed block
            $this->updateLastProcessedBlock($currentBlock);
        });
    }
    
    /**
     * Get events from blockchain
     */
    private function getEvents($fromBlock, $toBlock) {
        $filter = [
            'fromBlock' => '0x' . dechex($fromBlock),
            'toBlock' => '0x' . dechex($toBlock),
            'address' => $this->contractAddress
        ];
        
        $this->web3->eth->getLogs($filter, function ($err, $logs) {
            if ($err !== null) {
                echo "Error getting logs: " . $err->getMessage() . "\n";
                return;
            }
            
            foreach ($logs as $log) {
                $this->processLog($log);
            }
        });
    }
    
    /**
     * Process individual log entry
     */
    private function processLog($log) {
        $topics = $log->topics;
        $eventSignature = $topics[0];
        
        // Event signatures (keccak256 hash of event signature)
        $STAKED_SIG = '0x...'; // Update with actual signature
        $UNSTAKED_SIG = '0x...';
        $REWARD_CLAIMED_SIG = '0x...';
        $REFERRAL_REWARDED_SIG = '0x...';
        
        switch ($eventSignature) {
            case $STAKED_SIG:
                $this->handleStakedEvent($log);
                break;
            case $UNSTAKED_SIG:
                $this->handleUnstakedEvent($log);
                break;
            case $REWARD_CLAIMED_SIG:
                $this->handleRewardClaimedEvent($log);
                break;
            case $REFERRAL_REWARDED_SIG:
                $this->handleReferralRewardedEvent($log);
                break;
        }
    }
    
    /**
     * Handle Staked event
     */
    private function handleStakedEvent($log) {
        $user = '0x' . substr($log->topics[1], 26);
        $amount = hexdec($log->data);
        $referrer = isset($log->topics[2]) ? '0x' . substr($log->topics[2], 26) : null;
        $txHash = $log->transactionHash;
        
        echo "Staked: User=$user, Amount=$amount, Referrer=$referrer\n";
        
        // Get user ID from wallet address
        $userId = $this->getUserIdByWallet($user);
        if (!$userId) {
            echo "Warning: User not found for wallet $user\n";
            return;
        }
        
        // Convert wei to tokens (18 decimals)
        $tokenAmount = $amount / pow(10, 18);
        
        // Insert transaction record
        $stmt = $this->conn->prepare("
            INSERT INTO mlm_transactions 
            (user_id, type, amount, status, tx_hash, created_at) 
            VALUES (?, 'STAKE', ?, 'completed', ?, NOW())
        ");
        $stmt->bind_param("ids", $userId, $tokenAmount, $txHash);
        $stmt->execute();
        
        // Update wallet balance
        $this->conn->query("
            UPDATE mlm_wallets 
            SET staked_balance = staked_balance + $tokenAmount 
            WHERE user_id = $userId
        ");
        
        echo "✓ Staked event processed\n";
    }
    
    /**
     * Handle Unstaked event
     */
    private function handleUnstakedEvent($log) {
        $user = '0x' . substr($log->topics[1], 26);
        $amount = hexdec($log->data);
        $txHash = $log->transactionHash;
        
        echo "Unstaked: User=$user, Amount=$amount\n";
        
        $userId = $this->getUserIdByWallet($user);
        if (!$userId) return;
        
        $tokenAmount = $amount / pow(10, 18);
        
        // Insert transaction record
        $stmt = $this->conn->prepare("
            INSERT INTO mlm_transactions 
            (user_id, type, amount, status, tx_hash, created_at) 
            VALUES (?, 'UNSTAKE', ?, 'completed', ?, NOW())
        ");
        $stmt->bind_param("ids", $userId, $tokenAmount, $txHash);
        $stmt->execute();
        
        // Update wallet balance
        $this->conn->query("
            UPDATE mlm_wallets 
            SET staked_balance = staked_balance - $tokenAmount,
                main_wallet = main_wallet + $tokenAmount
            WHERE user_id = $userId
        ");
        
        echo "✓ Unstaked event processed\n";
    }
    
    /**
     * Handle RewardClaimed event
     */
    private function handleRewardClaimedEvent($log) {
        $user = '0x' . substr($log->topics[1], 26);
        $reward = hexdec($log->data);
        $txHash = $log->transactionHash;
        
        echo "Reward Claimed: User=$user, Reward=$reward\n";
        
        $userId = $this->getUserIdByWallet($user);
        if (!$userId) return;
        
        $rewardAmount = $reward / pow(10, 18);
        
        // Insert transaction record
        $stmt = $this->conn->prepare("
            INSERT INTO mlm_transactions 
            (user_id, type, amount, status, tx_hash, created_at) 
            VALUES (?, 'ROI', ?, 'completed', ?, NOW())
        ");
        $stmt->bind_param("ids", $userId, $rewardAmount, $txHash);
        $stmt->execute();
        
        // Update ROI wallet
        $this->conn->query("
            UPDATE mlm_wallets 
            SET roi_wallet = roi_wallet + $rewardAmount 
            WHERE user_id = $userId
        ");
        
        echo "✓ Reward claimed event processed\n";
    }
    
    /**
     * Handle ReferralRewarded event
     */
    private function handleReferralRewardedEvent($log) {
        $referrer = '0x' . substr($log->topics[1], 26);
        $referee = '0x' . substr($log->topics[2], 26);
        $data = substr($log->data, 2); // Remove 0x
        $amount = hexdec(substr($data, 0, 64));
        $level = hexdec(substr($data, 64, 64));
        $txHash = $log->transactionHash;
        
        echo "Referral Reward: Referrer=$referrer, Referee=$referee, Amount=$amount, Level=$level\n";
        
        $referrerId = $this->getUserIdByWallet($referrer);
        $refereeId = $this->getUserIdByWallet($referee);
        
        if (!$referrerId || !$refereeId) return;
        
        $rewardAmount = $amount / pow(10, 18);
        
        // Insert commission record
        $stmt = $this->conn->prepare("
            INSERT INTO mlm_commission_history 
            (user_id, from_user_id, type, amount, level, tx_hash, created_at) 
            VALUES (?, ?, 'referral', ?, ?, ?, NOW())
        ");
        $stmt->bind_param("iidis", $referrerId, $refereeId, $rewardAmount, $level, $txHash);
        $stmt->execute();
        
        // Update referral wallet
        $this->conn->query("
            UPDATE mlm_wallets 
            SET referral_wallet = referral_wallet + $rewardAmount 
            WHERE user_id = $referrerId
        ");
        
        echo "✓ Referral reward event processed\n";
    }
    
    /**
     * Get user ID by wallet address
     */
    private function getUserIdByWallet($walletAddress) {
        $stmt = $this->conn->prepare("
            SELECT id FROM mlm_users WHERE wallet_address = ?
        ");
        $stmt->bind_param("s", $walletAddress);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['id'];
        }
        
        return null;
    }
    
    /**
     * Update last processed block
     */
    private function updateLastProcessedBlock($blockNumber) {
        $this->lastProcessedBlock = $blockNumber;
        
        $stmt = $this->conn->prepare("
            INSERT INTO mlm_system_settings (setting_key, value) 
            VALUES ('last_processed_block', ?) 
            ON DUPLICATE KEY UPDATE value = ?
        ");
        $stmt->bind_param("ss", $blockNumber, $blockNumber);
        $stmt->execute();
    }
}

// Configuration
$RPC_URL = getenv('BSC_RPC_URL') ?: 'https://data-seed-prebsc-1-s1.binance.org:8545';
$CONTRACT_ADDRESS = getenv('CONTRACT_ADDRESS') ?: '0x...'; // Update after deployment

// Start listener
$listener = new BlockchainEventListener($RPC_URL, $CONTRACT_ADDRESS, $conn);
$listener->listen();
