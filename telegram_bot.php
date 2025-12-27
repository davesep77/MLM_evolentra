<?php
/**
 * Evolentra Telegram Support Bot
 * Handles user support, account queries, and platform interactions
 */

require_once __DIR__ . '/config_db.php';
require_once __DIR__ . '/vendor/autoload.php';

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Update;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class EvolentraTelegramBot {
    private $bot;
    private $conn;
    private $botToken;
    
    // Bot commands
    private $commands = [
        '/start' => 'Start interaction with the bot',
        '/help' => 'Show available commands',
        '/balance' => 'Check your wallet balances',
        '/stats' => 'View your account statistics',
        '/referral' => 'Get your referral link',
        '/support' => 'Contact support team',
        '/stake' => 'View staking information',
        '/withdraw' => 'Request withdrawal',
        '/transactions' => 'View recent transactions',
        '/settings' => 'Bot settings'
    ];
    
    public function __construct($botToken, $conn) {
        $this->botToken = $botToken;
        $this->conn = $conn;
        $this->bot = new BotApi($botToken);
    }
    
    /**
     * Start the bot (webhook or polling)
     */
    public function start($useWebhook = false) {
        if ($useWebhook) {
            $this->handleWebhook();
        } else {
            $this->startPolling();
        }
    }
    
    /**
     * Handle webhook updates
     */
    public function handleWebhook() {
        $content = file_get_contents('php://input');
        $update = json_decode($content, true);
        
        if ($update) {
            $this->processUpdate($update);
        }
    }
    
    /**
     * Start polling for updates
     */
    public function startPolling() {
        echo "Telegram Bot started (polling mode)...\n";
        
        $offset = 0;
        while (true) {
            try {
                $updates = $this->bot->getUpdates($offset, 100, 60);
                
                foreach ($updates as $update) {
                    $this->processUpdate($update);
                    $offset = $update->getUpdateId() + 1;
                }
                
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage() . "\n";
                sleep(5);
            }
        }
    }
    
    /**
     * Process incoming update
     */
    private function processUpdate($update) {
        if (is_array($update)) {
            $update = json_decode(json_encode($update));
        }
        
        // Handle callback queries (inline button clicks)
        if (isset($update->callback_query)) {
            $this->handleCallbackQuery($update->callback_query);
            return;
        }
        
        // Handle messages
        if (!isset($update->message)) {
            return;
        }
        
        $message = $update->message;
        $chatId = $message->chat->id;
        $text = $message->text ?? '';
        
        // Log interaction
        $this->logInteraction($chatId, $text);
        
        // Handle commands
        if (strpos($text, '/') === 0) {
            $this->handleCommand($chatId, $text, $message);
        } else {
            $this->handleMessage($chatId, $text, $message);
        }
    }
    
    /**
     * Handle bot commands
     */
    private function handleCommand($chatId, $command, $message) {
        $parts = explode(' ', $command);
        $cmd = $parts[0];
        
        switch ($cmd) {
            case '/start':
                $this->cmdStart($chatId, $message);
                break;
            case '/help':
                $this->cmdHelp($chatId);
                break;
            case '/balance':
                $this->cmdBalance($chatId);
                break;
            case '/stats':
                $this->cmdStats($chatId);
                break;
            case '/referral':
                $this->cmdReferral($chatId);
                break;
            case '/support':
                $this->cmdSupport($chatId);
                break;
            case '/stake':
                $this->cmdStake($chatId);
                break;
            case '/withdraw':
                $this->cmdWithdraw($chatId);
                break;
            case '/transactions':
                $this->cmdTransactions($chatId);
                break;
            case '/settings':
                $this->cmdSettings($chatId);
                break;
            default:
                $this->sendMessage($chatId, "Unknown command. Type /help for available commands.");
        }
    }
    
    /**
     * /start command
     */
    private function cmdStart($chatId, $message) {
        $firstName = $message->from->first_name ?? 'User';
        
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => '🔗 Link Account', 'callback_data' => 'link_account'],
                ['text' => '💰 Check Balance', 'callback_data' => 'balance']
            ],
            [
                ['text' => '📊 My Stats', 'callback_data' => 'stats'],
                ['text' => '🎁 Referral Link', 'callback_data' => 'referral']
            ],
            [
                ['text' => '💬 Support', 'callback_data' => 'support'],
                ['text' => '⚙️ Settings', 'callback_data' => 'settings']
            ]
        ]);
        
        $welcomeMsg = "🎉 Welcome to Evolentra, $firstName!\n\n";
        $welcomeMsg .= "I'm your personal assistant for managing your MLM account.\n\n";
        $welcomeMsg .= "🔹 Check balances\n";
        $welcomeMsg .= "🔹 View statistics\n";
        $welcomeMsg .= "🔹 Get referral links\n";
        $welcomeMsg .= "🔹 Request support\n";
        $welcomeMsg .= "🔹 Manage staking\n\n";
        $welcomeMsg .= "Choose an option below or type /help for commands.";
        
        $this->bot->sendMessage($chatId, $welcomeMsg, null, false, null, $keyboard);
    }
    
    /**
     * /help command
     */
    private function cmdHelp($chatId) {
        $helpMsg = "📚 *Available Commands:*\n\n";
        
        foreach ($this->commands as $cmd => $desc) {
            $helpMsg .= "`$cmd` - $desc\n";
        }
        
        $helpMsg .= "\n💡 *Quick Actions:*\n";
        $helpMsg .= "• Send your email to link your account\n";
        $helpMsg .= "• Use inline buttons for quick access\n";
        $helpMsg .= "• Contact support anytime with /support\n";
        
        $this->bot->sendMessage($chatId, $helpMsg, 'Markdown');
    }
    
    /**
     * /balance command
     */
    private function cmdBalance($chatId) {
        $user = $this->getUserByTelegramId($chatId);
        
        if (!$user) {
            $this->sendLinkAccountMessage($chatId);
            return;
        }
        
        $wallet = $this->getUserWallet($user['id']);
        
        $balanceMsg = "💰 *Your Wallet Balances*\n\n";
        $balanceMsg .= "🏦 Main Wallet: $" . number_format($wallet['main_wallet'], 2) . "\n";
        $balanceMsg .= "📈 ROI Wallet: $" . number_format($wallet['roi_wallet'], 2) . "\n";
        $balanceMsg .= "👥 Referral Wallet: $" . number_format($wallet['referral_wallet'], 2) . "\n";
        $balanceMsg .= "🌳 Binary Wallet: $" . number_format($wallet['binary_wallet'], 2) . "\n";
        $balanceMsg .= "🔒 Staked Balance: $" . number_format($wallet['staked_balance'], 2) . "\n\n";
        $balanceMsg .= "💵 *Total Balance:* $" . number_format(
            $wallet['main_wallet'] + $wallet['roi_wallet'] + 
            $wallet['referral_wallet'] + $wallet['binary_wallet'], 2
        );
        
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => '💸 Withdraw', 'callback_data' => 'withdraw'],
                ['text' => '🔄 Refresh', 'callback_data' => 'balance']
            ]
        ]);
        
        $this->bot->sendMessage($chatId, $balanceMsg, 'Markdown', false, null, $keyboard);
    }
    
    /**
     * /stats command
     */
    private function cmdStats($chatId) {
        $user = $this->getUserByTelegramId($chatId);
        
        if (!$user) {
            $this->sendLinkAccountMessage($chatId);
            return;
        }
        
        $stats = $this->getUserStats($user['id']);
        
        $statsMsg = "📊 *Account Statistics*\n\n";
        $statsMsg .= "👤 *Profile*\n";
        $statsMsg .= "Name: " . $user['full_name'] . "\n";
        $statsMsg .= "Email: " . $user['email'] . "\n";
        $statsMsg .= "Rank: " . ($user['rank'] ?: 'Starter') . "\n";
        $statsMsg .= "Member Since: " . date('M d, Y', strtotime($user['created_at'])) . "\n\n";
        
        $statsMsg .= "💼 *Investment*\n";
        $statsMsg .= "Total Investment: $" . number_format($user['investment'], 2) . "\n";
        $statsMsg .= "Active Package: " . $this->getPackageName($user['investment']) . "\n\n";
        
        $statsMsg .= "👥 *Team*\n";
        $statsMsg .= "Direct Referrals: " . $stats['direct_referrals'] . "\n";
        $statsMsg .= "Total Team: " . $stats['total_team'] . "\n";
        $statsMsg .= "Team Volume: $" . number_format($stats['team_volume'], 2) . "\n\n";
        
        $statsMsg .= "💰 *Earnings*\n";
        $statsMsg .= "Total Earned: $" . number_format($stats['total_earned'], 2) . "\n";
        $statsMsg .= "This Month: $" . number_format($stats['month_earned'], 2) . "\n";
        
        $this->bot->sendMessage($chatId, $statsMsg, 'Markdown');
    }
    
    /**
     * /referral command
     */
    private function cmdReferral($chatId) {
        $user = $this->getUserByTelegramId($chatId);
        
        if (!$user) {
            $this->sendLinkAccountMessage($chatId);
            return;
        }
        
        $referralCode = $user['referral_code'];
        $referralLink = "https://evolentra.com/register?ref=$referralCode";
        
        $refMsg = "🎁 *Your Referral Program*\n\n";
        $refMsg .= "Share your link and earn:\n";
        $refMsg .= "• 10% Direct Referral Bonus\n";
        $refMsg .= "• 5% Indirect Referral Bonus\n";
        $refMsg .= "• Binary Matching Bonus\n\n";
        $refMsg .= "📎 *Your Referral Link:*\n";
        $refMsg .= "`$referralLink`\n\n";
        $refMsg .= "🔑 *Referral Code:* `$referralCode`\n\n";
        $refMsg .= "Share this link with friends and family!";
        
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => '📤 Share Link', 'url' => "https://t.me/share/url?url=$referralLink&text=Join Evolentra and start earning!"]
            ]
        ]);
        
        $this->bot->sendMessage($chatId, $refMsg, 'Markdown', false, null, $keyboard);
    }
    
    /**
     * /support command
     */
    private function cmdSupport($chatId) {
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => '📝 Create Ticket', 'callback_data' => 'create_ticket'],
                ['text' => '📋 My Tickets', 'callback_data' => 'view_tickets']
            ],
            [
                ['text' => '❓ FAQ', 'callback_data' => 'faq'],
                ['text' => '📞 Contact Admin', 'callback_data' => 'contact_admin']
            ]
        ]);
        
        $supportMsg = "💬 *Support Center*\n\n";
        $supportMsg .= "How can we help you today?\n\n";
        $supportMsg .= "🔹 Create a support ticket\n";
        $supportMsg .= "🔹 View your tickets\n";
        $supportMsg .= "🔹 Check FAQ\n";
        $supportMsg .= "🔹 Contact admin directly\n\n";
        $supportMsg .= "Choose an option below:";
        
        $this->bot->sendMessage($chatId, $supportMsg, 'Markdown', false, null, $keyboard);
    }
    
    /**
     * /stake command
     */
    private function cmdStake($chatId) {
        $user = $this->getUserByTelegramId($chatId);
        
        if (!$user) {
            $this->sendLinkAccountMessage($chatId);
            return;
        }
        
        $stakeInfo = $this->getStakeInfo($user['wallet_address']);
        
        $stakeMsg = "🔒 *BSC Staking Dashboard*\n\n";
        $stakeMsg .= "💎 Staked Amount: " . number_format($stakeInfo['staked'], 2) . " EVOL\n";
        $stakeMsg .= "🎁 Pending Rewards: " . number_format($stakeInfo['pending'], 4) . " EVOL\n";
        $stakeMsg .= "📅 Staking Since: " . $stakeInfo['since'] . "\n";
        $stakeMsg .= "📈 Daily Rate: 1.2%\n\n";
        $stakeMsg .= "💰 Total Earned: " . number_format($stakeInfo['total_earned'], 2) . " EVOL\n";
        
        $keyboard = new InlineKeyboardMarkup([
            [
                ['text' => '➕ Stake More', 'url' => 'https://evolentra.com/bsc_staking.php'],
                ['text' => '🎁 Claim Rewards', 'callback_data' => 'claim_rewards']
            ]
        ]);
        
        $this->bot->sendMessage($chatId, $stakeMsg, 'Markdown', false, null, $keyboard);
    }
    
    /**
     * /transactions command
     */
    private function cmdTransactions($chatId) {
        $user = $this->getUserByTelegramId($chatId);
        
        if (!$user) {
            $this->sendLinkAccountMessage($chatId);
            return;
        }
        
        $transactions = $this->getRecentTransactions($user['id'], 10);
        
        $txMsg = "📜 *Recent Transactions*\n\n";
        
        if (empty($transactions)) {
            $txMsg .= "No transactions found.";
        } else {
            foreach ($transactions as $tx) {
                $icon = $this->getTransactionIcon($tx['type']);
                $txMsg .= "$icon *" . $tx['type'] . "*\n";
                $txMsg .= "Amount: $" . number_format($tx['amount'], 2) . "\n";
                $txMsg .= "Date: " . date('M d, H:i', strtotime($tx['created_at'])) . "\n";
                $txMsg .= "Status: " . ucfirst($tx['status']) . "\n\n";
            }
        }
        
        $this->bot->sendMessage($chatId, $txMsg, 'Markdown');
    }
    
    /**
     * Handle callback queries
     */
    private function handleCallbackQuery($callbackQuery) {
        $chatId = $callbackQuery->message->chat->id;
        $data = $callbackQuery->data;
        $messageId = $callbackQuery->message->message_id;
        
        // Answer callback to remove loading state
        $this->bot->answerCallbackQuery($callbackQuery->id);
        
        switch ($data) {
            case 'link_account':
                $this->sendLinkAccountMessage($chatId);
                break;
            case 'balance':
                $this->cmdBalance($chatId);
                break;
            case 'stats':
                $this->cmdStats($chatId);
                break;
            case 'referral':
                $this->cmdReferral($chatId);
                break;
            case 'support':
                $this->cmdSupport($chatId);
                break;
            case 'settings':
                $this->cmdSettings($chatId);
                break;
            case 'withdraw':
                $this->handleWithdrawRequest($chatId);
                break;
            case 'create_ticket':
                $this->handleCreateTicket($chatId);
                break;
            case 'view_tickets':
                $this->handleViewTickets($chatId);
                break;
            case 'faq':
                $this->handleFAQ($chatId);
                break;
        }
    }
    
    /**
     * Handle regular messages
     */
    private function handleMessage($chatId, $text, $message) {
        // Check if user is linking account
        if (filter_var($text, FILTER_VALIDATE_EMAIL)) {
            $this->linkAccountByEmail($chatId, $text, $message);
            return;
        }
        
        // Check if user is creating support ticket
        $user = $this->getUserByTelegramId($chatId);
        if ($user && $this->isCreatingTicket($chatId)) {
            $this->createSupportTicket($chatId, $text, $user['id']);
            return;
        }
        
        // Default response
        $this->sendMessage($chatId, "I didn't understand that. Type /help for available commands.");
    }
    
    /**
     * Link account by email
     */
    private function linkAccountByEmail($chatId, $email, $message) {
        $stmt = $this->conn->prepare("SELECT id FROM mlm_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Update user with telegram ID
            $telegramUsername = $message->from->username ?? '';
            $stmt = $this->conn->prepare("
                UPDATE mlm_users 
                SET telegram_id = ?, telegram_username = ? 
                WHERE id = ?
            ");
            $stmt->bind_param("ssi", $chatId, $telegramUsername, $row['id']);
            $stmt->execute();
            
            $this->sendMessage($chatId, "✅ Account linked successfully! You can now use all bot features. Type /help to get started.");
        } else {
            $this->sendMessage($chatId, "❌ No account found with this email. Please register at https://evolentra.com first.");
        }
    }
    
    /**
     * Send link account message
     */
    private function sendLinkAccountMessage($chatId) {
        $msg = "🔗 *Link Your Account*\n\n";
        $msg .= "To use this bot, please link your Evolentra account.\n\n";
        $msg .= "Simply send me your registered email address.";
        
        $this->bot->sendMessage($chatId, $msg, 'Markdown');
    }
    
    /**
     * Get user by telegram ID
     */
    private function getUserByTelegramId($telegramId) {
        $stmt = $this->conn->prepare("SELECT * FROM mlm_users WHERE telegram_id = ?");
        $stmt->bind_param("s", $telegramId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Get user wallet
     */
    private function getUserWallet($userId) {
        $stmt = $this->conn->prepare("SELECT * FROM mlm_wallets WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Get user statistics
     */
    private function getUserStats($userId) {
        // Direct referrals
        $directQuery = $this->conn->query("
            SELECT COUNT(*) as count FROM mlm_users WHERE sponsor_id = $userId
        ");
        $directCount = $directQuery->fetch_assoc()['count'];
        
        // Total team (simplified - you may want recursive query)
        $teamQuery = $this->conn->query("
            SELECT COUNT(*) as count FROM mlm_users 
            WHERE sponsor_id = $userId OR sponsor_id IN (
                SELECT id FROM mlm_users WHERE sponsor_id = $userId
            )
        ");
        $teamCount = $teamQuery->fetch_assoc()['count'];
        
        // Total earned
        $earningsQuery = $this->conn->query("
            SELECT SUM(amount) as total FROM mlm_transactions 
            WHERE user_id = $userId AND type IN ('ROI', 'REFERRAL', 'BINARY')
        ");
        $totalEarned = $earningsQuery->fetch_assoc()['total'] ?: 0;
        
        // This month earnings
        $monthQuery = $this->conn->query("
            SELECT SUM(amount) as total FROM mlm_transactions 
            WHERE user_id = $userId 
            AND type IN ('ROI', 'REFERRAL', 'BINARY')
            AND MONTH(created_at) = MONTH(NOW())
            AND YEAR(created_at) = YEAR(NOW())
        ");
        $monthEarned = $monthQuery->fetch_assoc()['total'] ?: 0;
        
        return [
            'direct_referrals' => $directCount,
            'total_team' => $teamCount,
            'team_volume' => 0, // Calculate based on your logic
            'total_earned' => $totalEarned,
            'month_earned' => $monthEarned
        ];
    }
    
    /**
     * Helper functions
     */
    private function sendMessage($chatId, $text, $parseMode = null) {
        try {
            $this->bot->sendMessage($chatId, $text, $parseMode);
        } catch (Exception $e) {
            error_log("Telegram send error: " . $e->getMessage());
        }
    }
    
    private function logInteraction($chatId, $message) {
        $stmt = $this->conn->prepare("
            INSERT INTO mlm_admin_logs (action, description, created_at) 
            VALUES ('telegram_interaction', ?, NOW())
        ");
        $desc = "Chat ID: $chatId, Message: $message";
        $stmt->bind_param("s", $desc);
        $stmt->execute();
    }
    
    private function getPackageName($investment) {
        if ($investment >= 25001) return 'TERRA';
        if ($investment >= 5001) return 'RISE';
        if ($investment >= 500) return 'ROOT';
        return 'None';
    }
    
    private function getTransactionIcon($type) {
        $icons = [
            'DEPOSIT' => '💰',
            'WITHDRAWAL' => '💸',
            'ROI' => '📈',
            'REFERRAL' => '👥',
            'BINARY' => '🌳',
            'STAKE' => '🔒',
            'UNSTAKE' => '🔓'
        ];
        return $icons[$type] ?? '💵';
    }
    
    private function getStakeInfo($walletAddress) {
        // This would query blockchain or database
        return [
            'staked' => 0,
            'pending' => 0,
            'since' => 'Not staking',
            'total_earned' => 0
        ];
    }
    
    private function getRecentTransactions($userId, $limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT * FROM mlm_transactions 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
        return $transactions;
    }
    
    private function cmdSettings($chatId) {
        $msg = "⚙️ *Bot Settings*\n\n";
        $msg .= "Configure your notification preferences:\n\n";
        $msg .= "🔔 Notifications: Enabled\n";
        $msg .= "📊 Daily Summary: Enabled\n";
        $msg .= "💰 Balance Alerts: Enabled\n";
        
        $this->bot->sendMessage($chatId, $msg, 'Markdown');
    }
    
    private function handleWithdrawRequest($chatId) {
        $msg = "💸 *Withdrawal Request*\n\n";
        $msg .= "To request a withdrawal, please visit:\n";
        $msg .= "https://evolentra.com/withdraw.php\n\n";
        $msg .= "Or contact support for assistance.";
        
        $this->bot->sendMessage($chatId, $msg, 'Markdown');
    }
    
    private function handleCreateTicket($chatId) {
        $msg = "📝 *Create Support Ticket*\n\n";
        $msg .= "Please describe your issue in detail.\n";
        $msg .= "Send your message and we'll create a ticket for you.";
        
        // Set flag that user is creating ticket
        $this->setUserState($chatId, 'creating_ticket');
        
        $this->bot->sendMessage($chatId, $msg, 'Markdown');
    }
    
    private function handleViewTickets($chatId) {
        $user = $this->getUserByTelegramId($chatId);
        if (!$user) return;
        
        $msg = "📋 *Your Support Tickets*\n\n";
        $msg .= "Visit https://evolentra.com/support.php to view all tickets.";
        
        $this->bot->sendMessage($chatId, $msg, 'Markdown');
    }
    
    private function handleFAQ($chatId) {
        $msg = "❓ *Frequently Asked Questions*\n\n";
        $msg .= "*Q: How do I deposit?*\n";
        $msg .= "A: Visit invest.php and choose Binance Pay or manual transfer.\n\n";
        $msg .= "*Q: When are ROI payments?*\n";
        $msg .= "A: Daily at 1.2% of your investment.\n\n";
        $msg .= "*Q: How do referrals work?*\n";
        $msg .= "A: Share your link and earn 10% direct + 5% indirect.\n\n";
        $msg .= "More questions? Type /support";
        
        $this->bot->sendMessage($chatId, $msg, 'Markdown');
    }
    
    private function isCreatingTicket($chatId) {
        // Check user state from cache/database
        return false; // Implement state management
    }
    
    private function setUserState($chatId, $state) {
        // Store user state in cache/database
    }
    
    private function createSupportTicket($chatId, $message, $userId) {
        // Create ticket in database
        $this->sendMessage($chatId, "✅ Support ticket created! Our team will respond soon.");
    }
}

// Configuration
$BOT_TOKEN = getenv('TELEGRAM_BOT_TOKEN') ?: 'YOUR_BOT_TOKEN_HERE';

// Start bot
$bot = new EvolentraTelegramBot($BOT_TOKEN, $conn);
$bot->start(false); // false = polling, true = webhook
