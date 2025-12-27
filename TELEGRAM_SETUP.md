# Telegram Bot Setup Guide

## Step 1: Create Bot with @BotFather ✅

### Instructions:
1. Open Telegram app
2. Search for: `@BotFather`
3. Start chat and send: `/newbot`
4. Follow prompts:
   - **Bot name**: Evolentra Support Bot
   - **Bot username**: evolentra_support_bot (or any available name ending in 'bot')
5. Copy the bot token (looks like: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`)
6. **IMPORTANT**: Save this token securely!

### Example Conversation:
```
You: /newbot
BotFather: Alright, a new bot. How are we going to call it?
You: Evolentra Support Bot
BotFather: Good. Now let's choose a username for your bot.
You: evolentra_support_bot
BotFather: Done! Congratulations on your new bot.
         Use this token to access the HTTP API:
         123456789:ABCdefGHIjklMNOpqrsTUVwxyz
```

---

## Step 2: Install Dependencies ⏳
**Status**: Running automatically...

Command: `composer install`

This will install:
- telegram-bot/api
- guzzlehttp/guzzle
- monolog/monolog

---

## Step 3: Update Database ⏳
**Status**: Running automatically...

Command: `mysql -u root mlm_system < setup_telegram_schema.sql`

This creates:
- telegram_id column in mlm_users
- mlm_telegram_states table
- mlm_telegram_tickets table
- mlm_telegram_notifications table

---

## Step 4: Set Bot Token

### Option A: Environment Variable (Recommended)
**Windows:**
```cmd
set TELEGRAM_BOT_TOKEN=YOUR_TOKEN_HERE
```

**PowerShell:**
```powershell
$env:TELEGRAM_BOT_TOKEN="YOUR_TOKEN_HERE"
```

### Option B: Update .env file
Create/edit `.env` file:
```
TELEGRAM_BOT_TOKEN=YOUR_TOKEN_HERE
```

### Option C: Direct in code
Edit `telegram_bot.php` line 15:
```php
$BOT_TOKEN = 'YOUR_TOKEN_HERE';
```

---

## Step 5: Start Bot

### Development Mode (Polling):
```bash
php telegram_bot.php
```

You should see:
```
Telegram Bot started (polling mode)...
```

### Production Mode (Webhook):
1. Set webhook URL:
```bash
curl -F "url=https://yourdomain.com/telegram_webhook.php" \
     https://api.telegram.org/botYOUR_TOKEN/setWebhook
```

2. Verify:
```bash
curl https://api.telegram.org/botYOUR_TOKEN/getWebhookInfo
```

---

## Step 6: Test Commands

1. **Find your bot** in Telegram
   - Search for your bot username
   - Or use the link from BotFather

2. **Send**: `/start`

3. **Expected response**:
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

4. **Link your account**:
   - Send your registered email
   - Bot will confirm: "✅ Account linked successfully!"

5. **Test other commands**:
   - `/balance` - Check wallets
   - `/stats` - View statistics
   - `/referral` - Get referral link
   - `/help` - See all commands

---

## Troubleshooting

### Bot not responding?
1. Check token is correct
2. Verify bot is running
3. Check for errors in console
4. Try `/start` again

### Can't install dependencies?
1. Check Composer is installed: `composer --version`
2. Install Composer: https://getcomposer.org/download/
3. Run: `composer install` again

### Database error?
1. Check MySQL is running
2. Verify database name: `mlm_system`
3. Run SQL manually in phpMyAdmin

### Account linking failed?
1. Use exact email from registration
2. Check email exists: `SELECT * FROM mlm_users WHERE email = 'your@email.com'`
3. Try again with correct email

---

## Next Steps After Setup

1. **Configure notifications**
   - Edit notification preferences
   - Set up daily summaries
   - Enable transaction alerts

2. **Customize messages**
   - Edit welcome message
   - Update help text
   - Add custom commands

3. **Set up webhooks** (for production)
   - Get SSL certificate
   - Configure webhook URL
   - Test webhook delivery

4. **Monitor usage**
   - Check logs
   - Monitor active users
   - Track support tickets

---

## Quick Reference

### Bot Commands
- `/start` - Start bot
- `/help` - Show help
- `/balance` - Check balances
- `/stats` - View stats
- `/referral` - Get referral link
- `/support` - Contact support
- `/stake` - Staking info
- `/transactions` - Recent transactions

### Admin Tasks
- View logs: `tail -f telegram_bot.log`
- Check users: `SELECT COUNT(*) FROM mlm_users WHERE telegram_id IS NOT NULL`
- Send broadcast: Use admin panel

### Support
- Documentation: TELEGRAM_BOT_README.md
- Issues: Check error logs
- Help: Contact development team

---

**Status**: Ready to configure! 🚀
