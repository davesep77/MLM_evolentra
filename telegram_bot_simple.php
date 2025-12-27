<?php
/**
 * Evolentra Telegram Bot - Simplified Version
 * No Composer required!
 */

require_once __DIR__ . '/config_db.php';

// ============================================
// CONFIGURATION
// ============================================
$BOT_TOKEN = '8508685878:AAEowOz0CqDoII_S7iipi_ENs8TvzEXRlcY'; // Your bot token from @BotFather
$API_URL = "https://api.telegram.org/bot{$BOT_TOKEN}/";

// ============================================
// MAIN BOT LOOP
// ============================================
echo "Telegram Bot Started!\n";
echo "Waiting for messages...\n\n";

$offset = 0;
while (true) {
    $updates = getUpdates($offset);
    
    if (!empty($updates['result'])) {
        foreach ($updates['result'] as $update) {
            processUpdate($update);
            $offset = $update['update_id'] + 1;
        }
    }
    
    sleep(1); // Wait 1 second between checks
}

// ============================================
// FUNCTIONS
// ============================================

function getUpdates($offset) {
    global $API_URL;
    $url = $API_URL . "getUpdates?offset={$offset}&timeout=30";
    $response = file_get_contents($url);
    return json_decode($response, true);
}

function sendMessage($chatId, $text, $keyboard = null) {
    global $API_URL;
    
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'Markdown'
    ];
    
    if ($keyboard) {
        $data['reply_markup'] = json_encode($keyboard);
    }
    
    $url = $API_URL . "sendMessage?" . http_build_query($data);
    file_get_contents($url);
}

function processUpdate($update) {
    if (!isset($update['message'])) return;
    
    $message = $update['message'];
    $chatId = $message['chat']['id'];
    $text = $message['text'] ?? '';
    $firstName = $message['from']['first_name'] ?? 'User';
    
    echo "[" . date('H:i:s') . "] Message from $firstName: $text\n";
    
    // Handle commands
    if (strpos($text, '/') === 0) {
        handleCommand($chatId, $text, $firstName);
    } else {
        handleMessage($chatId, $text);
    }
}

