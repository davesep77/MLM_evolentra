# Quick Start Guide - Telegram Bot Setup

## ⚠️ Current Status

✅ Bot code created
✅ Database schema ready
✅ Setup scripts created
❌ Composer not installed (required)
⏳ Waiting for manual steps

---

## 🚀 Quick Setup (Choose One Method)

### Method 1: Automated Setup (Recommended)
Run the setup script:
```cmd
setup_telegram.bat
```

### Method 2: Manual Setup

#### Step 1: Install Composer (One-time)
Download and install from: https://getcomposer.org/Composer-Setup.exe

After installation, restart your terminal and verify:
```cmd
composer --version
```

#### Step 2: Install Bot Dependencies
```cmd
cd d:\xampp\htdocs\MLM_Evolentra
composer install
```

#### Step 3: Update Database
Open phpMyAdmin or MySQL command line:
```sql
USE mlm_system;
SOURCE d:/xampp/htdocs/MLM_Evolentra/setup_telegram_schema.sql;
```

Or use command line:
```cmd
d:\xampp\mysql\bin\mysql -u root mlm_system < setup_telegram_schema.sql
```

#### Step 4: Create Telegram Bot
1. Open Telegram
2. Search: `@BotFather`
3. Send: `/newbot`
4. Name: `Evolentra Support Bot`
5. Username: `evolentra_support_bot` (or any available)
6. **Copy the token!**

#### Step 5: Configure Token
Edit `telegram_bot.php` line 15:
```php
$BOT_TOKEN = 'paste_your_token_here';
```

#### Step 6: Start Bot
```cmd
php telegram_bot.php
```

You should see:
```
Telegram Bot started (polling mode)...
```

#### Step 7: Test Bot
1. Find your bot in Telegram
2. Send: `/start`
3. Send your email to link account
4. Try: `/balance`, `/stats`, `/help`

---

## 🎯 Alternative: Use Without Composer

If you can't install Composer, use this simplified version:

### Create: `telegram_bot_simple.php`
```php
<?php
require_once 'config_db.php';

// Your bot token from BotFather
$botToken = 'YOUR_BOT_TOKEN_HERE';
$apiUrl = "https://api.telegram.org/bot$botToken/";

// Get updates
$offset = 0;
while (true) {
    $updates = file_get_contents($apiUrl . "getUpdates?offset=$offset&timeout=30");
    $updates = json_decode($updates, true);
    
    if (!empty($updates['result'])) {
        foreach ($updates['result'] as $update) {
            $chatId = $update['message']['chat']['id'] ?? null;
            $text = $update['message']['text'] ?? '';
            
            if ($chatId) {
                handleMessage($chatId, $text, $conn, $apiUrl);
            }
            
            $offset = $update['update_id'] + 1;
        }
    }
}

function handleMessage($chatId, $text, $conn, $apiUrl) {
    if ($text == '/start') {
        sendMessage($chatId, "Welcome to Evolentra! Send your email to link account.", $apiUrl);
    }
    elseif ($text == '/balance') {
        $user = getUserByTelegram($chatId, $conn);
        if ($user) {
            $wallet = getWallet($user['id'], $conn);
            $msg = "💰 Balance:\nMain: $" . $wallet['main_wallet'];
            sendMessage($chatId, $msg, $apiUrl);
        } else {
            sendMessage($chatId, "Please link your account first. Send your email.", $apiUrl);
        }
    }
    elseif (filter_var($text, FILTER_VALIDATE_EMAIL)) {
        linkAccount($chatId, $text, $conn, $apiUrl);
    }
    else {
        sendMessage($chatId, "Unknown command. Try /start or /balance", $apiUrl);
    }
}

function sendMessage($chatId, $text, $apiUrl) {
    file_get_contents($apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode($text));
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

function linkAccount($chatId, $email, $conn, $apiUrl) {
    $stmt = $conn->prepare("SELECT id FROM mlm_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if ($user) {
        $stmt = $conn->prepare("UPDATE mlm_users SET telegram_id = ? WHERE id = ?");
        $stmt->bind_param("si", $chatId, $user['id']);
        $stmt->execute();
        sendMessage($chatId, "✅ Account linked! Try /balance", $apiUrl);
    } else {
        sendMessage($chatId, "❌ Email not found. Register first at evolentra.com", $apiUrl);
    }
}
?>
```

Then run:
```cmd
php telegram_bot_simple.php
```

---

## 📋 Checklist

- [ ] Install Composer (or use simple version)
- [ ] Run `composer install` (or skip if using simple version)
- [ ] Update database schema
- [ ] Create bot with @BotFather
- [ ] Copy bot token
- [ ] Set token in code
- [ ] Start bot script
- [ ] Test with /start command
- [ ] Link account with email
- [ ] Test other commands

---

## 🆘 Troubleshooting

### Composer not found?
**Solution**: Download from https://getcomposer.org/Composer-Setup.exe
Or use the simple version above (no Composer needed)

### MySQL command not working?
**Solution**: Use phpMyAdmin:
1. Open http://localhost/phpmyadmin
2. Select `mlm_system` database
3. Click "Import"
4. Choose `setup_telegram_schema.sql`
5. Click "Go"

### Bot not responding?
**Solution**: 
1. Check token is correct
2. Verify bot is running (check console)
3. Try `/start` command again
4. Check for errors in console

### Can't link account?
**Solution**:
1. Use exact email from registration
2. Check email exists in database
3. Make sure telegram_id column exists

---

## 📞 Need Help?

1. Check `TELEGRAM_BOT_README.md` for full documentation
2. Review error messages in console
3. Verify all steps completed
4. Contact support if issues persist

---

**Ready to start?** Run `setup_telegram.bat` or follow manual steps above!
