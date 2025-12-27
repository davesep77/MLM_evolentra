# 🚀 SUPER QUICK START - Telegram Bot

## ⚡ 3-Minute Setup (No Composer Needed!)

I've created the **EASIEST** possible setup for you!

---

## 🎯 Option 1: Ultra-Simple (RECOMMENDED)

### Just run this:
```cmd
START_BOT.bat
```

That's it! The script will:
1. ✅ Open phpMyAdmin for you
2. ✅ Guide you to create the bot
3. ✅ Ask for your token
4. ✅ Start the bot automatically

**Total time: 3 minutes!**

---

## 📋 What You'll Do:

### Step 1: Update Database (30 seconds)
- Script opens phpMyAdmin
- Copy/paste the SQL it shows you
- Click "Go"

### Step 2: Create Bot (1 minute)
- Open Telegram
- Search: `@BotFather`
- Send: `/newbot`
- Follow prompts
- Copy token

### Step 3: Start Bot (30 seconds)
- Paste token when asked
- Bot starts automatically!

---

## 🎉 That's All!

No Composer installation needed!
No complex setup!
Just 3 simple steps!

---

## 🧪 Test Your Bot

1. Find your bot in Telegram
2. Send: `/start`
3. Send your email
4. Try: `/balance`

---

## 💡 Alternative: Manual Setup

If you prefer to do it manually:

### 1. Update Database
Run this SQL in phpMyAdmin:
```sql
ALTER TABLE mlm_users 
ADD COLUMN telegram_id VARCHAR(50) DEFAULT NULL,
ADD COLUMN telegram_username VARCHAR(100) DEFAULT NULL;
```

### 2. Create Bot
- Telegram → @BotFather → /newbot
- Copy token

### 3. Edit File
Open `telegram_bot_simple.php`
Line 11: Replace `YOUR_BOT_TOKEN_HERE` with your token

### 4. Start Bot
```cmd
d:\xampp\php\php.exe telegram_bot_simple.php
```

---

## ✅ Features Included

- ✅ Check balances
- ✅ View statistics
- ✅ Get referral link
- ✅ View transactions
- ✅ Support system
- ✅ Account linking
- ✅ All major commands

---

## 🆘 Need Help?

The `START_BOT.bat` script guides you through everything!

Just double-click it and follow the instructions.

---

**Ready?** Double-click `START_BOT.bat` now!