function handleCommand($chatId, $command, $firstName) {
    global $conn;
    
    switch ($command) {
        case '/start':
            $keyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '💰 Check Balance', 'callback_data' => 'balance'],
                        ['text' => '📊 My Stats', 'callback_data' => 'stats']
                    ],
                    [
                        ['text' => '🎁 Referral Link', 'callback_data' => 'referral'],
                        ['text' => '💬 Support', 'callback_data' => 'support']
                    ]
                ]
            ];
            
            $msg = "🎉 *Welcome to Evolentra, $firstName!*\n\n";
            $msg .= "I'm your personal assistant.\n\n";
            $msg .= "🔹 Check balances\n";
            $msg .= "🔹 View statistics\n";
            $msg .= "🔹 Get referral links\n";
            $msg .= "🔹 Request support\n\n";
            $msg .= "📧 *To get started, send me your registered email address.*";
            
            sendMessage($chatId, $msg, $keyboard);
            break;
            
        case '/help':
            $msg = "📚 *Available Commands:*\n\n";
            $msg .= "`/start` - Start bot\n";
            $msg .= "`/balance` - Check balances\n";
            $msg .= "`/stats` - View statistics\n";
            $msg .= "`/referral` - Get referral link\n";
            $msg .= "`/transactions` - Recent transactions\n";
            $msg .= "`/support` - Contact support\n";
            $msg .= "`/help` - Show this message\n\n";
            $msg .= "💡 Send your email to link your account!";
            
            sendMessage($chatId, $msg);
            break;
            
        case '/balance':
            $user = getUserByTelegram($chatId, $conn);
            if (!$user) {
                sendMessage($chatId, "❌ Please link your account first.\nSend me your registered email.");
                return;
            }
            
            $wallet = getWallet($user['id'], $conn);
            
            $msg = "💰 *Your Wallet Balances*\n\n";
            $msg .= "🏦 Main Wallet: $" . number_format($wallet['main_wallet'], 2) . "\n";
            $msg .= "📈 ROI Wallet: $" . number_format($wallet['roi_wallet'], 2) . "\n";
            $msg .= "👥 Referral Wallet: $" . number_format($wallet['referral_wallet'], 2) . "\n";
            $msg .= "🌳 Binary Wallet: $" . number_format($wallet['binary_wallet'], 2) . "\n\n";
            $total = $wallet['main_wallet'] + $wallet['roi_wallet'] + $wallet['referral_wallet'] + $wallet['binary_wallet'];
            $msg .= "💵 *Total: $" . number_format($total, 2) . "*";
            
            sendMessage($chatId, $msg);
            break;
            
        case '/stats':
            $user = getUserByTelegram($chatId, $conn);
            if (!$user) {
                sendMessage($chatId, "❌ Please link your account first.\nSend me your registered email.");
                return;
            }
            
            $msg = "📊 *Account Statistics*\n\n";
            $msg .= "👤 Name: " . $user['full_name'] . "\n";
            $msg .= "📧 Email: " . $user['email'] . "\n";
            $msg .= "💼 Investment: $" . number_format($user['investment'], 2) . "\n";
            $msg .= "🏆 Rank: " . ($user['rank'] ?: 'Starter') . "\n";
            $msg .= "📅 Member Since: " . date('M d, Y', strtotime($user['created_at']));
            
            sendMessage($chatId, $msg);
            break;
            
        case '/referral':
            $user = getUserByTelegram($chatId, $conn);
            if (!$user) {
                sendMessage($chatId, "❌ Please link your account first.\nSend me your registered email.");
                return;
            }
            
            $refCode = $user['referral_code'];
            $refLink = "https://evolentra.com/register?ref=$refCode";
            
            $msg = "🎁 *Your Referral Program*\n\n";
            $msg .= "📎 *Referral Link:*\n";
            $msg .= "`$refLink`\n\n";
            $msg .= "🔑 *Referral Code:* `$refCode`\n\n";
            $msg .= "Share and earn:\n";
            $msg .= "• 10% Direct Bonus\n";
            $msg .= "• 5% Indirect Bonus\n";
            $msg .= "• Binary Matching";
            
            sendMessage($chatId, $msg);
            break;
            
        case '/transactions':
            $user = getUserByTelegram($chatId, $conn);
            if (!$user) {
                sendMessage($chatId, "❌ Please link your account first.\nSend me your registered email.");
                return;
            }
            
            $txs = getRecentTransactions($user['id'], $conn);
            
            $msg = "📜 *Recent Transactions*\n\n";
            if (empty($txs)) {
                $msg .= "No transactions found.";
            } else {
                foreach ($txs as $tx) {
                    $msg .= "• *" . $tx['type'] . "*\n";
                    $msg .= "  Amount: $" . number_format($tx['amount'], 2) . "\n";
                    $msg .= "  Date: " . date('M d, H:i', strtotime($tx['created_at'])) . "\n\n";
                }
            }
            
            sendMessage($chatId, $msg);
            break;
            
        case '/support':
            $msg = "💬 *Support Center*\n\n";
            $msg .= "Need help? Contact us:\n\n";
            $msg .= "📧 Email: support@evolentra.com\n";
            $msg .= "🌐 Website: https://evolentra.com/support\n\n";
            $msg .= "Or describe your issue here and we'll create a ticket!";
            
            sendMessage($chatId, $msg);
            break;
            
        default:
            sendMessage($chatId, "Unknown command. Type /help for available commands.");
    }
}

function handleMessage($chatId, $text) {
    global $conn;
    
    // Check if it's an email (account linking)
    if (filter_var($text, FILTER_VALIDATE_EMAIL)) {
        linkAccount($chatId, $text, $conn);
        return;
    }
    
    sendMessage($chatId, "I didn't understand that. Type /help for commands or send your email to link your account.");
}

function linkAccount($chatId, $email, $conn) {
    $stmt = $conn->prepare("SELECT id, full_name FROM mlm_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        $stmt = $conn->prepare("UPDATE mlm_users SET telegram_id = ? WHERE id = ?");
        $stmt->bind_param("si", $chatId, $user['id']);
        $stmt->execute();
        
        sendMessage($chatId, "✅ *Account Linked Successfully!*\n\nWelcome, " . $user['full_name'] . "!\n\nYou can now use all bot features.\nType /help to see available commands.");
    } else {
        sendMessage($chatId, "❌ *Email Not Found*\n\nPlease register at https://evolentra.com first, then send your registered email here.");
    }
}

function getUserByTelegram($telegramId, $conn) {
    $stmt = $conn->prepare("SELECT * FROM mlm_users WHERE telegram_id = ?");
    $stmt->bind_param("s", $telegramId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getWallet($userId, $conn) {
    $stmt = $conn->prepare("SELECT * FROM mlm_wallets WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getRecentTransactions($userId, $conn) {
    $stmt = $conn->prepare("
        SELECT * FROM mlm_transactions 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $txs = [];
    while ($row = $result->fetch_assoc()) {
        $txs[] = $row;
    }
    return $txs;
}
