# Composer Installation & Telegram Bot Setup

## Step 1: Install Composer

### Download Composer
1. **Open your browser**
2. **Go to**: https://getcomposer.org/Composer-Setup.exe
3. **Download** the installer (Composer-Setup.exe)
4. **Run** the installer

### Installation Steps
1. Click "Next" on welcome screen
2. **Developer mode**: Leave unchecked → Click "Next"
3. **Choose PHP**: 
   - Browse to: `d:\xampp\php\php.exe`
   - Click "Next"
4. **Proxy settings**: Leave empty → Click "Next"
5. **Ready to install**: Click "Install"
6. **Finish**: Click "Finish"

### Verify Installation
Open a **NEW** terminal/command prompt and run:
```cmd
composer --version
```

You should see:
```
Composer version 2.x.x
```

---

## Step 2: Install Bot Dependencies

Once Composer is installed, run:

```cmd
cd d:\xampp\htdocs\MLM_Evolentra
composer install
```

This will install:
- ✅ telegram-bot/api (Telegram Bot SDK)
- ✅ guzzlehttp/guzzle (HTTP client)
- ✅ monolog/monolog (Logging)

Expected output:
```
Loading composer repositories with package information
Installing dependencies from lock file
Package operations: 10 installs, 0 updates, 0 removals
  - Installing telegram-bot/api (v2.3.x)
  - Installing guzzlehttp/guzzle (7.x)
  ...
Generating autoload files
```

---

## Step 3: Update Database

### Option A: Using MySQL Command Line
```cmd
d:\xampp\mysql\bin\mysql -u root mlm_system < setup_telegram_schema.sql
```

### Option B: Using phpMyAdmin (Easier)
1. Open: http://localhost/phpmyadmin
2. Click on `mlm_system` database (left sidebar)
3. Click "Import" tab (top menu)
4. Click "Choose File"
5. Select: `d:\xampp\htdocs\MLM_Evolentra\setup_telegram_schema.sql`
6. Click "Go" at bottom
7. Wait for success message

This creates:
- ✅ `telegram_id` column in `mlm_users`
- ✅ `telegram_username` column in `mlm_users`
- ✅ `mlm_telegram_states` table
- ✅ `mlm_telegram_tickets` table
- ✅ `mlm_telegram_notifications` table

---

## Step 4: Create Your Telegram Bot

### Open Telegram App
1. Search for: `@BotFather`
2. Click "Start" or send `/start`

### Create New Bot
Send this command:
```
/newbot
```

### Follow BotFather's Instructions

**BotFather**: "Alright, a new bot. How are we going to call it? Please choose a name for your bot."

**You type**:
```
Evolentra Support Bot
```

**BotFather**: "Good. Now let's choose a username for your bot. It must end in `bot`. Like this, for example: TetrisBot or tetris_bot."

**You type** (choose one):
```
evolentra_support_bot
```
or
```
EvolentraBot
```
or
```
evolentra_mlm_bot
```

**BotFather will respond**:
```
Done! Congratulations on your new bot. You will find it at t.me/evolentra_support_bot

Use this token to access the HTTP API:
123456789:ABCdefGHIjklMNOpqrsTUVwxyz-1234567890

For a description of the Bot API, see this page: https://core.telegram.org/bots/api
```

### ⚠️ IMPORTANT: Copy Your Token!
The token looks like: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz-1234567890`

**Save it securely!** You'll need it in the next step.

---

## Step 5: Configure Bot Token

### Edit telegram_bot.php

1. Open: `d:\xampp\htdocs\MLM_Evolentra\telegram_bot.php`
2. Find line 15 (around line 15):
```php
$BOT_TOKEN = getenv('TELEGRAM_BOT_TOKEN') ?: 'YOUR_BOT_TOKEN_HERE';
```

3. Replace with your actual token:
```php
$BOT_TOKEN = '123456789:ABCdefGHIjklMNOpqrsTUVwxyz-1234567890';
```

4. Save the file

### Alternative: Use Environment Variable
```cmd
set TELEGRAM_BOT_TOKEN=123456789:ABCdefGHIjklMNOpqrsTUVwxyz-1234567890
```

---

## Step 6: Start the Bot

Open terminal and run:
```cmd
cd d:\xampp\htdocs\MLM_Evolentra
php telegram_bot.php
```

You should see:
```
Telegram Bot started (polling mode)...
```

**Keep this terminal window open!** The bot is now running.

---

## Step 7: Test Your Bot

### Find Your Bot in Telegram
1. Open Telegram app
2. Search for your bot username (e.g., `@evolentra_support_bot`)
3. Click on it
4. Click "Start" button

### Test Commands

**Send**: `/start`

**Expected Response**:
```
🎉 Welcome to Evolentra, [Your Name]!

I'm your personal assistant for managing your MLM account.

🔹 Check balances
🔹 View statistics
🔹 Get referral links
🔹 Request support
🔹 Manage staking

Choose an option below or type /help for commands.
```

### Link Your Account

**Send**: `your_email@example.com` (your registered email)

**Expected Response**:
```
✅ Account linked successfully! You can now use all bot features. Type /help to get started.
```

### Try Other Commands

- `/balance` - Check your wallet balances
- `/stats` - View account statistics
- `/referral` - Get your referral link
- `/help` - See all available commands
- `/support` - Contact support
- `/transactions` - View recent transactions

---

## 🎉 Success Checklist

- [ ] Composer installed and verified
- [ ] Dependencies installed (`composer install`)
- [ ] Database updated (via phpMyAdmin or MySQL)
- [ ] Bot created with @BotFather
- [ ] Bot token copied and saved
- [ ] Token configured in `telegram_bot.php`
- [ ] Bot started (`php telegram_bot.php`)
- [ ] Bot responding in Telegram
- [ ] Account linked with email
- [ ] Commands working (`/balance`, `/stats`, etc.)

---

## 🔧 Troubleshooting

### Composer not found after installation?
**Solution**: Close and reopen your terminal, then try again

### "composer install" fails?
**Solution**: 
1. Check internet connection
2. Try: `composer install --ignore-platform-reqs`
3. Or download dependencies manually

### Bot not responding?
**Solution**:
1. Check token is correct (no extra spaces)
2. Verify bot is running (check terminal)
3. Try `/start` command again
4. Check for errors in terminal output

### "Account not found" when linking?
**Solution**:
1. Use exact email from registration
2. Check email exists: Login to website first
3. Verify database has your email

### Database import failed?
**Solution**:
1. Use phpMyAdmin instead
2. Check MySQL is running
3. Verify database name is `mlm_system`

---

## 📞 Next Steps After Setup

### 1. Keep Bot Running
For production, use:
- **Windows Service**: Use NSSM
- **Linux**: Use systemd or supervisor
- **Webhook**: Set up webhook for better reliability

### 2. Customize Bot
Edit `telegram_bot.php` to:
- Change welcome message
- Add custom commands
- Modify response text

### 3. Enable Notifications
Set up automated notifications for:
- New deposits
- Withdrawals approved
- Referral signups
- Daily summaries

### 4. Monitor Usage
Check logs regularly:
```sql
SELECT * FROM mlm_admin_logs 
WHERE action = 'telegram_interaction' 
ORDER BY created_at DESC LIMIT 20;
```

---

## 🚀 You're All Set!

Your Telegram bot is now ready to serve your users 24/7!

**Bot Features Available**:
✅ Balance checking
✅ Statistics viewing
✅ Referral link sharing
✅ Support ticket creation
✅ Transaction history
✅ Staking information
✅ Real-time notifications

**Need help?** Check `TELEGRAM_BOT_README.md` for full documentation.
