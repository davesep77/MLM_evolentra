# Evolentra Telegram Bot

## Overview
A comprehensive Telegram bot for Evolentra MLM platform that allows users to:
- Check balances and statistics
- View referral links
- Request support
- Monitor staking
- View transactions
- Get notifications

## Features

### 🤖 Bot Commands
- `/start` - Start interaction and link account
- `/help` - Show available commands
- `/balance` - Check wallet balances
- `/stats` - View account statistics
- `/referral` - Get referral link
- `/support` - Contact support team
- `/stake` - View staking information
- `/withdraw` - Request withdrawal
- `/transactions` - View recent transactions
- `/settings` - Bot settings

### 💬 Interactive Features
- Inline keyboard buttons for quick actions
- Account linking via email
- Support ticket creation
- Real-time balance updates
- Transaction notifications
- Referral link sharing

## Setup Instructions

### 1. Create Telegram Bot

1. Open Telegram and search for `@BotFather`
2. Send `/newbot` command
3. Follow instructions to create your bot
4. Copy the bot token (looks like: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`)

### 2. Install Dependencies

```bash
cd d:\xampp\htdocs\MLM_Evolentra
composer install
```

### 3. Update Database Schema

```bash
mysql -u root -p mlm_system < setup_telegram_schema.sql
```

### 4. Configure Bot Token

**Option A: Environment Variable**
```bash
# Windows
set TELEGRAM_BOT_TOKEN=your_bot_token_here

# Linux/Mac
export TELEGRAM_BOT_TOKEN=your_bot_token_here
```

**Option B: .env File**
```
TELEGRAM_BOT_TOKEN=your_bot_token_here
```

**Option C: Direct in Code**
Edit `telegram_bot.php` line 15:
```php
$BOT_TOKEN = 'your_bot_token_here';
```

### 5. Choose Running Mode

#### Polling Mode (Recommended for Development)
```bash
php telegram_bot.php
```

#### Webhook Mode (Recommended for Production)

1. **Set Webhook URL**
```bash
curl -F "url=https://yourdomain.com/telegram_webhook.php" \
     https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook
```

2. **Verify Webhook**
```bash
curl https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getWebhookInfo
```

3. **Configure SSL** (Required for webhooks)
   - Ensure your domain has valid SSL certificate
   - Telegram only accepts HTTPS webhooks

## Usage Guide

### For Users

1. **Link Account**
   - Start bot: `/start`
   - Send your registered email
   - Account will be linked automatically

2. **Check Balance**
   - Command: `/balance`
   - Or click "💰 Check Balance" button

3. **Get Referral Link**
   - Command: `/referral`
   - Share link directly from Telegram

4. **Create Support Ticket**
   - Command: `/support`
   - Click "📝 Create Ticket"
   - Describe your issue

5. **View Statistics**
   - Command: `/stats`
   - See investment, team, earnings

### For Admins

#### Send Notifications to All Users
```php
// In your admin panel or cron job
$users = $conn->query("SELECT telegram_id FROM mlm_users WHERE telegram_id IS NOT NULL");
foreach ($users as $user) {
    $bot->sendMessage($user['telegram_id'], "Important announcement!");
}
```

#### Send Individual Notification
```php
$bot->sendMessage($telegram_id, "Your withdrawal has been approved!");
```

## Bot Architecture

### Message Flow
```
User Message → Telegram Server → Webhook/Polling → telegram_bot.php
                                                          ↓
                                                    processUpdate()
                                                          ↓
                                            handleCommand() / handleMessage()
                                                          ↓
                                                    Database Query
                                                          ↓
                                                    Send Response
```

### Database Integration
- `mlm_users.telegram_id` - Links Telegram to user account
- `mlm_telegram_states` - Tracks conversation states
- `mlm_telegram_tickets` - Support tickets
- `mlm_telegram_notifications` - Notification queue

## Advanced Features

### 1. Automated Notifications

Create a cron job to send daily summaries:

```php
// daily_telegram_summary.php
require 'telegram_bot.php';

$users = $conn->query("
    SELECT u.telegram_id, w.* 
    FROM mlm_users u 
    JOIN mlm_wallets w ON u.id = w.user_id 
    WHERE u.telegram_notifications = 1 AND u.telegram_id IS NOT NULL
");

foreach ($users as $user) {
    $msg = "📊 Daily Summary\n\n";
    $msg .= "Balance: $" . $user['main_wallet'] . "\n";
    $msg .= "ROI Today: $" . calculateDailyROI($user['user_id']);
    
    $bot->sendMessage($user['telegram_id'], $msg);
}
```

### 2. Transaction Alerts

Trigger on new transactions:

```php
// In your transaction processing code
if ($user['telegram_id'] && $user['telegram_notifications']) {
    $bot->sendMessage(
        $user['telegram_id'],
        "💰 New transaction: $" . $amount . " (" . $type . ")"
    );
}
```

### 3. Referral Notifications

Alert when someone joins via referral:

```php
if ($sponsor['telegram_id']) {
    $bot->sendMessage(
        $sponsor['telegram_id'],
        "🎉 New referral! " . $newUser['full_name'] . " just joined your team!"
    );
}
```

## Troubleshooting

### Bot Not Responding
1. Check bot token is correct
2. Verify bot is running (`ps aux | grep telegram_bot`)
3. Check logs for errors
4. Test with `/start` command

### Webhook Not Working
1. Verify SSL certificate is valid
2. Check webhook URL is accessible
3. Review Telegram webhook info:
   ```bash
   curl https://api.telegram.org/bot<TOKEN>/getWebhookInfo
   ```
4. Check server logs

### Account Linking Failed
1. Verify email exists in database
2. Check `mlm_users` table has `telegram_id` column
3. Ensure email format is valid

### Commands Not Working
1. Verify user account is linked
2. Check database connection
3. Review error logs
4. Test with `/help` command

## Security Considerations

1. **Token Protection**
   - Never commit bot token to git
   - Use environment variables
   - Rotate token if compromised

2. **User Verification**
   - Always verify telegram_id matches user
   - Validate all user inputs
   - Sanitize messages before database

3. **Rate Limiting**
   - Implement rate limits for commands
   - Prevent spam/abuse
   - Monitor unusual activity

4. **Data Privacy**
   - Don't send sensitive data via Telegram
   - Use secure links for transactions
   - Comply with GDPR/privacy laws

## Performance Optimization

### Polling Mode
- Adjust update interval (default: 60s)
- Process updates in batches
- Use database connection pooling

### Webhook Mode
- Use queue for heavy operations
- Respond quickly (< 200ms)
- Process in background if needed

## Monitoring

### Check Bot Status
```bash
# Polling mode
ps aux | grep telegram_bot

# Webhook mode
curl https://api.telegram.org/bot<TOKEN>/getWebhookInfo
```

### View Logs
```bash
tail -f telegram_bot.log
```

### Database Queries
```sql
-- Active users
SELECT COUNT(*) FROM mlm_users WHERE telegram_id IS NOT NULL;

-- Recent interactions
SELECT * FROM mlm_admin_logs 
WHERE action = 'telegram_interaction' 
ORDER BY created_at DESC LIMIT 10;

-- Open tickets
SELECT * FROM mlm_telegram_tickets WHERE status = 'open';
```

## Deployment

### Production Checklist
- [ ] Set up webhook (not polling)
- [ ] Configure SSL certificate
- [ ] Set environment variables
- [ ] Enable error logging
- [ ] Set up monitoring
- [ ] Configure rate limiting
- [ ] Test all commands
- [ ] Create admin commands
- [ ] Set up notification system
- [ ] Document for support team

### Scaling
- Use Redis for session storage
- Implement message queue (RabbitMQ)
- Load balance webhook handlers
- Cache frequent queries
- Use CDN for media files

## Support

For issues:
1. Check this documentation
2. Review error logs
3. Test with BotFather
4. Contact development team

## License
MIT License
